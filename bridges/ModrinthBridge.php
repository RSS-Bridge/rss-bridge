<?php

declare(strict_types=1);

// Uses the modrinth API documented here: https://docs.modrinth.com/api/

class ModrinthBridge extends BridgeAbstract
{
    const NAME = 'Modrinth';
    const URI = 'https://modrinth.com/';
    const DESCRIPTION = 'For new versions of mods, resource packs, etc.';
    const MAINTAINER = 'xnand';

    const PARAMETERS = [[
        'name' => [
            'name' => 'Name',
            'required' => true,
            'title' => 'The project name as seen in the URL bar',
            'exampleValue' => 'sodium'
        ],
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'Mod' => 'mod',
                'Resource Pack' => 'resourcepack',
                'Data Pack' => 'datapack',
                'Shader' => 'shader',
                'Modpack' => 'modpack',
                'Plugin' => 'plugin'
            ],
            'defaultValue' => 'mod'
        ],
        'loaders' => [
            'name' => 'Loaders',
            'title' => 'List of mod loaders, separated by commas',
            'exampleValue' => 'neoforge, fabric'
        ],
        'game_versions' => [
            'name' => 'Game versions',
            'title' => 'List of game versions, separated by commas',
            'exampleValue' => '1.19.1, 1.19.2'
        ],
        'featured' => [
            'name' => 'Featured',
            'type' => 'list',
            'values' => [
                'Unset' => '',
                'True' => 'true',
                'False' => 'false'
            ],
            'title' => "Whether to filter for featured or non-featured\nUnset means no filter",
            'defaultValue', ''
        ]
    ]];


    public function getURI()
    {
        $name = $this->getInput('name');
        $category = $this->getInput('category');
        $uri = self::URI . $category . '/' . $name . '/versions';
        if (empty($name)) {
            $uri = parent::getURI();
        }
        return $uri;
    }

    public function getName()
    {
        $name = $this->getInput('name');
        if (empty($name)) {
            $name = parent::getName();
        }
        return $name;
    }

    public function collectData()
    {
        $apiUrl = 'https://api.modrinth.com/v2/project';
        $projectName = $this->getInput('name');
        $url = sprintf('%s/%s/version', $apiUrl, $projectName);

        $queryTable = [
            'loaders' => $this->parseInputList($this->getInput('loaders')),
            'game_versions' => $this->parseInputList($this->getInput('game_versions')),
            'featured' => ($this->getInput('featured')) ? : null
        ];

        $query = http_build_query($queryTable);
        if ($query) {
            $url .= '?' . $query;
        }

        // They expect a descriptive user agent and may block connections without one
        // Change as appropriate
        // https://docs.modrinth.com/api/#user-agents
        $header = [ 'User-Agent: rss-bridge plugin https://github.com/RSS-Bridge/rss-bridge' ];
        $data = json_decode(getContents($url, $header));

        foreach ($data as $entry) {
            $item = [];

            $item['uri'] = self::URI . $this->getInput('category') . '/' . $this->getInput('name') . '/version/' . $entry->version_number;
            $item['title'] = $entry->name;
            $item['timestamp'] = $entry->date_published;
            // Not setting the author as this would take a second request to match the author's user ID
            $item['author'] = 'Modrinth';
            $item['content'] = markdownToHtml($entry->changelog);
            $item['categories'] = array_merge($entry->loaders, $entry->game_versions);
            $item['uid'] = $entry->id;

            $this->items[] = $item;
        }
    }

    // Converts lists like `foo, bar, baz` to `["foo", "bar", "baz"]`
    protected function parseInputList($input): ?string
    {
        if (empty($input)) {
            return null;
        }
        $items = array_filter(array_map('trim', explode(',', $input)));
        return $items ? json_encode($items) : null; // return nothing if string is empty
    }
}
