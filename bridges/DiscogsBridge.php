<?php

class DiscogsBridge extends BridgeAbstract
{
    const MAINTAINER = 'teromene';
    const NAME = 'DiscogsBridge';
    const URI = 'https://www.discogs.com/';
    const DESCRIPTION = 'Returns releases from discogs';
    const PARAMETERS = [
        'Artist Releases' => [
            'artistid' => [
                'name' => 'Artist ID',
                'type' => 'number',
                'required' => true,
                'exampleValue' => '28104',
                'title' => 'Only the ID from an artist page. EG /artist/28104-Aesop-Rock is 28104'
            ],
            'image' => [
                'name' => 'Include Image',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
                'title' => 'Whether to include image (if bridge is configured with a personal access token)',
            ]
        ],
        'Label Releases' => [
            'labelid' => [
                'name' => 'Label ID',
                'type' => 'number',
                'required' => true,
                'exampleValue' => '8201',
                'title' => 'Only the ID from a label page. EG /label/8201-Rhymesayers-Entertainment is 8201'
            ],
            'image' => [
                'name' => 'Include Image',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
                'title' => 'Whether to include image (if bridge is configured with a personal access token)',
            ]
        ],
        'User Wantlist' => [
            'username_wantlist' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'TheBlindMaster',
            ],
            'image' => [
                'name' => 'Include Image',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
                'title' => 'Whether to include image (if bridge is configured with a personal access token)',
            ]
        ],
        'User Folder' => [
            'username_folder' => [
                'name' => 'Username',
                'type' => 'text',
            ],
            'folderid' => [
                'name' => 'Folder ID',
                'type' => 'number',
            ],
            'image' => [
                'name' => 'Include Image',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
                'title' => 'Whether to include image (if bridge is configured with a personal access token)',
            ]
        ],
    ];
    const CONFIGURATION = [
        /**
         * When a personal access token is provided, Discogs' API will
         * return images as part of artist and label information.
         *
         * @see https://www.discogs.com/settings/developers
         */
        'personal_access_token' => [
            'required' => false,
        ],
    ];

    public function collectData()
    {
        $headers = [];

        if ($this->getOption('personal_access_token')) {
            $headers = ['Authorization: Discogs token=' . $this->getOption('personal_access_token')];
        }

        if (!empty($this->getInput('artistid')) || !empty($this->getInput('labelid'))) {
            if (!empty($this->getInput('artistid'))) {
                $url = 'https://api.discogs.com/artists/'
                . $this->getInput('artistid')
                . '/releases?sort=year&sort_order=desc';
                $data = getContents($url, $headers);
            } elseif (!empty($this->getInput('labelid'))) {
                $url = 'https://api.discogs.com/labels/'
                . $this->getInput('labelid')
                . '/releases?sort=year&sort_order=desc';
                $data = getContents($url, $headers);
            }

            $jsonData = json_decode($data, true);

            foreach ($jsonData['releases'] as $release) {
                $item = [];
                $item['author'] = $release['artist'];
                $item['title'] = $release['title'];
                $item['id'] = $release['id'];
                $resId = array_key_exists('main_release', $release) ? $release['main_release'] : $release['id'];
                $item['uri'] = self::URI . $this->getInput('artistid') . '/release/' . $resId;

                if (isset($release['year'])) {
                    $item['timestamp'] = DateTime::createFromFormat('Y', $release['year'])->getTimestamp();
                }

                $item['content'] = $item['author'] . ' - ' . $item['title'];

                if (isset($release['thumb']) && $this->getInput('image') === true) {
                    $item['content'] = sprintf(
                        '<img src="%s"/><br/><br/>%s',
                        $release['thumb'],
                        $item['content'],
                    );
                }

                $this->items[] = $item;
            }
        } elseif (!empty($this->getInput('username_wantlist')) || !empty($this->getInput('username_folder'))) {
            if (!empty($this->getInput('username_wantlist'))) {
                $url = 'https://api.discogs.com/users/'
                . $this->getInput('username_wantlist')
                . '/wants?sort=added&sort_order=desc';
                $data = getContents($url, $headers);
                $jsonData = json_decode($data, true)['wants'];
            } elseif (!empty($this->getInput('username_folder'))) {
                $url = 'https://api.discogs.com/users/'
                . $this->getInput('username_folder')
                . '/collection/folders/'
                . $this->getInput('folderid')
                . '/releases?sort=added&sort_order=desc';
                $data = getContents($url, $headers);
                $jsonData = json_decode($data, true)['releases'];
            }
            foreach ($jsonData as $element) {
                $infos = $element['basic_information'];
                $item = [];
                $item['title'] = $infos['title'];
                $item['author'] = $infos['artists'][0]['name'];
                $item['id'] = $infos['artists'][0]['id'];
                $item['uri'] = self::URI . $infos['artists'][0]['id'] . '/release/' . $infos['id'];
                $item['timestamp'] = strtotime($element['date_added']);
                $item['content'] = $item['author'] . ' - ' . $item['title'];

                if (isset($infos['thumb']) && $this->getInput('image') === true) {
                    $item['content'] = sprintf(
                        '<img src="%s"/><br/><br/>%s',
                        $infos['thumb'],
                        $item['content'],
                    );
                }

                $this->items[] = $item;
            }
        }
    }

    public function getURI()
    {
        return self::URI;
    }

    public function getName()
    {
        return static::NAME;
    }
}
