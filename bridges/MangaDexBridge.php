<?php

class MangaDexBridge extends BridgeAbstract
{
    const NAME = 'MangaDex Bridge';
    const URI = 'https://mangadex.org/';
    const API_ROOT = 'https://api.mangadex.org/';
    const DESCRIPTION = 'Returns MangaDex items using the API';

    const PARAMETERS = [
        'Title Chapters' => [
            'url' => [
                'name' => 'URL to title page',
                'exampleValue' => 'https://mangadex.org/title/f9c33607-9180-4ba6-b85c-e4b5faee7192/official-test-manga',
                'required' => true
            ],
            'external' => [
                'name' => 'Allow external feed items',
                'type' => 'checkbox',
                'title' => 'Some chapters are inaccessible or only available on an external site. Include these?'
            ],
            'limit' => [
                'name' => 'Item Limit',
                'type' => 'number',
                'defaultValue' => 10,
                'required' => true
            ],
            'lang' => [
                'name' => 'Chapter Languages (default=all)',
                'title' => 'comma-separated, two-letter language codes (example "en,jp")',
                'exampleValue' => 'en,jp',
                'required' => false
            ],
            'images' => [
                'name' => 'Fetch chapter page images',
                'type' => 'list',
                'title' => 'Places chapter images in feed contents. Entries will consume more bandwidth.',
                'defaultValue' => 'no',
                'values' => [
                    'None' => 'no',
                    'Data Saver' => 'saver',
                    'Full Quality' => 'yes'
                ]
            ]
        ],
        'Search Chapters' => [
            'chapter' => [
                'name' => 'Chapter Number (default=all)',
                'title' => 'The example value finds the newest first chapters',
                'exampleValue' => 1,
                'required' => false
            ],
            'groups' => [
                'name' => 'Group UUID (default=all)',
                'title' => 'This can be found in the MangaDex Group Page URL',
                'exampleValue' => '00e03853-1b96-4f41-9542-c71b8692033b',
                'required' => false,
            ],
            'uploader' => [
                'name' => 'User UUID (default=all)',
                'title' => 'This can be found in the MangaDex User Page URL',
                'exampleValue' => 'd2ae45e0-b5e2-4e7f-a688-17925c2d7d6b',
                'required' => false,
            ],
            'external' => [
                'name' => 'Allow external feed items',
                'type' => 'checkbox',
                'title' => 'Some chapters are inaccessible or only available on an external site. Include these?'
            ],
            'limit' => [
                'name' => 'Item Limit',
                'type' => 'number',
                'defaultValue' => 10,
                'required' => true
            ],
            'lang' => [
                'name' => 'Chapter Languages (default=all)',
                'title' => 'comma-separated, two-letter language codes (example "en,jp")',
                'exampleValue' => 'en,jp',
                'required' => false
            ],
            'images' => [
                'name' => 'Fetch chapter page images',
                'type' => 'list',
                'title' => 'Places chapter images in feed contents. Entries will consume more bandwidth.',
                'defaultValue' => 'no',
                'values' => [
                    'None' => 'no',
                    'Data Saver' => 'saver',
                    'Full Quality' => 'yes'
                ]
            ]
        ],
        // Future Manga Contexts:
        // Manga List (by author or tags): https://api.mangadex.org/swagger.html#/Manga/get-search-manga
        // Random Manga: https://api.mangadex.org/swagger.html#/Manga/get-manga-random
        // Future Chapter Contexts:
        // User Lists https://api.mangadex.org/swagger.html#/Feed/get-list-id-feed
        //
        // https://api.mangadex.org/docs/get-covers/
        'New manga' => [
            'originalLanguages' => [
                'name' => 'Original languages',
                'type' => 'text',
                'title' => 'Include only chapters originally in these languages',
                'exampleValue' => 'ja,ko,zh',
            ],
            'excludeOriginalLanguages' => [
                'name' => 'Exclude original languages',
                'type' => 'checkbox',
                'title' => 'Invert the selection in original languages',
            ],
            'translatedLanguage' => [
                'name' => 'Translated language',
                'type' => 'list',
                'values' => [
                    'Any' => '',
                    'Chinese (simplified)' => 'zh',
                    'English' => 'en',
                    'French' => 'fr',
                    'Korean' => 'ko',
                    'Spanish (LATAM)' => 'es-la',
                    'Spanish' => 'es',
                ],
            ],
            'order' => [
                'name' => 'Sort Order',
                'type' => 'list',
                'values' => [
                    'Recent chapter' => 'recentChapter',
                    'Recently added' => 'createdAt',
                    'Recently updated' => 'updatedAt',
                ],
                'defaultValue' => 'createdAt',
            ],
            'safe' => [
                'name' => 'Safe',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'suggestive' => [
                'name' => 'Suggestive',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'erotica' => [
                'name' => 'Erotica',
                'type' => 'checkbox',
                'defaultValue' => 'checked'
            ],
            'pornographic' => [
                'name' => 'Pornographic',
                'type' => 'checkbox'
            ],
        ],
    ];

    private const CDN_URI = 'https://uploads.mangadex.org/';
    private const CUSTOM_TAGS = ['content' => 'contentRating', 'demos' => 'publicationDemographic', 'statuses' => 'status'];
    const TITLE_REGEX = '#title/(?<uuid>[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})#';

    protected $feedName = '';
    protected $feedURI = '';

    protected function buildArrayQuery($name, $array)
    {
        $query = '';
        foreach ($array as $item) {
            $query .= '&' . $name . '=' . $item;
        }
        return $query;
    }

    protected function getAPI()
    {
        $params = [
            'limit' => $this->getInput('limit')
        ];

        $array_params = [];
        if (!empty($this->getInput('lang'))) {
            $array_params['translatedLanguage[]'] = explode(',', $this->getInput('lang'));
        }

        switch ($this->queriedContext) {
            case 'Title Chapters':
                preg_match(self::TITLE_REGEX, $this->getInput('url'), $matches)
                    or returnClientError('Invalid URL Parameter');
                $this->feedURI = self::URI . 'title/' . $matches['uuid'];
                $params['order[readableAt]'] = 'desc';
                if (!$this->getInput('external')) {
                    $params['includeFutureUpdates'] = '0';
                }
                $array_params['includes[]'] = ['manga', 'scanlation_group', 'user'];
                $uri = self::API_ROOT . 'manga/' . $matches['uuid'] . '/feed';
                break;
            case 'Search Chapters':
                $params['chapter'] = $this->getInput('chapter');
                $params['groups[]'] = $this->getInput('groups');
                $params['uploader'] = $this->getInput('uploader');
                $params['order[readableAt]'] = 'desc';
                if (!$this->getInput('external')) {
                    $params['includeFutureUpdates'] = '0';
                }
                $array_params['includes[]'] = ['manga', 'scanlation_group', 'user'];
                $uri = self::API_ROOT . 'chapter';
                break;
            default:
                returnServerError('Unimplemented Context (getAPI)');
        }

        // Remove null keys
        $params = array_filter($params, function ($v) {
            return !empty($v);
        });

        $uri .= '?' . http_build_query($params);

        // Arrays are passed as repeated keys to MangaDex
        // This cannot be handled by http_build_query
        foreach ($array_params as $name => $array_param) {
            $uri .= $this->buildArrayQuery($name, $array_param);
        }

        return $uri;
    }

    public function getName()
    {
        if ($this->queriedContext === 'New manga') {
            if ($this->getInput('order') !== 'recentChapter') {
                return $this->getKey('order') . ' manga - ' . parent::getName();
            }

            $title = 'Latest';

            $translatedLanguage = $this->getKey('translatedLanguage');
            if ($translatedLanguage !== 'Any') {
                $title .= ' ' . $translatedLanguage;
            }

            return $title . ' chapters - ' . parent::getName();
        }

        switch ($this->queriedContext) {
            case 'Title Chapters':
                return $this->feedName . ' Chapters';
            case 'Search Chapters':
                return 'MangaDex Chapter Search';
            default:
                return parent::getName();
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Title Chapters':
                return $this->feedURI;
            default:
                return parent::getURI();
        }
    }

    public function collectData()
    {
        if ($this->queriedContext === 'New manga') {
            $queryParts = [];
            $queryParts['limit'] = 50;

            if (!empty($this->getInput('originalLanguages'))) {
                $queryPart = $this->getInput('excludeOriginalLanguages') === true ? 'excludedOriginalLanguage' : 'originalLanguage';
                foreach (explode(',', $this->getInput('originalLanguages')) as $language) {
                    $language = trim($language);
                    $queryParts[$queryPart][] = $language;
                }
            }

            $queryParts['contentRating'] = array_filter(['safe', 'suggestive', 'erotica', 'pornographic'], function ($rating) {
                return $this->getInput($rating) === true;
            });

            if ($this->getInput('order') === 'recentChapter') {
                $this->items = $this->collectChapters($queryParts);
            } else {
                $this->items = $this->collectManga($queryParts);
            }

            return;
        }

        $api_uri = $this->getAPI();
        $header = [
            'Content-Type: application/json'
        ];
        $content = json_decode(getContents($api_uri, $header), true);
        if ($content['result'] == 'ok') {
            $content = $content['data'];
        } else {
            returnServerError('Could not retrieve API results');
        }

        switch ($this->queriedContext) {
            case 'Title Chapters':
                $this->getChapters($content);
                break;
            case 'Search Chapters':
                $this->getChapters($content);
                break;
            default:
                returnServerError('Unimplemented Context (collectData)');
        }
    }

    protected function getChapters($content)
    {
        foreach ($content as $chapter) {
            $item = [];
            $item['uid'] = $chapter['id'];
            $item['uri'] = self::URI . 'chapter/' . $chapter['id'];

            // External chapter
            if (!$this->getInput('external') && $chapter['attributes']['pages'] == 0) {
                continue;
            }

            $item['title'] = '';
            if (isset($chapter['attributes']['volume'])) {
                $item['title'] .= 'Volume ' . $chapter['attributes']['volume'] . ' ';
            }
            if (isset($chapter['attributes']['chapter'])) {
                $item['title'] .= 'Chapter ' . $chapter['attributes']['chapter'];
            }
            if (!empty($chapter['attributes']['title'])) {
                $item['title'] .= ' - ' . $chapter['attributes']['title'];
            }
            $item['title'] .= ' [' . $chapter['attributes']['translatedLanguage'] . ']';

            $item['timestamp'] = $chapter['attributes']['readableAt'];

            $groups = [];
            $users = [];
            foreach ($chapter['relationships'] as $rel) {
                switch ($rel['type']) {
                    case 'scanlation_group':
                        $groups[] = $rel['attributes']['name'];
                        break;
                    case 'manga':
                        if (empty($this->feedName)) {
                            $this->feedName = reset($rel['attributes']['title']);
                        }
                        if ($this->queriedContext !== 'Title Chapters') {
                            $item['title'] = reset($rel['attributes']['title']) . ' ' . $item['title'];
                        }
                        break;
                    case 'user':
                        if (isset($item['author'])) {
                            $users[] = $rel['attributes']['username'];
                        } else {
                            $item['author'] = $rel['attributes']['username'];
                        }
                        break;
                }
            }
            $item['content'] = 'Groups: ' .
                (empty($groups) ? 'No Group' : implode(', ', $groups));
            if (!empty($users)) {
                $item['content'] .= '<br>Other Users: ' . implode(', ', $users);
            }

            // Fetch chapter page images if desired and add to content
            if ($this->getInput('images') !== 'no') {
                $api_uri = self::API_ROOT . 'at-home/server/' . $item['uid'];
                $header = ['Content-Type: application/json'];
                $pages = json_decode(getContents($api_uri, $header), true);
                if ($pages['result'] != 'ok') {
                    returnServerError('Could not retrieve API results');
                }

                if ($this->getInput('images') == 'saver') {
                    $page_base = $pages['baseUrl'] . '/data-saver/' . $pages['chapter']['hash'] . '/';
                    foreach ($pages['chapter']['dataSaver'] as $image) {
                        $item['content'] .= '<br><img src="' . $page_base . $image . '"/>';
                    }
                } else {
                    $page_base = $pages['baseUrl'] . '/data/' . $pages['chapter']['hash'] . '/';
                    foreach ($pages['chapter']['data'] as $image) {
                        $item['content'] .= '<br><img src="' . $page_base . $image . '"/>';
                    }
                }
            }
            $this->items[] = $item;
        }
    }

    private function collectChapters(array $queryParts): array
    {
        if (!empty($this->getInput('translatedLanguage'))) {
            $queryParts['translatedLanguage'] = [$this->getInput('translatedLanguage')];
        }

        $queryParts['order'] = ['readableAt' => 'desc'];
        $queryParts['includes'] = ['manga'];

        $res = static::getMangaDexContents(static::API_ROOT . 'chapter?' . preg_replace('/\%5B\d+\%5D/', '%5B%5D', http_build_query($queryParts)));
        $mangaWithoutCover = [];
        $covers = $this->loadCacheValue('covers') ?? [];
        foreach ($res['data'] as $chapter) {
            foreach ($chapter['relationships'] as $relationship) {
                if ($relationship['type'] !== 'manga') {
                    continue;
                }

                if (empty($covers[$relationship['id']])) {
                    $mangaWithoutCover[$relationship['id']] = $relationship['id'];
                }

                break;
            }
        }

        $queryParts = [];
        $queryParts['limit'] = 100;
        $queryParts['order'] = ['volume' => 'desc'];
        while ($mangaWithoutCover) {
            $queryParts['manga'] = array_values($mangaWithoutCover);
            $coverRes = static::getMangaDexContents(static::API_ROOT . 'cover?' . preg_replace('/\%5B\d+\%5D/', '%5B%5D', http_build_query($queryParts)));

            foreach ($coverRes['data'] as $cover) {
                foreach ($cover['relationships'] as $relationship) {
                    if ($relationship['type'] !== 'manga') {
                        continue;
                    }

                    if (empty($covers[$relationship['id']])) {
                        $covers[$relationship['id']] = $cover;
                        unset($mangaWithoutCover[$relationship['id']]);
                    }

                    break;
                }
            }
        }

        $this->saveCacheValue('covers', $covers);

        $items = [];
        foreach ($res['data'] as $chapter) {
            $manga = [];
            foreach ($chapter['relationships'] as $relationship) {
                if ($relationship['type'] === 'manga') {
                    $manga = $relationship;
                    break;
                }
            }

            $title = static::getItemTitle($manga, $chapter, $this->getInput('translatedLanguage'));
            if (!empty($manga['attributes']['originalLanguage'])) {
                $title = '[' . $manga['attributes']['originalLanguage'] . '] ' . $title;
            }

            $coverURIs = [];
            if (!empty($covers[$manga['id']])) {
                $coverURIs = [static::CDN_URI . 'covers/' . $manga['id'] . '/' . $covers[$manga['id']]['attributes']['fileName']];
            }

            $categories = static::getMangaCategories($manga);
            $item = [
                'uri' => static::getMangaURI($manga),
                'title' => $title,
                'timestamp' => $chapter['attributes']['readableAt'],
                'content' => static::getMangaContent($manga, $chapter, $coverURIs, $this->getInput('translatedLanguage')),
                'enclosures' => $coverURIs,
                'categories' => $categories,
                'uid' => static::URI . $chapter['id'],
            ];

            $items[] = $item;
        }

        return $items;
    }

    private function collectManga(array $queryParts): array
    {
        if (!empty($this->getInput('translatedLanguage'))) {
            $queryParts['availableTranslatedLanguage'] = [$this->getInput('translatedLanguage')];
        }

        $queryParts['order'] = [$this->getInput('order') => 'desc'];
        $queryParts['includes'] = ['cover_art', 'author', 'artist'];

        $res = static::getMangaDexContents(static::API_ROOT . 'manga?' . preg_replace('/\%5B\d+\%5D/', '%5B%5D', http_build_query($queryParts)));
        $items = [];
        foreach ($res['data'] as $manga) {
            $authors = [];
            $covers = [];
            foreach ($manga['relationships'] as $relationship) {
                switch ($relationship['type']) {
                    case 'author':
                        $authors[] = $relationship['attributes']['name'];
                        break;
                    case 'cover_art':
                        $covers[] = static::CDN_URI . 'covers/' . $manga['id'] . '/' . $relationship['attributes']['fileName'];
                        break;
                }
            }

            switch ($this->getInput('order')) {
                case 'latestUploadedChapter':
                    $timestamp = $manga['attributes']['updatedAt'];
                    $uid = static::URI . $manga['attributes']['latestUploadedChapter'];
                    break;
                case 'updatedAt':
                    $timestamp = $manga['attributes']['updatedAt'];
                    $uid = static::URI . $manga['id'] . $manga['attributes']['updatedAt'];
                    break;
                default:
                    $timestamp = $manga['attributes']['createdAt'];
                    $uid = static::URI . $manga['id'];
                    break;
            }

            $title = static::getItemTitle($manga, [], $this->getInput('translatedLanguage'));
            if (!empty($manga['attributes']['originalLanguage'])) {
                $title = '[' . $manga['attributes']['originalLanguage'] . '] ' . $title;
            }

            $categories = static::getMangaCategories($manga);
            $item = [
                'uri' => static::getMangaURI($manga),
                'title' => $title,
                'timestamp' => $timestamp,
                'author' => reset($authors),
                'content' => static::getMangaContent($manga, [], $covers, $this->getInput('translatedLanguage')),
                'enclosures' => $covers,
                'categories' => $categories,
                'uid' => $uid,
            ];

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param string $uri api.mangadex.org URL
     *
     * @return array JSON array
     */
    private static function getMangaDexContents(string $uri): array
    {
        $contents = getContents($uri);
        $json = json_decode($contents, true);

        if (($json['result'] ?? null) !== 'ok') {
            returnServerError('Invalid response from server.');
        }

        return $json;
    }

    /**
     * @param array $manga JSON array of manga contents
     *
     * @return array Manga tags including content rating, demographic and status
     */
    private static function getMangaCategories(array $manga): array
    {
        $categories = [];
        foreach (static::CUSTOM_TAGS as $searchFilter => $customTag) {
            if (empty($manga['attributes'][$customTag])) {
                continue;
            }

            $categories[$searchFilter] = htmlspecialchars(ucfirst($manga['attributes'][$customTag]), ENT_XML1);
        }

        foreach ($manga['attributes']['tags'] ?? [] as $tag) {
            $categories[$tag['id']] = htmlspecialchars(reset($tag['attributes']['name']), ENT_XML1);
        }

        return $categories;
    }

    /**
     * @param array $manga JSON array of manga contents
     *
     * @return string Canonical URI of manga
     */
    private static function getMangaURI(array $manga): string
    {
        $title = $manga['attributes']['title'] ?? [];
        $title = array_reverse($title);
        $title = array_pop($title) ?? '';
        $title = preg_replace('/\s*\([^)]*\)\s*/', '', $title);
        $title = str_replace('ā', 'a', $title);
        $title = str_replace(['é', 'ē'], 'e', $title);
        $title = preg_replace('/[^A-Za-z0-9]+/', '-', $title);
        $title = strtolower($title);
        $title = rtrim($title, '-');

        if (strlen($title) > 100) {
            $title = substr($title, 0, 101);
            $title = implode('-', explode('-', $title, -1));
        }

        return static::URI . 'title/' . $manga['id'] . '/' . $title;
    }

    /**
     * @param array $manga JSON array of manga contents
     * @param array $chapter JSON array of chapter contents
     * @param string $preferredLanguage Language code, e.g. "en"
     *
     * @return string Feed item title in preferred language
     */
    private static function getItemTitle(array $manga, array $chapter, string $preferredLanguage): string
    {
        $title = '';
        $chapterTitle = '';

        if (!empty($chapter['attributes']['volume'])) {
            $chapterTitle .= 'Vol. ' . $chapter['attributes']['volume'];
        }

        if (!empty($chapter['attributes']['chapter'])) {
            if (!empty($chapterTitle)) {
                $chapterTitle .= ' ';
            }

            $chapterTitle .= 'Ch. ' . $chapter['attributes']['chapter'];
        }

        if (!empty($chapter['attributes']['title'])) {
            $chapterTitle .= ': ' . $chapter['attributes']['title'];
        }

        if (!empty($chapterTitle)) {
            $title .= $chapterTitle . ' | ';
        }

        $mangaTitles = static::getMangaTitles($manga);
        $mangaTitle = '';
        foreach ($mangaTitles as $currentTitle) {
            foreach ($currentTitle as $language => $value) {
                if (empty($mangaTitle)) {
                    $mangaTitle = $value;
                }

                if (empty($preferredLanguage) || $language === $preferredLanguage) {
                    return $title . $value;
                }
            }
        }

        return $title . $mangaTitle;
    }

    /**
     * @param array $manga JSON array of manga contents
     * @param array $chapter JSON array of chapter contents
     * @param array $coverURIs of manga cover images
     * @param string $preferredLanguage for titles, etc.
     *
     * @return string Feed item content HTML code
     */
    private static function getMangaContent(array $manga, array $chapter, array $coverURIs, string $preferredLanguage): string
    {
        $content = '';

        if (!empty($coverURIs)) {
            foreach ($coverURIs as $coverURI) {
                $content .= '<p><img src="' . htmlspecialchars($coverURI) . '.256.jpg"></p>';
            }
        }

        if (!empty($manga)) {
            $content .= '<p>Manga link: <a href="' . static::getMangaURI($manga) . '">' . htmlspecialchars(reset($manga['attributes']['title']) ?? '') . '</a></p>';
        }

        if (!empty($chapter)) {
            $chapterTitle = $chapter['attributes']['title'] ?: 'No chapter title';
            $content .= '<p>Chapter link: <a href="' . static::URI . 'chapter/' . htmlspecialchars($chapter['id']) . '">' . htmlspecialchars($chapterTitle) . '</a></p>';
        }


        $descriptions = $manga['attributes']['description'] ?? [];
        $description = '';
        foreach ($descriptions as $key => $value) {
            if (empty($description)) {
                $description = $value;
            }

            if (empty($preferredLanguage) || $key === $preferredLanguage) {
                $description = $value;
                break;
            }
        }

        $content .= '<p>' . nl2br(htmlspecialchars($description), false) . '</p><hr>';

        $categories = static::getMangaCategories($manga);
        if (!empty($categories)) {
            $htmlCategories = [];
            foreach ($categories as $key => $value) {
                if (empty(static::CUSTOM_TAGS[$key])) {
                    $href = static::URI . 'tag/' . htmlspecialchars($key);
                } else {
                    $href = static::URI . 'titles?' . htmlspecialchars($key) . '=' . htmlspecialchars(strtolower($value));
                }

                $htmlCategories[] = '<a href="' . $href . '">' . htmlspecialchars($value) . '</a>';
            }
            $content .= '<p>Tags: ' . implode(', ', $htmlCategories) . '</p>';
        }

        $authors = [];
        $artists = [];
        foreach ($manga['relationships'] ?? [] as $relationship) {
            switch ($relationship['type']) {
                case 'author':
                    $authors[$relationship['id']] = $relationship['attributes']['name'];
                    break;
                case 'artist':
                    $artists[$relationship['id']] = $relationship['attributes']['name'];
                    break;
            }
        }

        foreach (['Authors' => $authors, 'Artists' => $artists] as $description => $items) {
            if (empty($items)) {
                continue;
            }

            asort($items);

            $htmlItems = [];
            foreach ($items as $id => $name) {
                $htmlItems[] = '<a href="' . static::URI . 'author/' . htmlspecialchars($id) . '">' . htmlspecialchars($name) . '</a>';
            }

            $content .= '<p>' . substr($description, 0, 1 < count($htmlItems) ? PHP_INT_MAX : -1) . ': ' . implode(', ', $htmlItems) . '</p>';
        }

        $mangaTitles = static::getMangaTitles($manga);
        if (!empty($mangaTitles)) {
            $htmlAltTitles = [];
            foreach ($mangaTitles as $altTitle) {
                foreach ($altTitle as $language => $title) {
                    $htmlAltTitles[] = 'Title (' . htmlspecialchars($language) . '): ' . htmlspecialchars($title);
                }
            }

            sort($htmlAltTitles);
            $content .= '<p>' . implode('<br>', $htmlAltTitles) . '</p>';
        }

        $content = str_replace('', '', $content);

        return $content;
    }

    private static function getMangaTitles(array $manga): array
    {
        $mangaTitles = [];

        if (!empty($manga['attributes']['title'])) {
            $mangaTitles[] = $manga['attributes']['title'];
        }

        if (!empty($manga['attributes']['altTitles'])) {
            $mangaTitles = array_merge($mangaTitles, $manga['attributes']['altTitles']);
        }

        return $mangaTitles;
    }
}
