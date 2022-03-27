<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

/**
 * Validator for bridge parameters
 */
class ParameterValidator
{
    /**
     * Holds the list of invalid parameters
     *
     * @var array
     */
    private $invalid = array();

    /**
     * Add item to list of invalid parameters
     *
     * @param string $name The name of the parameter
     * @param string $reason The reason for that parameter being invalid
     * @return void
     */
    private function addInvalidParameter($name, $reason)
    {
        $this->invalid[] = array(
            'name' => $name,
            'reason' => $reason
        );
    }

    /**
     * Return list of invalid parameters.
     *
     * Each element is an array of 'name' and 'reason'.
     *
     * @return array List of invalid parameters
     */
    public function getInvalidParameters()
    {
        return $this->invalid;
    }

    /**
     * Validate value for a text input
     *
     * @param string $value The value of a text input
     * @param string|null $pattern (optional) A regex pattern
     * @return string|null The filtered value or null if the value is invalid
     */
    private function validateTextValue($value, $pattern = null)
    {
        if (!is_null($pattern)) {
            $filteredValue = filter_var(
                $value,
                FILTER_VALIDATE_REGEXP,
                array('options' => array(
                    'regexp' => '/^' . $pattern . '$/'
                )
                )
            );
        } else {
            $filteredValue = filter_var($value);
        }

        if ($filteredValue === false) {
            return null;
        }

        return $filteredValue;
    }

    /**
     * Validate value for a number input
     *
     * @param int $value The value of a number input
     * @return int|null The filtered value or null if the value is invalid
     */
    private function validateNumberValue($value)
    {
        $filteredValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($filteredValue === false) {
            return null;
        }

        return $filteredValue;
    }

    /**
     * Validate value for a checkbox
     *
     * @param bool $value The value of a checkbox
     * @return bool The filtered value
     */
    private function validateCheckboxValue($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * Validate value for a list
     *
     * @param string $value The value of a list
     * @param array $expectedValues A list of expected values
     * @return string|null The filtered value or null if the value is invalid
     */
    private function validateListValue($value, $expectedValues)
    {
        $filteredValue = filter_var($value);

        if ($filteredValue === false) {
            return null;
        }

        if (!in_array($filteredValue, $expectedValues)) { // Check sub-values?
            foreach ($expectedValues as $subName => $subValue) {
                if (is_array($subValue) && in_array($filteredValue, $subValue)) {
                    return $filteredValue;
                }
            }
            return null;
        }

        return $filteredValue;
    }

    /**
     * Check if all required parameters are satisfied
     *
     * @param array $data (ref) A list of input values
     * @param array $parameters The bridge parameters
     * @return bool True if all parameters are satisfied
     */
    public function validateData(&$data, $parameters)
    {

        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $name => $value) {
            // Some RSS readers add a cache-busting parameter (_=<timestamp>) to feed URLs, detect and ignore them.
            if ($name === '_') {
                continue;
            }

            $registered = false;
            foreach ($parameters as $context => $set) {
                if (array_key_exists($name, $set)) {
                    $registered = true;
                    if (!isset($set[$name]['type'])) {
                        $set[$name]['type'] = 'text';
                    }

                    switch ($set[$name]['type']) {
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
                            if (isset($set[$name]['pattern'])) {
                                $data[$name] = $this->validateTextValue($value, $set[$name]['pattern']);
                            } else {
                                $data[$name] = $this->validateTextValue($value);
                            }
                            break;
                    }

                    if (is_null($data[$name]) && isset($set[$name]['required']) && $set[$name]['required']) {
                        $this->addInvalidParameter($name, 'Parameter is invalid!');
                    }
                }
            }

            if (!$registered) {
                $this->addInvalidParameter($name, 'Parameter is not registered!');
            }
        }

        return empty($this->invalid);
    }

    /**
     * Get the name of the context matching the provided inputs
     *
     * @param array $data Associative array of user data
     * @param array $parameters Array of bridge parameters
     * @return string|null Returns the context name or null if no match was found
     */
    public function getQueriedContext($data, $parameters)
    {
        $queriedContexts = array();

        // Detect matching context
        foreach ($parameters as $context => $set) {
            $queriedContexts[$context] = null;

            // Ensure all user data exist in the current context
            $notInContext = array_diff_key($data, $set);
            if (array_key_exists('global', $parameters)) {
                $notInContext = array_diff_key($notInContext, $parameters['global']);
            }
            if (sizeof($notInContext) > 0) {
                continue;
            }

            // Check if all parameters of the context are satisfied
            foreach ($set as $id => $properties) {
                if (isset($data[$id]) && !empty($data[$id])) {
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
            case 0: // Found no match, is there a context without parameters?
                if (isset($data['context'])) {
                    return $data['context'];
                }
                foreach ($queriedContexts as $context => $queried) {
                    if (is_null($queried)) {
                        return $context;
                    }
                }
                return null;
            case 1: // Found unique match
                return array_search(true, $queriedContexts);
            default:
                return false;
        }
    }
}
