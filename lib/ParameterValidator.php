<?php

class ParameterValidator
{
    private array $invalid = [];

    /**
     * Check that inputs are actually present in the bridge parameters.
     *
     * Also check whether input values are allowed.
     */
    public function validateInput(&$input, $parameters): bool
    {
        if (!is_array($input)) {
            return false;
        }

        foreach ($input as $name => $value) {
            // Some RSS readers add a cache-busting parameter (_=<timestamp>) to feed URLs, detect and ignore them.
            if ($name === '_') {
                continue;
            }

            $registered = false;
            foreach ($parameters as $context => $set) {
                if (!array_key_exists($name, $set)) {
                    continue;
                }
                $registered = true;
                if (!isset($set[$name]['type'])) {
                    // Default type is text
                    $set[$name]['type'] = 'text';
                }

                switch ($set[$name]['type']) {
                    case 'number':
                        $input[$name] = $this->validateNumberValue($value);
                        break;
                    case 'checkbox':
                        $input[$name] = $this->validateCheckboxValue($value);
                        break;
                    case 'list':
                        $input[$name] = $this->validateListValue($value, $set[$name]['values']);
                        break;
                    default:
                    case 'text':
                        if (isset($set[$name]['pattern'])) {
                            $input[$name] = $this->validateTextValue($value, $set[$name]['pattern']);
                        } else {
                            $input[$name] = $this->validateTextValue($value);
                        }
                        break;
                }

                if (
                    is_null($input[$name])
                    && isset($set[$name]['required'])
                    && $set[$name]['required']
                ) {
                    $this->invalid[] = ['name' => $name, 'reason' => 'Parameter is invalid!'];
                }
            }

            if (!$registered) {
                $this->invalid[] = ['name' => $name, 'reason' => 'Parameter is not registered!'];
            }
        }

        return $this->invalid === [];
    }

    /**
     * Get the name of the context matching the provided inputs
     *
     * @param array $input Associative array of user data
     * @param array $parameters Array of bridge parameters
     * @return string|null Returns the context name or null if no match was found
     */
    public function getQueriedContext($input, $parameters)
    {
        $queriedContexts = [];

        // Detect matching context
        foreach ($parameters as $context => $set) {
            $queriedContexts[$context] = null;

            // Ensure all user data exist in the current context
            $notInContext = array_diff_key($input, $set);
            if (array_key_exists('global', $parameters)) {
                $notInContext = array_diff_key($notInContext, $parameters['global']);
            }
            if (count($notInContext) > 0) {
                continue;
            }

            // Check if all parameters of the context are satisfied
            foreach ($set as $id => $properties) {
                if (isset($input[$id]) && !empty($input[$id])) {
                    $queriedContexts[$context] = true;
                } elseif (
                    isset($properties['type'])
                    && ($properties['type'] === 'checkbox' || $properties['type'] === 'list')
                ) {
                    continue;
                } elseif (isset($properties['required']) && $properties['required'] === true) {
                    $queriedContexts[$context] = false;
                    break;
                }
            }
        }

        // Abort if one of the globally required parameters is not satisfied
        if (
            array_key_exists('global', $parameters)
            && $queriedContexts['global'] === false
        ) {
            return null;
        }
        unset($queriedContexts['global']);

        switch (array_sum($queriedContexts)) {
            case 0:
                // Found no match, is there a context without parameters?
                if (isset($input['context'])) {
                    return $input['context'];
                }
                foreach ($queriedContexts as $context => $queried) {
                    if (is_null($queried)) {
                        return $context;
                    }
                }
                return null;
            case 1:
                // Found unique match
                return array_search(true, $queriedContexts);
            default:
                return false;
        }
    }

    public function getInvalidParameters(): array
    {
        return $this->invalid;
    }

    private function validateTextValue($value, $pattern = null)
    {
        if (is_null($pattern)) {
            // No filtering taking place
            $filteredValue = filter_var($value);
        } else {
            $filteredValue = filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^' . $pattern . '$/']]);
        }
        if ($filteredValue === false) {
            return null;
        }
        return $filteredValue;
    }

    private function validateNumberValue($value)
    {
        $filteredValue = filter_var($value, FILTER_VALIDATE_INT);
        if ($filteredValue === false) {
            return null;
        }
        return $filteredValue;
    }

    private function validateCheckboxValue($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function validateListValue($value, $expectedValues)
    {
        $filteredValue = filter_var($value);
        if ($filteredValue === false) {
            return null;
        }
        if (!in_array($filteredValue, $expectedValues)) {
            // Check sub-values?
            foreach ($expectedValues as $subName => $subValue) {
                if (is_array($subValue) && in_array($filteredValue, $subValue)) {
                    return $filteredValue;
                }
            }
            return null;
        }
        return $filteredValue;
    }
}
