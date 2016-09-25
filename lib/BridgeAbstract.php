<?php
require_once(__DIR__ . '/BridgeInterface.php');
abstract class BridgeAbstract implements BridgeInterface {

	const NAME = 'Unnamed bridge';
	const URI = '';
	const DESCRIPTION = 'No description provided';
	const MAINTAINER = 'No maintainer';
	const PARAMETERS = array();

	protected $cache;
	protected $items = array();
	protected $inputs = array();
	protected $queriedContext = '';

	/**
	* Return items stored in the bridge
	* @return mixed
	*/
	public function getItems(){
		return $this->items;
	}

	protected function validateTextValue($value, $pattern = null){
		if(!is_null($pattern)){
			$filteredValue = filter_var($value
			, FILTER_VALIDATE_REGEXP
			, array('options' => array(
					'regexp' => '/^' . $pattern . '$/'
				))
			);
		} else {
			$filteredValue = filter_var($value);
		}

		if($filteredValue === false)
			return null;

		return $filteredValue;
	}

	protected function validateNumberValue($value){
		$filteredValue = filter_var($value, FILTER_VALIDATE_INT);

		if($filteredValue === false && !empty($value))
			return null;

		return $filteredValue;
	}

	protected function validateCheckboxValue($value){
		$filteredValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

		if(is_null($filteredValue))
			return null;

		return $filteredValue;
	}

	protected function validateListValue($value, $expectedValues){
		$filteredValue = filter_var($value);

		if($filteredValue === false)
			return null;

		if(!in_array($filteredValue, $expectedValues)){ // Check sub-values?
			foreach($expectedValues as $subName => $subValue){
				if(is_array($subValue) && in_array($filteredValue, $subValue))
					return $filteredValue;
			}
			return null;
		}

		return $filteredValue;
	}

	protected function validateData(&$data){
		if(!is_array($data))
			return false;

		foreach($data as $name => $value){
			$registered = false;
			foreach(static::PARAMETERS as $context => $set){
				if(array_key_exists($name, $set)){
					$registered = true;
					if(!isset($set[$name]['type'])){
						$set[$name]['type'] = 'text';
					}

					switch($set[$name]['type']){
					case 'number':
						$data[$name] = $this->validateNumberValue($value);
						break;
					case 'checkbox':
						$data[$name] = $this->validateCheckboxValue($value);
						break;
					case 'list':
						$data[$name] = $this->validateListValue($value, $set[$name]['values']);
						break;
					default:
					case 'text':
						if(isset($set[$name]['pattern'])){
							$data[$name] = $this->validateTextValue($value, $set[$name]['pattern']);
						} else {
							$data[$name] = $this->validateTextValue($value);
						}
						break;
					}

					if(is_null($data[$name])){
						echo 'Parameter \'' . $name . '\' is invalid!' . PHP_EOL;
						return false;
					}
				}
			}

			if(!$registered)
				return false;
		}

		return true;
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
			$this->cache->prepare($inputs);
			$time = $this->cache->getTime();
			if($time !== false && (time() - $this->getCacheDuration() < $time)){
				$this->items = $this->cache->loadData();
				return;
			}
		}

		if(empty(static::PARAMETERS)){
			if(!empty($inputs)){
				returnClientError('Invalid parameters value(s)');
			}

			$this->collectData();
			if(!is_null($this->cache)){
				$this->cache->saveData($this->getItems());
			}
			return;
		}

		if(!$this->validateData($inputs)){
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
			$this->cache->saveData($this->getItems());
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

	public function getCacheDuration(){
		return 3600;
	}

	public function setCache(\CacheAbstract $cache){
		$this->cache = $cache;
	}
}
