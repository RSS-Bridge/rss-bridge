<?php

class PixivBridge extends BridgeAbstract
{
    // Good resource on API return values (Ex: illustType):
    // https://hackage.haskell.org/package/pixiv-0.1.0/docs/Web-Pixiv-Types.html
    const NAME = 'Pixiv Bridge';
    const URI = 'https://www.pixiv.net/';
    const DESCRIPTION = 'Returns the tag search from pixiv.net';


    const PARAMETERS = [
        'global' => [
            'posts' => [
                'name' => 'Post Limit',
                'type' => 'number',
                'defaultValue' => '10'
            ],
            'fullsize' => [
                'name' => 'Full-size Image',
                'type' => 'checkbox'
            ],
            'mode' => [
                'name' => 'Post Type',
                'type' => 'list',
                'values' => ['All Works' => 'all',
                                  'Illustrations' => 'illustrations/',
                                  'Manga' => 'manga/',
                                  'Novels' => 'novels/']
            ],
        ],
        'Tag' => [
            'tag' => [
                'name' => 'Query to search',
                'exampleValue' => 'オリジナル',
                'required' => true
            ]
        ],
        'User' => [
            'userid' => [
                'name' => 'User ID from profile URL',
                'exampleValue' => '11',
                'required' => true
            ]
        ]
    ];

    // maps from URLs to json keys by context
    const JSON_KEY_MAP = [
        'Tag' => [
            'illustrations/' => 'illust',
            'manga/' => 'manga',
            'novels/' => 'novel'
        ],
        'User' => [
            'illustrations/' => 'illusts',
            'manga/' => 'manga',
            'novels/' => 'novels'
        ]
    ];

    // Hold the username for getName()
    private $username = null;

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Tag':
                $context = 'Tag';
                $query = $this->getInput('tag');
                break;
            case 'User':
                $context = 'User';
                $query = $this->username ?? $this->getInput('userid');
                break;
            default:
                return parent::getName();
        }
        $mode = array_search(
            $this->getInput('mode'),
            self::PARAMETERS['global']['mode']['values']
        );
        return "Pixiv ${mode} from ${context} ${query}";
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Tag':
                $uri = static::URI . 'tags/' . urlencode($this->getInput('tag') ?? '');
                break;
            case 'User':
                $uri = static::URI . 'users/' . $this->getInput('userid');
                break;
            default:
                return parent::getURI();
        }
        if ($this->getInput('mode') != 'all') {
            $uri = $uri . '/' . $this->getInput('mode');
        }
        return $uri;
    }

    private function getSearchURI($mode)
    {
        switch ($this->queriedContext) {
            case 'Tag':
                $query = urlencode($this->getInput('tag'));
                $uri = static::URI . 'ajax/search/top/' . $query;
                break;
            case 'User':
                $uri = static::URI . 'ajax/user/' . $this->getInput('userid')
                 . '/profile/top';
                break;
            default:
                returnClientError('Invalid Context');
        }
        return $uri;
    }

    private function getDataFromJSON($json, $json_key)
    {
        $json = $json['body'][$json_key];
        // Tags context contains subkey
        if ($this->queriedContext == 'Tag') {
            $json = $json['data'];
        }
        return $json;
    }

    private function collectWorksArray()
    {
        $content = getContents($this->getSearchURI($this->getInput('mode')));
        $content = json_decode($content, true);
        if ($this->getInput('mode') == 'all') {
            $total = [];
            foreach (self::JSON_KEY_MAP[$this->queriedContext] as $mode => $json_key) {
                $current = $this->getDataFromJSON($content, $json_key);
                $total = array_merge($total, $current);
            }
            $content = $total;
        } else {
            $json_key = self::JSON_KEY_MAP[$this->queriedContext][$this->getInput('mode')];
            $content = $this->getDataFromJSON($content, $json_key);
        }
        return $content;
    }

    public function collectData()
    {
        $content = $this->collectWorksArray();

        $content = array_filter($content, function ($v, $k) {
            return !array_key_exists('isAdContainer', $v);
        }, ARRAY_FILTER_USE_BOTH);
        // Sort by updateDate to get newest works
        usort($content, function ($a, $b) {
            return $b['updateDate'] <=> $a['updateDate'];
        });
        $content = array_slice($content, 0, $this->getInput('posts'));

        foreach ($content as $result) {
            // Store username for getName()
            if (!$this->username) {
                $this->username = $result['userName'];
            }

            $item = [];
            $item['uid'] = $result['id'];
            $subpath = array_key_exists('illustType', $result) ? 'artworks/' : 'novel/show.php?id=';
            $item['uri'] = static::URI . $subpath . $result['id'];
            $item['title'] = $result['title'];
            $item['author'] = $result['userName'];
            $item['timestamp'] = $result['updateDate'];
            $item['categories'] = $result['tags'];
            $cached_image = $this->cacheImage(
                $result['url'],
                $result['id'],
                array_key_exists('illustType', $result)
            );
            $item['content'] = "<img src='" . $cached_image . "' />";

            // Additional content items
            if (array_key_exists('pageCount', $result)) {
                $item['content'] .= '<br>Page Count: ' . $result['pageCount'];
            } else {
                $item['content'] .= '<br>Word Count: ' . $result['wordCount'];
            }

            $this->items[] = $item;
        }
    }

    private function cacheImage($url, $illustId, $isImage)
    {
        $illustId = preg_replace('/[^0-9]/', '', $illustId);
        $thumbnailurl = $url;

        $path = PATH_CACHE . 'pixiv_img/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $path .= $illustId;
        if ($this->getInput('fullsize')) {
            $path .= '_fullsize';
        }
        $path .= '.jpg';

        if (!is_file($path)) {
            // Get fullsize URL
            if ($isImage && $this->getInput('fullsize')) {
                $ajax_uri = static::URI . 'ajax/illust/' . $illustId;
                $imagejson = json_decode(getContents($ajax_uri), true);
                $url = $imagejson['body']['urls']['original'];
            }

            $headers = ['Referer: ' . static::URI];
            try {
                $illust = getContents($url, $headers);
            } catch (Exception $e) {
                $illust = getContents($thumbnailurl, $headers); // Original thumbnail
            }
            file_put_contents($path, $illust);
        }

        return get_home_page_url() . 'cache/pixiv_img/' . preg_replace('/.*\//', '', $path);
    }
}
