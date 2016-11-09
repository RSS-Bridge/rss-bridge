<?php
function validateData(&$data,$parameters){
	$validateTextValue = function($value, $pattern = null){
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
	};

	$validateNumberValue = function($value){
		$filteredValue = filter_var($value, FILTER_VALIDATE_INT);

		if($filteredValue === false && !empty($value))
			return null;

		return $filteredValue;
	};

	$validateCheckboxValue = function($value){
		$filteredValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

		if(is_null($filteredValue))
			return null;

		return $filteredValue;
	};

	$validateListValue = function($value, $expectedValues){
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
	};

	if(!is_array($data))
		return false;

	foreach($data as $name => $value){
		$registered = false;
		foreach($parameters as $context => $set){
			if(array_key_exists($name, $set)){
				$registered = true;
				if(!isset($set[$name]['type'])){
					$set[$name]['type'] = 'text';
				}

				switch($set[$name]['type']){
				case 'number':
					$data[$name] = $validateNumberValue($value);
					break;
				case 'checkbox':
					$data[$name] = $validateCheckboxValue($value);
					break;
				case 'list':
					$data[$name] = $validateListValue($value, $set[$name]['values']);
					break;
				default:
				case 'text':
					if(isset($set[$name]['pattern'])){
						$data[$name] = $validateTextValue($value, $set[$name]['pattern']);
					} else {
						$data[$name] = $validateTextValue($value);
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
