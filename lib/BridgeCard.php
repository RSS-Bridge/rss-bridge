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

            <a style="position: absolute; top: 10px; left: 10px" href="#bridge-{$bridgeClassName}">
                <h1>#</h1>
            </a>

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
                    $card .= '<h5>' . $contextName . "</h5>\n";
                }

                $card .= self::renderForm($bridgeClassName, $contextName, $contextParameters, $token);
            }
        }

        $card .= html_tag('label', 'Show less', [
            'class' => 'showless',
            'for'   => "showmore-$bridgeClassName",
        ]) . "\n";

        if (Configuration::getConfig('admin', 'donations') && $bridge->getDonationURI()) {
            $card .= sprintf(
                '<p class="maintainer">%s ~ <a href="%s">Donate</a></p>',
                $bridge->getMaintainer(),
                $bridge->getDonationURI()
            );
        } else {
            $card .= html_tag('p', $bridge->getMaintainer(), ['class' => 'maintainer']) . "\n";
        }
        $card .= "</section>\n\n";

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
            $form .= html_input([
                'type'  => 'hidden',
                'name'  => 'token',
                'value' => $token,
            ]) . "\n";
        }

        if (!empty($contextName)) {
            $form .= html_input([
                'type'  => 'hidden',
                'name'  => 'context',
                'value' => $contextName,
            ]) . "\n";
        }

        if (count($contextParameters) > 0) {
            $form .= '<div class="parameters">' . "\n";

            foreach ($contextParameters as $id => $inputEntry) {
                if (!isset($inputEntry['exampleValue'])) {
                    $inputEntry['exampleValue'] = '';
                }

                if (!isset($inputEntry['defaultValue'])) {
                    $inputEntry['defaultValue'] = $inputEntry['type'] === 'multi-list' ? [] : '';
                }

                $idArg = 'arg-' . urlencode($bridgeClassName) . '-' . urlencode($contextName) . '-' . urlencode($id);

                $form .= html_tag('label', $inputEntry['name'], ['for' => $idArg]) . "\n";

                if (
                    !isset($inputEntry['type'])
                    || $inputEntry['type'] === 'text'
                ) {
                    $form .= self::getTextInput($inputEntry, $idArg, $id) . "\n";
                } elseif ($inputEntry['type'] === 'number') {
                    $form .= self::getNumberInput($inputEntry, $idArg, $id) . "\n";
                } elseif ($inputEntry['type'] === 'list') {
                    $form .= self::getListInput($inputEntry, $idArg, $id) . "\n";
                } elseif ($inputEntry['type'] === 'multi-list') {
                    $form .= self::getListInput($inputEntry, $idArg, $id, true) . "\n";
                } elseif ($inputEntry['type'] === 'checkbox') {
                    $form .= self::getCheckboxInput($inputEntry, $idArg, $id) . "\n";
                } else {
                    $foo = 2;
                    // oops?
                }

                $params = [];
                if (isset($inputEntry['title'])) {
                    $params = [
                        'title' => $inputEntry['title'],
                        'class' => 'info',
                    ];
                }
                if ($inputEntry['exampleValue'] !== '') {
                    $params = [
                        'title'         => sprintf("Example (right click to use):\n%s", $inputEntry['exampleValue']),
                        'class'         => 'info',
                        'oncontextmenu' => 'rssbridge_use_placeholder_value(this);return false',
                        'data-for'      => $idArg,
                    ];
                }

                if ($params) {
                    $form .= html_tag('i', 'i', $params) . "\n";
                } else {
                    $form .= html_tag('i', ' ', ['class' => 'no-info']) . "\n";
                }
            }

            $form .= "</div>\n\n";
        }

        $form .= html_tag('button', 'Generate feed', [
            'type'          => 'submit',
            'name'          => 'format',
            'value'         => 'Html',
            'formtarget'    => '_blank',
        ]) . "\n";

        return $form . "</form>\n\n";
    }

    public static function getTextInput(array $entry, string $id, string $name): string
    {
        $pattern = $entry['pattern'] ?? null;
        $checked = $entry['defaultValue'] === 'checked';
        $required = $entry['required'] ?? false;

        return html_input([
            'id'            => $id,
            'type'          => 'text',
            'value'         => $entry['defaultValue'],
            'placeholder'   => $entry['exampleValue'],
            'name'          => $name,
            'pattern'       => $pattern,
            'checked'       => $checked,
            'required'      => $required,
        ]);
    }

    public static function getNumberInput(array $entry, string $id, string $name): string
    {
        $pattern = $entry['pattern'] ?? null;
        $checked = $entry['defaultValue'] === 'checked';
        $required = $entry['required'] ?? false;

        return html_input([
            'id'            => $id,
            'type'          => 'number',
            'value'         => $entry['defaultValue'],
            'placeholder'   => $entry['exampleValue'],
            'name'          => $name,
            'pattern'       => $pattern,
            'checked'       => $checked,
            'required'      => $required,
        ]);
    }

    public static function getCheckboxInput(array $entry, string $id, string $name): string
    {
        return html_input([
            'id'        => $id,
            'type'      => 'checkbox',
            'name'      => $name,
            'checked'   => $entry['defaultValue'] === 'checked',
        ]);
    }

    public static function getListInput(array $entry, string $id, string $name, bool $isMulti = false): string
    {
        $list = sprintf('<select id="%s" name="%s"%s>', $id, $name . ($isMulti ? '[]' : ''), $isMulti ? ' multiple' : '') . "\n";

        // Cast to array, so scalars become single element arrays - `"default value"` becomes `["default value"]`.
        // Flip, so the values become keys and we can access the values later with O(1) complexity.
        $flip = $isMulti ? array_flip((array)($entry['defaultValue'] ?? [])) : null;

        foreach ($entry['values'] as $name => $value) {
            if (is_array($value)) {
                $list .= '<optgroup label="' . htmlentities($name) . '">';
                foreach ($value as $subname => $subvalue) {
                    $selected = $isMulti ? (isset($flip[$subname]) || isset($flip[$subvalue])) : ($entry['defaultValue'] === $subname || $entry['defaultValue'] === $subvalue);
                    $list .= html_option($subname, $subvalue, $selected) . "\n";
                }
                $list .= '</optgroup>';
            } else {
                $selected = $isMulti ? (isset($flip[$name]) || isset($flip[$value])) : ($entry['defaultValue'] === $name || $entry['defaultValue'] === $value);
                $list .= html_option($name, $value, $selected) . "\n";
            }
        }

        $list .= "</select>\n";

        return $list;
    }
}
