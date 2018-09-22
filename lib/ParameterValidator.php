<?php
/**
 * Implements a validator for bridge parameters
 */
class ParameterValidator {
	private $invalid = array();

	private function addInvalidParameter($name, $reason){
		$this->invalid[] = array(
			'name' => $name,
			'reason' => $reason
		);
	}

	/**
	 * Returns an array of invalid parameters, where each element is an
	 * array of 'name' and 'reason'.
	 */
	public function getInvalidParameters() {
		return $this->invalid;
	}

	private function validateTextValue($value, $pattern = null){
		if(!is_null($pattern)) {
			$filteredValue = filter_var($value,
			FILTER_VALIDATE_REGEXP,
			array('options' => array(
					'regexp' => '/^' . $pattern . '$/'
				)
			));
		} else {
			$filteredValue = filter_var($value);
		}

		if($filteredValue === false)
			return null;

		return $filteredValue;
	}

	private function validateNumberValue($value){
		$filteredValue = filter_var($value, FILTER_VALIDATE_INT);

		if($filteredValue === false)
			return null;

		return $filteredValue;
	}

	private function validateCheckboxValue($value){
		return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
	}

	private function validateListValue($value, $expectedValues){
		$filteredValue = filter_var($value);

		if($filteredValue === false)
			return null;

		if(!in_array($filteredValue, $expectedValues)) { // Check sub-values?
			foreach($expectedValues as $subName => $subValue) {
				if(is_array($subValue) && in_array($filteredValue, $subValue))
					return $filteredValue;
			}
			return null;
		}

		return $filteredValue;
	}

	/**
	 * Checks if all required parameters are supplied by the user
	 * @param $data An array of parameters provided by the user
	 * @param $parameters An array of bridge parameters
	 */
	public function validateData(&$data, $parameters){

		if(!is_array($data))
			return false;

		foreach($data as $name => $value) {
			$registered = false;
			foreach($parameters as $context => $set) {
				if(array_key_exists($name, $set)) {
					$registered = true;
					if(!isset($set[$name]['type'])) {
						$set[$name]['type'] = 'text';
					}

					switch($set[$name]['type']) {
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
						if(isset($set[$name]['pattern'])) {
							$data[$name] = $this->validateTextValue($value, $set[$name]['pattern']);
						} else {
							$data[$name] = $this->validateTextValue($value);
						}
						break;
					}

					if(is_null($data[$name]) && isset($set[$name]['required']) && $set[$name]['required']) {
						$this->addInvalidParameter($name, 'Parameter is invalid!');
					}
				}
			}

			if(!$registered) {
				$this->addInvalidParameter($name, 'Parameter is not registered!');
			}
		}

		return empty($this->invalid);
	}

	/**
	 * Returns the name of the context matching the provided inputs
	 *
	 * @param array $data Associative array of user data
	 * @param array $parameters Array of bridge parameters
	 * @return mixed Returns the context name or null if no match was found
	 */
	public function getQueriedContext($data, $parameters){
		$queriedContexts = array();

		// Detect matching context
		foreach($parameters as $context => $set) {
			$queriedContexts[$context] = null;

			// Check if all parameters of the context are satisfied
			foreach($set as $id => $properties) {
				if(isset($data[$id]) && !empty($data[$id])) {
					$queriedContexts[$context] = true;
				} elseif(isset($properties['required'])
				&& $properties['required'] === true) {
					$queriedContexts[$context] = false;
					break;
				}
			}

		}

		// Abort if one of the globally required parameters is not satisfied
		if(array_key_exists('global', $parameters)
		&& $queriedContexts['global'] === false) {
			return null;
		}
		unset($queriedContexts['global']);

		switch(array_sum($queriedContexts)) {
		case 0: // Found no match, is there a context without parameters?
			foreach($queriedContexts as $context => $queried) {
				if(is_null($queried)) {
					return $context;
				}
			}
			return null;
		case 1: // Found unique match
			return array_search(true, $queriedContexts);
		default: return false;
		}
	}
}
