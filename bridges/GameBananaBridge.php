<?php

class GameBananaBridge extends BridgeAbstract
{
    const NAME = 'GameBanana';
    const MAINTAINER = 'phantop';
    const URI = 'https://gamebanana.com/';
    const DESCRIPTION = 'Returns mods from GameBanana.';
    const PARAMETERS = [
        'Game' => [
            'gid' => [
                'name' => 'Game ID',
                'required' => true,
                // Example: latest mods from Zelda: Tears of the Kingdom
                'exampleValue' => '7617',
            ],
            'updates' => [
                'name' => 'Get updates',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Enable game updates in feed'
            ],
        ]
    ];

    public function getIcon()
    {
        return 'https://images.gamebanana.com/static/img/favicon/favicon.ico';
    }

    private $title;

    public function collectData()
    {
        $url = 'https://api.gamebanana.com/Core/List/New?itemtype=Mod&page=1&gameid=' . $this->getInput('gid');
        if ($this->getInput('updates')) {
            $url .= '&include_updated=1';
        }
        $api_response = getContents($url);
        $json_list = json_decode($api_response, true); // Get first page mod list

        $url = 'https://api.gamebanana.com/Core/Item/Data?itemtype[]=Game&fields[]=name&itemid[]=' . $this->getInput('gid');
        $fields = 'name,Owner().name,text,screenshots,Files().aFiles(),date,Url().sProfileUrl(),udate,Updates().aLatestUpdates(),Category().name,RootCategory().name';
        foreach ($json_list as $element) { // Build api request to minimize API calls
            $mid = $element[1];
            $url .= '&itemtype[]=Mod&fields[]=' . $fields . '&itemid[]=' . $mid;
        }
        $api_response = getContents($url);
        $json_list = json_decode($api_response, true);

        $this->title = $json_list[0][0];
        array_shift($json_list); // Take title from API request and remove from json

        foreach ($json_list as $element) {
            // Trashed mod IDs are still picked up and return null; skip
            if ($element[0] == null) {
                continue;
            }

            $item = [];
            $item['uri'] = $element[6];
            $item['comments'] = $item['uri'] . '#PostsListModule';
            $item['title'] = $element[0];
            $item['author'] = $element[1];
            $item['categories'][] = $element[9];
            $item['categories'][] = $element[10];

            $item['timestamp'] = $element[5];
            if ($this->getInput('updates')) {
                $item['timestamp'] = $element[7];
            }

            $item['enclosures'] = [];
            foreach ($element[4] as $file) { // Place mod downloads in enclosures
                array_push($item['enclosures'], 'https://files.gamebanana.com/mods/' . $file['_sFile']);
            }

            // Get screenshots from element[3]
            $img_list = json_decode($element[3], true);
            $item['content'] = '';
            foreach ($img_list as $img_element) {
                $item['content'] .= '<img src="https://images.gamebanana.com/img/ss/mods/' . $img_element['_sFile'] . '"/>';
            }

            // Get updates from element[8], if applicable
            if ($this->getInput('updates') && count($element[8]) > 0) {
                $update = $element[8][0];
                $item['content'] .= '<br><strong>Update:</strong> ' . $update['_sTitle'];
                if ($update['_sText'] != '') {
                    $item['content'] .= '<br>' . $update['_sText'];
                }
                foreach ($update['_aChangeLog'] as $change) {
                    if ($change['cat'] == '') {
                        $change['cat'] = 'Change';
                    }
                    $item['content'] .= '<br><em>' . $change['cat'] . '</em>: ' . $change['text'];
                }
                $item['content'] .= '<br><hr>';
            }
            $item['content'] .= '<br>' . $element[2];

            $item['uid'] = $item['uri'] . $item['title'] . $item['timestamp'];
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if (isset($this->title)) {
            $name .= " - $this->title";
        }
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI() . 'games/' . $this->getInput('gid');
        return $uri;
    }
}
