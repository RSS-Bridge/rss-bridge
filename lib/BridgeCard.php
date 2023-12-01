<?php

final class BridgeCard
{
    /**
     * Gets a single bridge card
     *
     * @param class-string<BridgeAbstract> $bridgeClassName The bridge name
     * @param array $formats A list of formats
     * @param bool $isActive Indicates if the bridge is active or not
     * @return string The bridge card
     */
    public static function displayBridgeCard($bridgeClassName, $formats, $isActive = true)
    {
        $bridgeFactory = new BridgeFactory();

        $bridge = $bridgeFactory->create($bridgeClassName);

        $isHttps = strpos($bridge->getURI(), 'https') === 0;

        $uri = $bridge->getURI();
        $name = $bridge->getName();
        $icon = $bridge->getIcon();
        $description = $bridge->getDescription();
        $parameters = $bridge->getParameters();
        if (Configuration::getConfig('proxy', 'url') && Configuration::getConfig('proxy', 'by_bridge')) {
            $parameters['global']['_noproxy'] = [
                'name' => 'Disable proxy (' . (Configuration::getConfig('proxy', 'name') ?: Configuration::getConfig('proxy', 'url')) . ')',
                'type' => 'checkbox'
            ];
        }

        if (Configuration::getConfig('cache', 'custom_timeout')) {
            $parameters['global']['_cache_timeout'] = [
                'name' => 'Cache timeout in seconds',
                'type' => 'number',
                'defaultValue' => $bridge->getCacheTimeout()
            ];
        }

        $shortName = $bridge->getShortName();
        $card = <<<CARD
                <section
                    class="bridge-card"
                    id="bridge-{$bridgeClassName}"
                    data-ref="{$name}"
                    data-short-name="$shortName"
                >

                <h2><a href="{$uri}">{$name}</a></h2>
                <p class="description">{$description}</p>
                <input type="checkbox" class="showmore-box" id="showmore-{$bridgeClassName}" />
                <label class="showmore" for="showmore-{$bridgeClassName}">Show more</label>
CARD;

        // If we don't have any parameter for the bridge, we print a generic form to load it.
        if (count($parameters) === 0) {
            $card .= self::getForm($bridgeClassName, $formats, $isActive, $isHttps);

            // Display form with cache timeout and/or noproxy options (if enabled) when bridge has no parameters
        } elseif (count($parameters) === 1 && array_key_exists('global', $parameters)) {
            $card .= self::getForm($bridgeClassName, $formats, $isActive, $isHttps, '', $parameters['global']);
        } else {
            foreach ($parameters as $parameterName => $parameter) {
                if (!is_numeric($parameterName) && $parameterName === 'global') {
                    continue;
                }

                if (array_key_exists('global', $parameters)) {
                    $parameter = array_merge($parameter, $parameters['global']);
                }

                if (!is_numeric($parameterName)) {
                    $card .= '<h5>' . $parameterName . '</h5>' . PHP_EOL;
                }

                $card .= self::getForm($bridgeClassName, $formats, $isActive, $isHttps, $parameterName, $parameter);
            }
        }

        $card .= sprintf('<label class="showless" for="showmore-%s">Show less</label>', $bridgeClassName);
        if ($bridge->getDonationURI() !== '' && Configuration::getConfig('admin', 'donations')) {
            $card .= sprintf(
                '<p class="maintainer">%s ~ <a href="%s">Donate</a></p>',
                $bridge->getMaintainer(),
                $bridge->getDonationURI()
            );
        } else {
            $card .= sprintf('<p class="maintainer">%s</p>', $bridge->getMaintainer());
        }
        $card .= '</section>';

        return $card;
    }

    /**
     * Get the form header for a bridge card
     *
     * @param class-string<BridgeAbstract> $bridgeClassName The bridge name
     * @param bool $isHttps If disabled, adds a warning to the form
     * @return string The form header
     */
    private static function getFormHeader($bridgeClassName, $isHttps = false, $parameterName = '')
    {
        $form = <<<EOD
            <form method="GET" action="?">
                <input type="hidden" name="action" value="display" />
                <input type="hidden" name="bridge" value="{$bridgeClassName}" />
EOD;

        if (!empty($parameterName)) {
            $form .= sprintf('<input type="hidden" name="context" value="%s" />', $parameterName);
        }

        if (!$isHttps) {
            $form .= '<div class="secure-warning">Warning :
This bridge is not fetching its content through a secure connection</div>';
        }

        return $form;
    }

    /**
     * Get the form body for a bridge
     *
     * @param class-string<BridgeAbstract> $bridgeClassName The bridge name
     * @param array $formats A list of supported formats
     * @param bool $isActive Indicates if a bridge is enabled or not
     * @param bool $isHttps Indicates if a bridge uses HTTPS or not
     * @param string $parameterName Sets the bridge context for the current form
     * @param array $parameters The bridge parameters
     * @return string The form body
     */
    private static function getForm(
        $bridgeClassName,
        $formats,
        $isActive = false,
        $isHttps = false,
        $parameterName = '',
        $parameters = []
    ) {
        $form = self::getFormHeader($bridgeClassName, $isHttps, $parameterName);

        if (count($parameters) > 0) {
            $form .= '<div class="parameters">';

            foreach ($parameters as $id => $inputEntry) {
                if (!isset($inputEntry['exampleValue'])) {
                    $inputEntry['exampleValue'] = '';
                }

                if (!isset($inputEntry['defaultValue'])) {
                    $inputEntry['defaultValue'] = '';
                }

                $idArg = 'arg-'
                    . urlencode($bridgeClassName)
                    . '-'
                    . urlencode($parameterName)
                    . '-'
                    . urlencode($id);

                $form .= '<label for="'
                    . $idArg
                    . '">'
                    . filter_var($inputEntry['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
                    . '</label>'
                    . PHP_EOL;

                if (!isset($inputEntry['type']) || $inputEntry['type'] === 'text') {
                    $form .= self::getTextInput($inputEntry, $idArg, $id);
                } elseif ($inputEntry['type'] === 'number') {
                    $form .= self::getNumberInput($inputEntry, $idArg, $id);
                } elseif ($inputEntry['type'] === 'list') {
                    $form .= self::getListInput($inputEntry, $idArg, $id);
                } elseif ($inputEntry['type'] === 'checkbox') {
                    $form .= self::getCheckboxInput($inputEntry, $idArg, $id);
                }

                $infoText = [];
                $infoTextScript = '';
                if (isset($inputEntry['title'])) {
                    $infoText[] = filter_var($inputEntry['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                }
                if ($inputEntry['exampleValue'] !== '') {
                    $infoText[] = "Example (right click to use):\n" . filter_var($inputEntry['exampleValue'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $infoTextScript = 'rssbridge_use_placeholder_value(this);';
                }

                if (count($infoText) > 0) {
                    $form .= '<i class="info" data-for="' . $idArg . '" title="' . implode("\n\n", $infoText) . '" oncontextmenu="' . $infoTextScript . 'return false">i</i>';
                } else {
                    $form .= '<i class="no-info"></i>';
                }
            }

            $form .= '</div>';
        }

        if ($isActive) {
            $form .= '<button type="submit" name="format" formtarget="_blank" value="Html">Generate feed</button>';
        } else {
            $form .= '<span style="font-weight: bold;">Inactive</span>';
        }

        return $form . '</form>' . PHP_EOL;
    }

    /**
     * Get input field attributes
     *
     * @param array $entry The current entry
     * @return string The input field attributes
     */
    private static function getInputAttributes($entry)
    {
        $retVal = '';

        if (isset($entry['required']) && $entry['required'] === true) {
            $retVal .= ' required';
        }

        if (isset($entry['pattern'])) {
            $retVal .= ' pattern="' . $entry['pattern'] . '"';
        }

        return $retVal;
    }

    /**
     * Get text input
     *
     * @param array $entry The current entry
     * @param string $id The field ID
     * @param string $name The field name
     * @return string The text input field
     */
    private static function getTextInput($entry, $id, $name)
    {
        return '<input '
        . self::getInputAttributes($entry)
        . ' id="'
        . $id
        . '" type="text" value="'
        . filter_var($entry['defaultValue'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
        . '" placeholder="'
        . filter_var($entry['exampleValue'], FILTER_SANITIZE_FULL_SPECIAL_CHARS)
        . '" name="'
        . $name
        . '" />'
        . PHP_EOL;
    }

    /**
     * Get number input
     *
     * @param array $entry The current entry
     * @param string $id The field ID
     * @param string $name The field name
     * @return string The number input field
     */
    private static function getNumberInput($entry, $id, $name)
    {
        return '<input '
        . self::getInputAttributes($entry)
        . ' id="'
        . $id
        . '" type="number" value="'
        . filter_var($entry['defaultValue'], FILTER_SANITIZE_NUMBER_INT)
        . '" placeholder="'
        . filter_var($entry['exampleValue'], FILTER_SANITIZE_NUMBER_INT)
        . '" name="'
        . $name
        . '" />'
        . PHP_EOL;
    }

    /**
     * Get list input
     *
     * @param array $entry The current entry
     * @param string $id The field ID
     * @param string $name The field name
     * @return string The list input field
     */
    private static function getListInput($entry, $id, $name)
    {
        if (isset($entry['required']) && $entry['required'] === true) {
            Debug::log('The "required" attribute is not supported for lists.');
            unset($entry['required']);
        }

        $list = '<select '
        . self::getInputAttributes($entry)
        . ' id="'
        . $id
        . '" name="'
        . $name
        . '" >';

        foreach ($entry['values'] as $name => $value) {
            if (is_array($value)) {
                $list .= '<optgroup label="' . htmlentities($name) . '">';
                foreach ($value as $subname => $subvalue) {
                    if (
                        $entry['defaultValue'] === $subname
                        || $entry['defaultValue'] === $subvalue
                    ) {
                        $list .= '<option value="'
                            . $subvalue
                            . '" selected>'
                            . $subname
                            . '</option>';
                    } else {
                        $list .= '<option value="'
                            . $subvalue
                            . '">'
                            . $subname
                            . '</option>';
                    }
                }
                $list .= '</optgroup>';
            } else {
                if (
                    $entry['defaultValue'] === $name
                    || $entry['defaultValue'] === $value
                ) {
                    $list .= '<option value="'
                        . $value
                        . '" selected>'
                        . $name
                        . '</option>';
                } else {
                    $list .= '<option value="'
                        . $value
                        . '">'
                        . $name
                        . '</option>';
                }
            }
        }

        $list .= '</select>';

        return $list;
    }

    /**
     * Get checkbox input
     *
     * @param array $entry The current entry
     * @param string $id The field ID
     * @param string $name The field name
     * @return string The checkbox input field
     */
    private static function getCheckboxInput($entry, $id, $name)
    {
        if (isset($entry['required']) && $entry['required'] === true) {
            Debug::log('The "required" attribute is not supported for checkboxes.');
            unset($entry['required']);
        }

        return '<input '
        . self::getInputAttributes($entry)
        . ' id="'
        . $id
        . '" type="checkbox" name="'
        . $name
        . '" '
        . ($entry['defaultValue'] === 'checked' ? 'checked' : '')
        . ' />'
        . PHP_EOL;
    }
}
