<?php
require_once(__DIR__ . '/BridgeInterface.php');
abstract class BridgeAbstract implements BridgeInterface {

	const NAME = 'Unnamed bridge';
	const URI = '';
	const DESCRIPTION = 'No description provided';
	const MAINTAINER = 'No maintainer';
	const CACHE_TIMEOUT = 3600;
	const PARAMETERS = array();

	protected $cache;
	protected $extraInfos;
	protected $items = array();
	protected $inputs = array();
	protected $queriedContext = '';

	/**
	* Return cachable datas (extrainfos and items) stored in the bridge
	* @return mixed
	*/
	public function getCachable(){
		return array("items" => $this->getItems(), "extraInfos" => $this->getExtraInfos());
	}

	/**
	* Return items stored in the bridge
	* @return mixed
	*/
	public function getItems(){
		return $this->items;
	}


	protected function setInputs(array $inputs, $queriedContext){
		// Import and assign all inputs to their context
		foreach($inputs as $name => $value){
			foreach(static::PARAMETERS as $context => $set){
				if(array_key_exists($name, static::PARAMETERS[$context])){
					$this->inputs[$context][$name]['value'] = $value;
				}
			}
		}

		// Apply default values to missing data
		$contexts = array($queriedContext);
		if(array_key_exists('global', static::PARAMETERS)){
			$contexts[] = 'global';
		}

		foreach($contexts as $context){
			foreach(static::PARAMETERS[$context] as $name => $properties){
				if(isset($this->inputs[$context][$name]['value'])){
					continue;
				}

				$type = isset($properties['type']) ? $properties['type'] : 'text';

				switch($type){
				case 'checkbox':
					if(!isset($properties['defaultValue'])){
						$this->inputs[$context][$name]['value'] = false;
					} else {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				case 'list':
					if(!isset($properties['defaultValue'])){
						$firstItem = reset($properties['values']);
						if(is_array($firstItem)){
							$firstItem = reset($firstItem);
						}
						$this->inputs[$context][$name]['value'] = $firstItem;
					} else {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				default:
					if(isset($properties['defaultValue'])){
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				}
			}
		}

		// Copy global parameter values to the guessed context
		if(array_key_exists('global', static::PARAMETERS)){
			foreach(static::PARAMETERS['global'] as $name => $properties){
				if(isset($inputs[$name])){
					$value = $inputs[$name];
				} elseif (isset($properties['value'])){
					$value = $properties['value'];
				} else {
					continue;
				}
				$this->inputs[$queriedContext][$name]['value'] = $value;
			}
		}

		// Only keep guessed context parameters values
		if(isset($this->inputs[$queriedContext])){
			$this->inputs = array($queriedContext => $this->inputs[$queriedContext]);
		} else {
			$this->inputs = array();
		}
	}

	protected function getQueriedContext(array $inputs){
		$queriedContexts = array();
		foreach(static::PARAMETERS as $context => $set){
			$queriedContexts[$context] = null;
			foreach($set as $id => $properties){
				if(isset($inputs[$id]) && !empty($inputs[$id])){
					$queriedContexts[$context] = true;
				} elseif(isset($properties['required'])
				&& $properties['required'] === true){
					$queriedContexts[$context] = false;
					break;
				}
			}
		}

		if(array_key_exists('global', static::PARAMETERS)
		&& $queriedContexts['global'] === false){
			return null;
		}
		unset($queriedContexts['global']);

		switch(array_sum($queriedContexts)){
		case 0:
			foreach($queriedContexts as $context => $queried){
				if (is_null($queried)){
					return $context;
				}
			}
			return null;
		case 1: return array_search(true, $queriedContexts);
		default: return false;
		}
	}

	/**
	* Defined datas with parameters depending choose bridge
	* Note : you can define a cache with "setCache"
	* @param array array with expected bridge paramters
	*/
	public function setDatas(array $inputs){
		if(!is_null($this->cache)){
			$time = $this->cache->getTime();
			if($time !== false
			&& (time() - static::CACHE_TIMEOUT < $time)
			&& (!defined('DEBUG') || DEBUG !== true)){
				$cached = $this->cache->loadData();
				if(isset($cached['items']) && isset($cached['extraInfos'])){
					$this->items = $cached['items'];
					$this->extraInfos = $cached['extraInfos'];
					return;
				}
			}
		}

		if(empty(static::PARAMETERS)){
			if(!empty($inputs)){
				returnClientError('Invalid parameters value(s)');
			}

			$this->collectData();
			if(!is_null($this->cache)){
				$this->cache->saveData($this->getCachable());
			}
			return;
		}

		if(!validateData($inputs, static::PARAMETERS)){
			returnClientError('Invalid parameters value(s)');
		}

		// Guess the paramter context from input data
		$this->queriedContext = $this->getQueriedContext($inputs);
		if(is_null($this->queriedContext)){
			returnClientError('Required parameter(s) missing');
		} elseif($this->queriedContext === false){
			returnClientError('Mixed context parameters');
		}

		$this->setInputs($inputs, $this->queriedContext);

		$this->collectData();

		if(!is_null($this->cache)){
			$this->cache->saveData($this->getCachable());
		}
	}

	function getInput($input){
		if(!isset($this->inputs[$this->queriedContext][$input]['value'])){
			return null;
		}
		return $this->inputs[$this->queriedContext][$input]['value'];
	}

	public function getName(){
		return static::NAME;
	}

	public function getURI(){
		return static::URI;
	}

	public function getExtraInfos(){
		return array("name" => $this->getName(), "uri" => $this->getURI());
	}

	public function setCache(\CacheInterface $cache){
		$this->cache = $cache;
	}
}
