<?php

class TorrentGalaxyBridge extends BridgeAbstract
{
    const NAME = 'Torrent Galaxy Bridge';
    const URI = 'https://torrentgalaxy.to';
    const DESCRIPTION = 'Returns latest torrents';
    const MAINTAINER = 'GregThib';
    const CACHE_TIMEOUT = 14400; // 24h = 86400s

    const PARAMETERS = [
        [
            'search' => [
                'name' => 'search',
                'required' => true,
                'exampleValue' => 'simpsons',
                'title' => 'Type your query'
            ],
            'lang' => [
                'name' => 'language',
                'type' => 'list',
                'exampleValue' => 'All languages',
                'title' => 'Select your language',
                'values' => [
                    'All languages' => '0',
                    'English' => '1',
                    'French' => '2',
                    'German' => '3',
                    'Italian' => '4',
                    'Japanese' => '5',
                    'Spanish' => '6',
                    'Russian' => '7',
                    'Hindi' => '8',
                    'Other / Multiple' => '9',
                    'Korean' => '10',
                    'Danish' => '11',
                    'Norwegian' => '12',
                    'Dutch' => '13',
                    'Manderin' => '14',
                    'Portuguese' => '15',
                    'Bengali' => '16',
                    'Polish' => '17',
                    'Turkish' => '18',
                    'Telugu' => '19',
                    'Urdu' => '20',
                    'Arabic' => '21',
                    'Swedish' => '22',
                    'Romanian' => '23'
                ]
            ]
        ]
    ];

    public function collectData()
    {
        $url = self::URI
            . '/torrents.php?search=' . urlencode($this->getInput('search'))
            . '&lang=' . $this->getInput('lang')
            . '&sort=id&order=desc';
        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('div.tgxtablerow') as $result) {
            $identity = $result->find('div.tgxtablecell', 3)->find('div a', 0);
            $authorid = $result->find('div.tgxtablecell', 6)->find('a', 0);
            $creadate = $result->find('div.tgxtablecell', 11)->plaintext;
            $glxlinks = $result->find('div.tgxtablecell', 4);

            $item = [];
            $item['uri'] = self::URI . $identity->href;
            $item['title'] = $identity->plaintext;

            // todo: parse date strings such as '1Hr ago' etc.
            $createdAt = DateTime::createFromFormat('d/m/y H:i', $creadate);
            if ($createdAt) {
                $item['timestamp'] = $createdAt->format('U');
            }

            $item['author'] = $authorid->plaintext;
            $item['content'] = <<<HTML
<h1>{$identity->plaintext}</h1>
<h2>Links</h2>
<p><a href="{$glxlinks->find('a', 1)->href}" title="magnet link">magnet</a></p>
<p><a href="{$glxlinks->find('a', 0)->href}" title="torrent link">torrent</a></p>
<h2>Infos</h2>
<p>Size: {$result->find('div.tgxtablecell', 7)->plaintext}</p>
<p>Added by: <a href="{$authorid->href}" title="author profile">{$authorid->plaintext}</a></p>
<p>Upload time: {$creadate}</p>
HTML;
            $item['enclosures'] = [$glxlinks->find('a', 0)->href];
            $item['categories'] = [$result->find('div.tgxtablecell', 0)->plaintext];
            if (preg_match('#/torrent/([^/]+)/#', self::URI . $identity->href, $torrentid)) {
                $item['uid'] = $torrentid[1];
            }
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('search'))) {
            return $this->getInput('search') . ' : ' . self::NAME;
        }
        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('search'))) {
            return self::URI
                . '/torrents.php?search=' . urlencode($this->getInput('search'))
                . '&lang=' . $this->getInput('lang');
        }
        return parent::getURI();
    }

    public function getDescription()
    {
        if (!is_null($this->getInput('search'))) {
            return 'Latest torrents for "' . $this->getInput('search') . '"';
        }
        return parent::getDescription();
    }

    public function getIcon()
    {
        if (!is_null($this->getInput('search'))) {
            return self::URI . '/common/favicon/favicon.ico';
        }
        return parent::getIcon();
    }
}
