<?php

final class BridgeCard
{
    public static function render(
        string $bridgeClassName,
        Request $request,
        bool $setValuesFromQuery = false
    ): string
    {
        $bridgeFactory = new BridgeFactory();

        $bridge = $bridgeFactory->create($bridgeClassName);

        $uri = $bridge->getURI();
        $name = $bridge->getName();
        $icon = $bridge->getIcon();
        $description = $bridge->getDescription();
        $contexts = $bridge->getParameters();

        if (Configuration::getConfig('proxy', 'url') && Configuration::getConfig('proxy', 'by_bridge')) {
            $contexts['global']['_noproxy'] = [
                'name' => 'Disable proxy (' . (Configuration::getConfig('proxy', 'name') ?: Configuration::getConfig('proxy', 'url')) . ')',
                'type' => 'checkbox'
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

        $token = $request->attribute('token');

        $formRequest = $setValuesFromQuery ? $request : null;

        if (count($contexts) === 0) {
            // The bridge has zero parameters
            $card .= self::renderForm($bridgeClassName, '', [], $token, $formRequest);
        } elseif (count($contexts) === 1 && array_key_exists('global', $contexts)) {
            // The bridge has a single context with key 'global'
            $card .= self::renderForm($bridgeClassName, '', $contexts['global'], $token, $formRequest);
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

                $card .= self::renderForm($bridgeClassName, $contextName, $contextParameters, $token, $formRequest);
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
        ?string $token,
        ?Request $request
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
                    $form .= self::getTextInput($inputEntry, $idArg, $id, $request) . "\n";
                } elseif ($inputEntry['type'] === 'number') {
                    $form .= self::getNumberInput($inputEntry, $idArg, $id, $request);
                } elseif ($inputEntry['type'] === 'list') {
                    $form .= self::getListInput($inputEntry, $idArg, $id, $request) . "\n";
                } elseif ($inputEntry['type'] === 'checkbox') {
                    $form .= self::getCheckboxInput($inputEntry, $idArg, $id, $request);
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

    public static function getTextInput(array $entry, string $id, string $name, ?Request $request): string
    {
        if ($request === null) {
            $defaultValue = filter_var($entry['defaultValue'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        } else {
            $defaultValue = $request->get($name);
        }
        $exampleValue = filter_var($entry['exampleValue'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $attributes = self::getInputAttributes($entry);

        return sprintf('<input %s id="%s" type="text" value="%s" placeholder="%s" name="%s" />', $attributes, $id, $defaultValue, $exampleValue, $name);
    }

    public static function getNumberInput(array $entry, string $id, string $name, ?Request $request): string
    {
        if ($request === null) {
            $defaultValue = filter_var($entry['defaultValue'], FILTER_SANITIZE_NUMBER_INT);
        } else {
            $defaultValue = $request->get($name);
        }
        $exampleValue = filter_var($entry['exampleValue'], FILTER_SANITIZE_NUMBER_INT);
        $attributes = self::getInputAttributes($entry);

        return sprintf('<input %s id="%s" type="number" value="%s" placeholder="%s" name="%s" />' . "\n", $attributes, $id, $defaultValue, $exampleValue, $name);
    }

    public static function getListInput(array $entry, string $id, string $name, ?Request $request): string
    {
        $required = $entry['required'] ?? null;
        if ($required) {
            Debug::log('The "required" attribute is not supported for lists.');
            unset($entry['required']);
        }

        if ($request === null) {
            $defaultValue = $entry['defaultValue'];
        } else {
            $defaultValue = $request->get($name);
        }
        $attributes = self::getInputAttributes($entry);
        $list = sprintf('<select %s id="%s" name="%s" >' . "\n", $attributes, $id, $name);

        foreach ($entry['values'] as $name => $value) {
            if (is_array($value)) {
                $list .= '<optgroup label="' . htmlentities($name) . '">';
                foreach ($value as $subname => $subvalue) {
                    if (
                        $defaultValue === $subname
                        || $defaultValue === $subvalue
                    ) {
                        $list .= '<option value="' . $subvalue . '" selected>' . $subname . '</option>';
                    } else {
                        $list .= '<option value="' . $subvalue . '">' . $subname . '</option>';
                    }
                }
                $list .= '</optgroup>';
            } else {
                if (
                    $defaultValue === $name
                    || $defaultValue === $value
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


    public static function getCheckboxInput(array $entry, string $id, string $name, ?Request $request): string
    {
        $required = $entry['required'] ?? null;
        if ($required) {
            Debug::log('The "required" attribute is not supported for checkboxes.');
            unset($entry['required']);
        }
        if ($request === null) {
            $checked = $entry['defaultValue'] === 'checked' ? 'checked' : '';
        } else {
            $checked = $request->get($name) === 'on' ? 'checked' : '';
        }
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
