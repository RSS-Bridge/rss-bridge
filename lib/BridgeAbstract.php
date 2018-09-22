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
	protected $cacheTimeout;

	/**
	* Return cachable datas (extrainfos and items) stored in the bridge
	* @return mixed
	*/
	public function getCachable(){
		return array(
			'items' => $this->getItems(),
			'extraInfos' => $this->getExtraInfos()
		);
	}

	/**
	* Return items stored in the bridge
	* @return mixed
	*/
	public function getItems(){
		return $this->items;
	}

	/**
	 * Sets the input values for a given context. Existing values are
	 * overwritten.
	 *
	 * @param array $inputs Associative array of inputs
	 * @param string $context The context name
	 */
	protected function setInputs(array $inputs, $queriedContext){
		// Import and assign all inputs to their context
		foreach($inputs as $name => $value) {
			foreach(static::PARAMETERS as $context => $set) {
				if(array_key_exists($name, static::PARAMETERS[$context])) {
					$this->inputs[$context][$name]['value'] = $value;
				}
			}
		}

		// Apply default values to missing data
		$contexts = array($queriedContext);
		if(array_key_exists('global', static::PARAMETERS)) {
			$contexts[] = 'global';
		}

		foreach($contexts as $context) {
			foreach(static::PARAMETERS[$context] as $name => $properties) {
				if(isset($this->inputs[$context][$name]['value'])) {
					continue;
				}

				$type = isset($properties['type']) ? $properties['type'] : 'text';

				switch($type) {
				case 'checkbox':
					if(!isset($properties['defaultValue'])) {
						$this->inputs[$context][$name]['value'] = false;
					} else {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				case 'list':
					if(!isset($properties['defaultValue'])) {
						$firstItem = reset($properties['values']);
						if(is_array($firstItem)) {
							$firstItem = reset($firstItem);
						}
						$this->inputs[$context][$name]['value'] = $firstItem;
					} else {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				default:
					if(isset($properties['defaultValue'])) {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				}
			}
		}

		// Copy global parameter values to the guessed context
		if(array_key_exists('global', static::PARAMETERS)) {
			foreach(static::PARAMETERS['global'] as $name => $properties) {
				if(isset($inputs[$name])) {
					$value = $inputs[$name];
				} elseif(isset($properties['value'])) {
					$value = $properties['value'];
				} else {
					continue;
				}
				$this->inputs[$queriedContext][$name]['value'] = $value;
			}
		}

		// Only keep guessed context parameters values
		if(isset($this->inputs[$queriedContext])) {
			$this->inputs = array($queriedContext => $this->inputs[$queriedContext]);
		} else {
			$this->inputs = array();
		}
	}

	/**
	* Defined datas with parameters depending choose bridge
	* Note : you can define a cache with "setCache"
	* @param array array with expected bridge paramters
	*/
	public function setDatas(array $inputs){
		if(!is_null($this->cache)) {
			$time = $this->cache->getTime();
			if($time !== false
			&& (time() - $this->getCacheTimeout() < $time)
			&& (!defined('DEBUG') || DEBUG !== true)) {
				$cached = $this->cache->loadData();
				if(isset($cached['items']) && isset($cached['extraInfos'])) {
					$this->items = $cached['items'];
					$this->extraInfos = $cached['extraInfos'];
					return;
				}
			}
		}

		if(empty(static::PARAMETERS)) {
			if(!empty($inputs)) {
				returnClientError('Invalid parameters value(s)');
			}

			$this->collectData();
			if(!is_null($this->cache)) {
				$this->cache->saveData($this->getCachable());
			}
			return;
		}

		$validator = new ParameterValidator();

		if(!$validator->validateData($inputs, static::PARAMETERS)) {
			$parameters = array_map(
				function($i){ return $i['name']; }, // Just display parameter names
				$validator->getInvalidParameters()
			);

			returnClientError(
				'Invalid parameters value(s): '
				. implode(', ', $parameters)
			);
		}

		// Guess the paramter context from input data
		$this->queriedContext = $validator->getQueriedContext($inputs, static::PARAMETERS);
		if(is_null($this->queriedContext)) {
			returnClientError('Required parameter(s) missing');
		} elseif($this->queriedContext === false) {
			returnClientError('Mixed context parameters');
		}

		$this->setInputs($inputs, $this->queriedContext);

		$this->collectData();

		if(!is_null($this->cache)) {
			$this->cache->saveData($this->getCachable());
		}
	}

	/**
	 * Returns the value for the provided input
	 *
	 * @param string $input The input name
	 * @return mixed Returns the input value or null if the input is not defined
	 */
	protected function getInput($input){
		if(!isset($this->inputs[$this->queriedContext][$input]['value'])) {
			return null;
		}
		return $this->inputs[$this->queriedContext][$input]['value'];
	}

	public function getDescription(){
		return static::DESCRIPTION;
	}

	public function getMaintainer(){
		return static::MAINTAINER;
	}

	public function getName(){
		// Return cached name when bridge is using cached data
		if(isset($this->extraInfos)) {
			return $this->extraInfos['name'];
		}

		return static::NAME;
	}

	public function getIcon(){
		// Return cached icon when bridge is using cached data
		if(isset($this->extraInfos)) {
			return $this->extraInfos['icon'];
		}

		return '';
	}

	public function getParameters(){
		return static::PARAMETERS;
	}

	public function getURI(){
		// Return cached uri when bridge is using cached data
		if(isset($this->extraInfos)) {
			return $this->extraInfos['uri'];
		}

		return static::URI;
	}

	public function getExtraInfos(){
		return array(
			'name' => $this->getName(),
			'uri' => $this->getURI(),
			'icon' => $this->getIcon()
		);
	}

	public function setCache(\CacheInterface $cache){
		$this->cache = $cache;
	}

	public function setCacheTimeout($timeout){
		if(is_numeric($timeout) && ($timeout < 1 || $timeout > 86400)) {
			$this->cacheTimeout = static::CACHE_TIMEOUT;
			return;
		}

		$this->cacheTimeout = $timeout;
	}

	public function getCacheTimeout(){
		return isset($this->cacheTimeout) ? $this->cacheTimeout : static::CACHE_TIMEOUT;
	}

	public function getCacheTime(){
		return !is_null($this->cache) ? $this->cache->getTime() : false;
	}

	public function dieIfNotModified(){
		if ((defined('DEBUG') && DEBUG === true)) return; // disabled in debug mode

		$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
		if (!$if_modified_since) return; // If-Modified-Since value is required

		$last_modified = $this->getCacheTime();
		if (!$last_modified) return; // did not detect cache time

		if (time() - $this->getCacheTimeout() > $last_modified) return; // cache timeout

		$last_modified = (gmdate('D, d M Y H:i:s ', $last_modified) . 'GMT');

		if ($if_modified_since == $last_modified) {
			header('HTTP/1.1 304 Not Modified');
			die();
		}
	}
}
