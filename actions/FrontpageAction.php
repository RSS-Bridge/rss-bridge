<?php

final class FrontpageAction implements ActionInterface
{
    private BridgeFactory $bridgeFactory;

    public function __construct(
        BridgeFactory $bridgeFactory
    ) {
        $this->bridgeFactory = $bridgeFactory;
    }

    public function __invoke(Request $request): Response
    {
        $token = $request->getAttribute('token');

        $messages = [];
        $activeBridges = 0;

        $bridgeClassNames = $this->bridgeFactory->getBridgeClassNames();

        foreach ($this->bridgeFactory->getMissingEnabledBridges() as $missingEnabledBridge) {
            $messages[] = [
                'body' => sprintf('Warning : Bridge "%s" not found', $missingEnabledBridge),
                'level' => 'warning'
            ];
        }

        $body = '';
        foreach ($bridgeClassNames as $bridgeClassName) {
            if ($this->bridgeFactory->isEnabled($bridgeClassName)) {
                $bridge = $this->bridgeFactory->create($bridgeClassName);
                $body .= self::render($bridge, $bridgeClassName, $token);
                $activeBridges++;
            }
        }

        $response = new Response(render(__DIR__ . '/../templates/frontpage.html.php', [
            'messages'          => $messages,
            'admin_email'       => Configuration::getConfig('admin', 'email'),
            'admin_telegram'    => Configuration::getConfig('admin', 'telegram'),
            'bridges'           => $body,
            'active_bridges'    => $activeBridges,
            'total_bridges'     => count($bridgeClassNames),
        ]));

        // TODO: The rendered template could be cached, but beware config changes that changes the html
        return $response;
    }

    public static function render(
        BridgeAbstract $bridge,
        string $bridgeClassName,
        ?string $token
    ): string {
        $uri = $bridge->getURI();
        $name = $bridge->getName();
        $icon = $bridge->getIcon();
        $description = $bridge->getDescription();
        $parameters = $bridge->getParameters();

        // Checkbox for disabling of proxy (if enabled)
        if (
            Configuration::getConfig('proxy', 'url')
            && Configuration::getConfig('proxy', 'by_bridge')
        ) {
            $proxyName = Configuration::getConfig('proxy', 'name') ?: Configuration::getConfig('proxy', 'url');
            $parameters['global']['_noproxy'] = [
                'name' => sprintf('Disable proxy (%s)', $proxyName),
                'type' => 'checkbox',
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

            <a style="position: absolute; top: 10px; left: 10px" href="#bridge-{$bridgeClassName}">
                <h1>#</h1>
            </a>

            <h2><a href="{$uri}">{$name}</a></h2>
            <p class="description">{$description}</p>

            <input type="checkbox" class="showmore-box" id="showmore-{$bridgeClassName}" />
            <label class="showmore" for="showmore-{$bridgeClassName}">Show more</label>


        CARD;

        if (count($parameters) === 0) {
            // The bridge has zero parameters
            $card .= self::renderForm($bridgeClassName, '', [], $token);
        } elseif (count($parameters) === 1 && array_key_exists('global', $parameters)) {
            // The bridge has a single context with key 'global'
            $card .= self::renderForm($bridgeClassName, '', $parameters['global'], $token);
        } else {
            // The bridge has one or more contexts (named or unnamed)
            foreach ($parameters as $contextName => $contextParameters) {
                if ($contextName === 'global') {
                    continue;
                }

                if (array_key_exists('global', $parameters)) {
                    // Merge the global parameters into current context
                    $contextParameters = array_merge($contextParameters, $parameters['global']);
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
        array $parameters,
        ?string $token
    ): string {
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

        if (count($parameters) > 0) {
            $form .= '<div class="parameters">' . "\n";

            foreach ($parameters as $id => $parameter) {
                if (!isset($parameter['exampleValue'])) {
                    $parameter['exampleValue'] = '';
                }

                if (!isset($parameter['defaultValue'])) {
                    $parameter['defaultValue'] = ($parameter['type'] ?? null) === 'multi-list' ? [] : '';
                }

                $idArg = 'arg-' . urlencode($bridgeClassName) . '-' . urlencode($contextName) . '-' . urlencode($id);

                $form .= html_tag('label', $parameter['name'], ['for' => $idArg]) . "\n";

                if (
                    !isset($parameter['type'])
                    || $parameter['type'] === 'text'
                ) {
                    $form .= self::getTextInput($parameter, $idArg, $id) . "\n";
                } elseif ($parameter['type'] === 'number') {
                    $form .= self::getNumberInput($parameter, $idArg, $id) . "\n";
                } elseif ($parameter['type'] === 'list') {
                    $form .= self::getListInput($parameter, $idArg, $id) . "\n";
                } elseif ($parameter['type'] === 'multi-list') {
                    $form .= self::getListInput($inputEntry, $idArg, $id, true) . "\n";
                } elseif ($parameter['type'] === 'checkbox') {
                    $form .= self::getCheckboxInput($parameter, $idArg, $id) . "\n";
                } else {
                    $foo = 2;
                    // oops?
                }

                $params = [];
                if (isset($parameter['title'])) {
                    $params = [
                        'title' => $parameter['title'],
                        'class' => 'info',
                    ];
                }
                if ($parameter['exampleValue'] !== '') {
                    $params = [
                        'title'         => sprintf("Example (right click to use):\n%s", $parameter['exampleValue']),
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

    public static function getTextInput(array $parameter, string $id, string $name): string
    {
        $pattern = $parameter['pattern'] ?? null;
        $checked = $parameter['defaultValue'] === 'checked';
        $required = $parameter['required'] ?? false;

        return html_input([
            'id'            => $id,
            'type'          => 'text',
            'value'         => $parameter['defaultValue'],
            'placeholder'   => $parameter['exampleValue'],
            'name'          => $name,
            'pattern'       => $pattern,
            'checked'       => $checked,
            'required'      => $required,
        ]);
    }

    public static function getNumberInput(array $parameter, string $id, string $name): string
    {
        $pattern = $parameter['pattern'] ?? null;
        $checked = $parameter['defaultValue'] === 'checked';
        $required = $parameter['required'] ?? false;

        return html_input([
            'id'            => $id,
            'type'          => 'number',
            'value'         => $parameter['defaultValue'],
            'placeholder'   => $parameter['exampleValue'],
            'name'          => $name,
            'pattern'       => $pattern,
            'checked'       => $checked,
            'required'      => $required,
        ]);
    }

    public static function getCheckboxInput(array $parameter, string $id, string $name): string
    {
        return html_input([
            'id'        => $id,
            'type'      => 'checkbox',
            'name'      => $name,
            'checked'   => $parameter['defaultValue'] === 'checked',
        ]);
    }

    public static function getListInput(array $parameter, string $id, string $name, bool $isMulti = false): string
    {
        $list = sprintf('<select id="%s" name="%s"%s>', $id, $name . ($isMulti ? '[]' : ''), $isMulti ? ' multiple' : '') . "\n";

        if (!empty($parameter['values'])) {
            $default = $parameter['defaultValue'];
            // Cast to array, so scalars become single element arrays - `"default value"` becomes `["default value"]`.
            // Flip, so the values become keys and we can access the values later with O(1) complexity.
            if ($isMulti) {
                $default = array_flip((array)($default));
            }

            foreach ($parameter['values'] as $name => $value) {
                if (is_array($value)) {
                    $list .= '<optgroup label="' . htmlentities($name) . '">';
                    foreach ($value as $subname => $subvalue) {
                        if ($isMulti) {
                            $selected = isset($default[$subname]) || isset($default[$subvalue]);
                        } else {
                            $selected = $default === $subname || $default === $subvalue;
                        }
                        $list .= html_option($subname, $subvalue, $selected) . "\n";
                    }
                    $list .= '</optgroup>';
                } else {
                    if ($isMulti) {
                        $selected = isset($default[$name]) || isset($default[$value]);
                    } else {
                        $selected = $default === $name || $default === $value;
                    }
                    $list .= html_option($name, $value, $selected) . "\n";
                }
            }
        }

        $list .= "</select>\n";

        return $list;
    }
}
