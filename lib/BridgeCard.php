<?php

final class BridgeCard
{
    public static function render(
        BridgeFactory $bridgeFactory,
        string $bridgeClassName,
        ?string $token
    ): string {
        $bridge = $bridgeFactory->create($bridgeClassName);

        $uri = $bridge->getURI();
        $name = $bridge->getName();
        $icon = $bridge->getIcon();
        $description = $bridge->getDescription();
        $contexts = $bridge->getParameters();

        // Checkbox for disabling of proxy (if enabled)
        if (
            Configuration::getConfig('proxy', 'url')
            && Configuration::getConfig('proxy', 'by_bridge')
        ) {
            $proxyName = Configuration::getConfig('proxy', 'name') ?: Configuration::getConfig('proxy', 'url');
            $contexts['global']['_noproxy'] = [
                'name' => sprintf('Disable proxy (%s)', $proxyName),
                'type' => 'checkbox',
            ];
        }

        if (Configuration::getConfig('cache', 'custom_timeout')) {
            $contexts['global']['_cache_timeout'] = [
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

        if (count($contexts) === 0) {
            // The bridge has zero parameters
            $card .= self::renderForm($bridgeClassName, '', [], $token);
        } elseif (count($contexts) === 1 && array_key_exists('global', $contexts)) {
            // The bridge has a single context with key 'global'
            $card .= self::renderForm($bridgeClassName, '', $contexts['global'], $token);
        } else {
            // The bridge has one or more contexts (named or unnamed)
            foreach ($contexts as $contextName => $contextParameters) {
                if ($contextName === 'global') {
                    continue;
                }

                if (array_key_exists('global', $contexts)) {
                    // Merge the global parameters into current context
                    $contextParameters = array_merge($contextParameters, $contexts['global']);
                }

                if (!is_numeric($contextName)) {
                    // This is a named context
                    $card .= '<h5>' . $contextName . '</h5>' . PHP_EOL;
                }

                $card .= self::renderForm($bridgeClassName, $contextName, $contextParameters, $token);
            }
        }

        $card .= sprintf('<label class="showless" for="showmore-%s">Show less</label>', $bridgeClassName);

        if (Configuration::getConfig('admin', 'donations') && $bridge->getDonationURI()) {
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

    private static function renderForm(
        string $bridgeClassName,
        string $contextName,
        array $contextParameters,
        ?string $token
    ) {
        $form = <<<EOD
        <form method="GET" action="?" class="bridge-form">
            <input type="hidden" name="action" value="display" />
            <input type="hidden" name="bridge" value="{$bridgeClassName}" />
        EOD;

        if (Configuration::getConfig('authentication', 'token') && $token) {
            $form .= sprintf('<input type="hidden" name="token" value="%s" />', e($token));
        }

        if (!empty($contextName)) {
            $form .= sprintf('<input type="hidden" name="context" value="%s" />', $contextName);
        }

        if (count($contextParameters) > 0) {
            $form .= '<div class="parameters">';

            foreach ($contextParameters as $id => $inputEntry) {
                if (!isset($inputEntry['exampleValue'])) {
                    $inputEntry['exampleValue'] = '';
                }

                if (!isset($inputEntry['defaultValue'])) {
                    $inputEntry['defaultValue'] = '';
                }

                $idArg = 'arg-' . urlencode($bridgeClassName) . '-' . urlencode($contextName) . '-' . urlencode($id);

                $inputName = filter_var($inputEntry['name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $form .= '<label for="' . $idArg . '">' . $inputName . '</label>' . PHP_EOL;

                if (
                    !isset($inputEntry['type'])
                    || $inputEntry['type'] === 'text'
                ) {
                    $form .= self::getTextInput($inputEntry, $idArg, $id) . "\n";
                } elseif ($inputEntry['type'] === 'number') {
                    $form .= self::getNumberInput($inputEntry, $idArg, $id);
                } elseif ($inputEntry['type'] === 'list') {
                    $form .= self::getListInput($inputEntry, $idArg, $id) . "\n";
                } elseif ($inputEntry['type'] === 'dynamic_list') {
                    $form .= self::getDynamicListInput($inputEntry, $idArg, $id) . "\n";
                } elseif ($inputEntry['type'] === 'checkbox') {
                    $form .= self::getCheckboxInput($inputEntry, $idArg, $id);
                } else {
                    $foo = 2;
                    // oops?
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

        $form .= '<button type="submit" name="format" formtarget="_blank" value="Html">Generate feed</button>';

        return $form . '</form>' . PHP_EOL;
    }

    public static function getTextInput(array $entry, string $id, string $name): string
    {
        $defaultValue = filter_var($entry['defaultValue'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $exampleValue = filter_var($entry['exampleValue'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $attributes = self::getInputAttributes($entry);

        return sprintf('<input %s id="%s" type="text" value="%s" placeholder="%s" name="%s" />', $attributes, $id, $defaultValue, $exampleValue, $name);
    }

    public static function getNumberInput(array $entry, string $id, string $name): string
    {
        $defaultValue = filter_var($entry['defaultValue'], FILTER_SANITIZE_NUMBER_INT);
        $exampleValue = filter_var($entry['exampleValue'], FILTER_SANITIZE_NUMBER_INT);
        $attributes = self::getInputAttributes($entry);

        return sprintf('<input %s id="%s" type="number" value="%s" placeholder="%s" name="%s" />' . "\n", $attributes, $id, $defaultValue, $exampleValue, $name);
    }

    public static function getListInput(array $entry, string $id, string $name): string
    {
        $required = $entry['required'] ?? null;
        if ($required) {
            trigger_error('The required attribute is not supported for lists');
            unset($entry['required']);
        }

        $attributes = self::getInputAttributes($entry);
        $list = sprintf('<select %s id="%s" name="%s" >' . "\n", $attributes, $id, $name);

        foreach ($entry['values'] as $name => $value) {
            if (is_array($value)) {
                $list .= '<optgroup label="' . htmlentities($name) . '">';
                foreach ($value as $subname => $subvalue) {
                    if (
                        $entry['defaultValue'] === $subname
                        || $entry['defaultValue'] === $subvalue
                    ) {
                        $list .= '<option value="' . $subvalue . '" selected>' . $subname . '</option>';
                    } else {
                        $list .= '<option value="' . $subvalue . '">' . $subname . '</option>';
                    }
                }
                $list .= '</optgroup>';
            } else {
                if (
                    $entry['defaultValue'] === $name
                    || $entry['defaultValue'] === $value
                ) {
                    $list .= '<option value="' . $value . '" selected>' . $name . '</option>' . "\n";
                } else {
                    $list .= '<option value="' . $value . '">' . $name . '</option>' . "\n";
                }
            }
        }

        $list .= '</select>';

        return $list;
    }

    public static function getDynamicListInput(array $entry, string $id, string $name): string
    {
        $required = $entry['required'] ?? null;
        if ($required) {
            trigger_error('The required attribute is not supported for lists');
            unset($entry['required']);
        }

        if (!isset($entry['ajax_route']) || !isset($entry['fields_name_used_as_value']) || !isset($entry['fields_name_used_for_display'])) {
            trigger_error('The ajax_route and fields_name_used_as_value and fields_name_used_for_display attributes are required');
        }

        $attributes = self::getInputAttributes($entry);

        $fieldsDisplayString = '';
        foreach ($entry['fields_name_used_for_display'] as $index => $field) {
            if ($index === 0) {
                $fieldsDisplayString = 'option.' . $field;
            } else {
                $fieldsDisplayString .= ' + \' - \' + option.' . $field;
            }
        }

        $fieldsValueString = '';
        $fieldsNameUsedAsValueSeparator = isset($entry['fields_name_used_as_value_separator']) ? $entry['fields_name_used_as_value_separator'] : '-';
        foreach ($entry['fields_name_used_as_value'] as $index => $field) {
            if ($index === 0) {
                $fieldsValueString = 'option.' . $field;
            } else {
                $fieldsValueString .= ' + \'' . $fieldsNameUsedAsValueSeparator . '\' + option.' . $field;
            }
        }

        $list = sprintf(
            '<input %s id="input-%s" name="%s" autocomplete="off" type="text" list="options-%s" onmousedown="
                const id = \'%s\';
                const inputElement = document.getElementById(\'input-\' + id);
                const errorElement = document.getElementById(\'error-\' + id);
                const datalist = document.getElementById(\'options-\' + id);
                const options = document.getElementById(\'options-\' + id).options;

                let hasError = errorElement.innerHTML === \'\' ? false : true;
                if (!hasError && (!options || options.length === 0)) {
                    // set himself disabled while the request is progressing
                    inputElement.disabled = true;
                    inputElement.value = \'loading...\';
                    // used to not be blocked by cors
                    const proxy = \'%s\';

                    // Perform AJAX request
                    const xhr = new XMLHttpRequest();

                    xhr.open(\'GET\', proxy + \'%s\', true);
                    xhr.onerror = function () {
                        errorElement.innerHTML = \'failed fetching data\';
                        inputElement.value = \'\';
                        inputElement.disabled = false;
                        hasError = true;
                    }
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            // Parse the response and update the datalist
                            datalist.innerHTML = \'\'; // Clear existing options
                            let options = JSON.parse(xhr.responseText); // Assuming JSON response
                            if (%s) {
                                options = options[\'%s\'];
                            }
                            options.forEach(option => {
                                const opt = document.createElement(\'option\');

                                opt.innerHTML = %s; // the displayed value
                                opt.value = %s; // the value
                                datalist.appendChild(opt);
                            });
                            // set himself enabled when the request is done
                            inputElement.value = \'\';
                            inputElement.disabled = false;
                        } else {
                            errorElement.innerHTML = \'failed fetching data\';
                            inputElement.value = \'\';
                            inputElement.disabled = false;
                        }
                    };
                    xhr.send();
                }
            " />' . "\n",
            $attributes,
            $id,
            $name,
            $id,
            $id,
            Configuration::getConfig('proxy', 'url') ?: 'https://cors-anywhere.herokuapp.com/',
            $entry['ajax_route'],
            isset($entry['field_for_options']) ? 'true' : 'false',
            isset($entry['field_for_options']) ? $entry['field_for_options'] : null,
            $fieldsDisplayString,
            $fieldsValueString,
        );
        $list .= sprintf('<datalist id="options-%s"></datalist>' . "\n", $id);
        $list .= sprintf('<br><div id="error-%s" style="color: red; height: 28px"></div>' . "\n", $id);

        return $list;
    }

    public static function getCheckboxInput(array $entry, string $id, string $name): string
    {
        $required = $entry['required'] ?? null;
        if ($required) {
            trigger_error('The required attribute is not supported for checkboxes');
            unset($entry['required']);
        }

        $checked = $entry['defaultValue'] === 'checked' ? 'checked' : '';
        $attributes = self::getInputAttributes($entry);

        return sprintf('<input %s id="%s" type="checkbox" name="%s" %s />' . "\n", $attributes, $id, $name, $checked);
    }

    public static function getInputAttributes(array $entry): string
    {
        $result = '';

        $required = $entry['required'] ?? null;
        if ($required) {
            $result .= ' required';
        }

        $pattern = $entry['pattern'] ?? null;
        if ($pattern) {
            $result .= ' pattern="' . $pattern . '"';
        }

        return $result;
    }
}
