<?php

/**
 * Good resource on API return values (Ex: illustType):
 * https://hackage.haskell.org/package/pixiv-0.1.0/docs/Web-Pixiv-Types.html
 */
class PixivBridge extends BridgeAbstract
{
    const NAME = 'Pixiv Bridge';
    const URI = 'https://www.pixiv.net/';
    const DESCRIPTION = 'Returns the tag search from pixiv.net';
    const MAINTAINER = 'mruac';
    const CONFIGURATION = [
        'cookie' => [
            'required' => false,
            'defaultValue' => null
        ],
        'proxy_url' => [
            'required' => false,
            'defaultValue' => null
        ]
    ];

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
                'values' => [
                    'All Works' => 'all',
                    'Illustrations' => 'illustrations/',
                    'Manga' => 'manga/',
                    'Novels' => 'novels/'
                ]
            ],
            'mature' => [
                'name' => 'Include R-18 works',
                'type' => 'checkbox'
            ],
            'ai' => [
                'name' => 'Include AI-Generated works',
                'type' => 'checkbox'
            ]
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
        return 'Pixiv ' . $this->getKey('mode') . " from {$context} {$query}";
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
        $key = $json_key;
        if (
            $this->queriedContext === 'Tag' &&
            $this->getOption('cookie') !== null
        ) {
            switch ($json_key) {
                case 'illust':
                case 'manga':
                    $key = 'illustManga';
                    break;
            }
        }
        $json = $json['body'][$key];
        // Tags context contains subkey
        if ($this->queriedContext === 'Tag') {
            $json = $json['data'];
            if ($this->getOption('cookie') !== null) {
                switch ($json_key) {
                    case 'illust':
                        $json = array_reduce($json, function ($acc, $i) {
                            if ($i['illustType'] === 0) {
                                $acc[] = $i;
                            }return $acc;
                        }, []);
                        break;
                    case 'manga':
                        $json = array_reduce($json, function ($acc, $i) {
                            if ($i['illustType'] === 1) {
                                $acc[] = $i;
                            }return $acc;
                        }, []);
                        break;
                }
            }
        }
        return $json;
    }

    private function collectWorksArray()
    {
        $content = $this->getData($this->getSearchURI($this->getInput('mode')), true, true);
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
        $this->checkOptions();
        $proxy_url = $this->getOption('proxy_url');
        $proxy_url = $proxy_url ? rtrim($proxy_url, '/') : null;

        $content = $this->collectWorksArray();
        $content = array_filter($content, function ($v, $k) {
            return !array_key_exists('isAdContainer', $v);
        }, ARRAY_FILTER_USE_BOTH);

        // Sort by updateDate to get newest works
        usort($content, function ($a, $b) {
            return $b['updateDate'] <=> $a['updateDate'];
        });

        //exclude AI generated works if unchecked.
        if ($this->getInput('ai') !== true) {
            $content = array_filter($content, function ($v) {
                $isAI = $v['aiType'] === 2;
                return !$isAI;
            });
        }

        //exclude R-18 works if unchecked.
        if ($this->getInput('mature') !== true) {
            $content = array_filter($content, function ($v) {
                $isMature = $v['xRestrict'] > 0;
                return !$isMature;
            });
        }

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

            if ($proxy_url) {
                //use proxy image host if set.
                if ($this->getInput('fullsize')) {
                    $ajax_uri = static::URI . 'ajax/illust/' . $result['id'];
                    $imagejson = $this->getData($ajax_uri, true, true);
                    $img_url = preg_replace('/https:\/\/i\.pximg\.net/', $proxy_url, $imagejson['body']['urls']['original']);
                } else {
                    $img_url = preg_replace('/https:\/\/i\.pximg\.net/', $proxy_url, $result['url']);
                }
            } else {
                $img_url = $result['url'];
                // Temporarily disabling caching of the image
                //$img_url = $this->cacheImage($result['url'], $result['id'], array_key_exists('illustType', $result));
            }

            // Currently, this might result in broken image due to their strict referrer check
            $item['content'] = sprintf('<a href="%s"><img src="%s"/></a>', $img_url, $img_url);

            // Additional content items
            if (array_key_exists('pageCount', $result)) {
                $item['content'] .= '<br>Page Count: ' . $result['pageCount'];
            } else {
                $item['content'] .= '<br>Word Count: ' . $result['wordCount'];
            }

            $this->items[] = $item;
        }
    }

    /**
     * todo: remove manual file cache
     * See bridge specific documentation for alternative option.
     */
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
                $imagejson = $this->getData($ajax_uri, true, true);
                $url = $imagejson['body']['urls']['original'];
            }

            $headers = ['Referer: ' . static::URI];
            try {
                $illust = $this->getData($url, true, false, $headers);
            } catch (Exception $e) {
                $illust = $this->getData($thumbnailurl, true, false, $headers); // Original thumbnail
            }
            file_put_contents($path, $illust);
        }

        return get_home_page_url() . 'cache/pixiv_img/' . preg_replace('/.*\//', '', $path);
    }

    private function checkOptions()
    {
        $proxy = $this->getOption('proxy_url');
        if ($proxy) {
            if (
                !(strlen($proxy) > 0 && preg_match('/https?:\/\/.*/', $proxy))
            ) {
                returnServerError('Invalid proxy_url value set. The proxy must include the HTTP/S at the beginning of the url.');
            }
        }

        $cookie = $this->getCookie();
        if ($cookie) {
            $isAuth = $this->loadCacheValue('is_authenticated');
            if (!$isAuth) {
                $res = $this->getData('https://www.pixiv.net/ajax/webpush', true, true);
                if ($res['error'] === false) {
                    $this->saveCacheValue('is_authenticated', true);
                }
            }
        }
    }

    private function checkCookie(array $headers)
    {
        if (array_key_exists('set-cookie', $headers)) {
            foreach ($headers['set-cookie'] as $value) {
                if (str_starts_with($value, 'PHPSESSID=')) {
                    parse_str(strtr($value, ['&' => '%26', '+' => '%2B', ';' => '&']), $cookie);
                    if ($cookie['PHPSESSID'] != $this->getCookie()) {
                        $this->saveCacheValue('cookie', $cookie['PHPSESSID']);
                    }
                    break;
                }
            }
        }
    }

    private function getCookie()
    {
        // checks if cookie is set, if not initialise it with the cookie from the config
        $value = $this->loadCacheValue('cookie');
        if (!isset($value)) {
            $value = $this->getOption('cookie');

            // 30 days + 1 day to let cookie chance to renew
            $this->saveCacheValue('cookie', $this->getOption('cookie'), 2678400);
        }
        return $value;
    }

    //Cache getContents by default
    private function getData(string $url, bool $cache = true, bool $getJSON = false, array $httpHeaders = [], array $curlOptions = [])
    {
        $cookie_str = $this->getCookie();
        if ($cookie_str) {
            $curlOptions[CURLOPT_COOKIE] = 'PHPSESSID=' . $cookie_str;
        }

        if ($cache) {
            $data = $this->loadCacheValue($url);
            if (!$data) {
                $data = getContents($url, $httpHeaders, $curlOptions, true);
                $this->saveCacheValue($url, $data);
            }
        } else {
            $data = getContents($url, $httpHeaders, $curlOptions, true);
        }

        $this->checkCookie($data['headers']);

        if ($getJSON) {
            return json_decode($data['content'], true);
        } else {
            return $data['content'];
        }
    }
}
