<?php

final class WordPressPluginUpdateBridge extends BridgeAbstract
{
    const MAINTAINER = 'dvikan';
    const NAME = 'WordPress Plugins Update Bridge';
    const URI = 'https://wordpress.org/plugins/';
    const DESCRIPTION = 'Returns latest updates of wordpress.org plugins.';

    const PARAMETERS = [
        [
            // The incorrectly named pluginUrl is kept for BC
            'pluginUrl' => [
                'name' => 'Plugin slug',
                'exampleValue' => 'akismet',
                'required' => true,
                'title' => 'Slug or url',
            ]
        ]
    ];

    public function collectData()
    {
        $input = trim($this->getInput('pluginUrl'));
        if (preg_match('#https://wordpress\.org/plugins/([\w-]+)#', $input, $m)) {
            $slug = $m[1];
        } else {
            $slug = str_replace(['/'], '', $input);
        }

        $pluginData = self::fetchPluginData($slug);

        if ($pluginData->versions === []) {
            throw new \Exception('This plugin does not have versioning data');
        }

        // We don't need trunk. I think it's the latest commit.
        unset($pluginData->versions->trunk);

        foreach ($pluginData->versions as $version => $downloadUrl) {
            $this->items[] = [
                'title'     => $version,
                'uri'       => sprintf('https://wordpress.org/plugins/%s/#developers', $slug),
                'uid'       => $downloadUrl,
            ];
        }

        usort($this->items, function ($a, $b) {
            return version_compare($b['title'], $a['title']);
        });
    }

    /**
     * Fetch plugin data from wordpress.org json api
     *
     * https://codex.wordpress.org/WordPress.org_API#Plugins
     * https://wordpress.org/support/topic/using-the-wordpress-org-api/
     */
    private static function fetchPluginData(string $slug): \stdClass
    {
        $api = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=%s';
        return json_decode(getContents(sprintf($api, $slug)));
    }
}
