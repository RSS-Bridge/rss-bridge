<?php

class ParameterValidator
{
    /**
     * Validate and sanitize user inputs against configured bridge parameters (contexts)
     */
    public function validateInput(array &$input, $contexts): array
    {
        $errors = [];

        foreach ($input as $name => $value) {
            if ($name === UrlEncryptionService::PARAMETER_NAME && UrlEncryptionService::enabled()) {
                // Do not validate against encrypted URL tokens.
                continue;
            }

            $registered = false;

            foreach ($contexts as $contextName => $contextParameters) {
                if (!array_key_exists($name, $contextParameters)) {
                    continue;
                }
                $registered = true;
                if (!isset($contextParameters[$name]['type'])) {
                    // Default type is text
                    $contextParameters[$name]['type'] = 'text';
                }

                switch ($contextParameters[$name]['type']) {
                    case 'number':
                        $input[$name] = $this->validateNumberValue($value);
                        break;
                    case 'checkbox':
                        $input[$name] = $this->validateCheckboxValue($value);
                        break;
                    case 'list':
                        $input[$name] = $this->validateListValue($value, $contextParameters[$name]['values']);
                        break;
                    default:
                    case 'text':
                        if (isset($contextParameters[$name]['pattern'])) {
                            $input[$name] = $this->validateTextValue($value, $contextParameters[$name]['pattern']);
                        } else {
                            $input[$name] = $this->validateTextValue($value);
                        }
                        break;
                }

                if (
                    is_null($input[$name])
                    && isset($contextParameters[$name]['required'])
                    && $contextParameters[$name]['required']
                ) {
                    $errors[] = ['name' => $name, 'reason' => 'Parameter is invalid!'];
                }
            }

            if (!$registered) {
                $errors[] = ['name' => $name, 'reason' => 'Parameter is not registered!'];
            }
        }

        return $errors;
    }

    /**
     * Get the name of the context matching the provided inputs
     *
     * @param array $input Associative array of user data
     * @param array $contexts Array of bridge parameters
     * @return string|null Returns the context name or null if no match was found
     */
    public function getQueriedContext(array $input, array $contexts)
    {
        $queriedContexts = [];

        // Detect matching context
        foreach ($contexts as $contextName => $contextParameters) {
            $queriedContexts[$contextName] = null;

            // Ensure all user data exist in the current context
            $notInContext = array_diff_key($input, $contextParameters);
            if (array_key_exists('global', $contexts)) {
                $notInContext = array_diff_key($notInContext, $contexts['global']);
            }
            if (count($notInContext) > 0) {
                continue;
            }

            // Check if all parameters of the context are satisfied
            foreach ($contextParameters as $id => $properties) {
                if (!empty($input[$id])) {
                    $queriedContexts[$contextName] = true;
                } elseif (
                    isset($properties['type'])
                    && ($properties['type'] === 'checkbox' || $properties['type'] === 'list')
                ) {
                    continue;
                } elseif (isset($properties['required']) && $properties['required'] === true) {
                    $queriedContexts[$contextName] = false;
                    break;
                }
            }
        }

        // Abort if one of the globally required parameters is not satisfied
        if (
            array_key_exists('global', $contexts)
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
                foreach ($queriedContexts as $context2 => $queried) {
                    if (is_null($queried)) {
                        return $context2;
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
