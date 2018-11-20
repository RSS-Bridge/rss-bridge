<?php

abstract class BridgeAbstract implements BridgeInterface {

	const NAME = 'Unnamed bridge';
	const URI = '';
	const DESCRIPTION = 'No description provided';
	const MAINTAINER = 'No maintainer';
	const CACHE_TIMEOUT = 3600;
	const PARAMETERS = array();

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
	* @param array array with expected bridge paramters
	*/
	public function setDatas(array $inputs){

		if(empty(static::PARAMETERS)) {

			if(!empty($inputs)) {
				returnClientError('Invalid parameters value(s)');
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
		return static::NAME;
	}

	public function getIcon(){
		return '';
	}

	public function getParameters(){
		return static::PARAMETERS;
	}

	public function getURI(){
		return static::URI;
	}

	public function getCacheTimeout(){
		return static::CACHE_TIMEOUT;
	}

	/** {@inheritdoc} */
	public function detectParameters($url){
		$regex = '/^(https?:\/\/)?(www\.)?(.+?)(\/)?$/';
		if(empty(static::PARAMETERS)
		&& preg_match($regex, $url, $urlMatches) > 0
		&& preg_match($regex, static::URI, $bridgeUriMatches) > 0
		&& $urlMatches[3] === $bridgeUriMatches[3]) {
			return array();
		} else {
			return null;
		}
	}

}
