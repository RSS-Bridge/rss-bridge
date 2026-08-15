<?php

class FacebookBridge extends BridgeAbstract
{
    // const MAINTAINER = 'teromene, logmanoriginal';
    const NAME = 'Facebook Bridge | Main Site';
    const URI = 'https://www.facebook.com/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Input a page title or a profile log. For a profile log,
 please insert the parameter as follow : myExamplePage/132621766841117';

    const PARAMETERS = [
        'User' => [
            'u' => [
                'name' => 'Username',
                'required' => true,
                'exampleValue' => 'zuck'
            ],
            'media_type' => [
                'name' => 'Media type',
                'type' => 'list',
                'required' => false,
                'values' => [
                    'All' => 'all',
                    'Video' => 'video',
                    'No Video' => 'novideo'
                ],
                'defaultValue' => 'all'
            ],
            'skip_reviews' => [
                'name' => 'Skip reviews',
                'type' => 'checkbox',
                'required' => false,
                'defaultValue' => false,
                'title' => 'Feed includes reviews when unchecked'
            ]
        ],
        'Group' => [
            'g' => [
                'name' => 'Group',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'https://www.facebook.com/groups/743149642484225',
                'title' => 'Insert group name or facebook group URL'
            ]
        ],
        'global' => [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Specify the number of items to return (default: -1)',
                'defaultValue' => -1
            ]
        ]
    ];

    private $authorName = '';
    private $groupName = '';

    public function getIcon()
    {
        return 'https://static.xx.fbcdn.net/rsrc.php/y1/r/ay1hV6OlegS.ico';
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'User':
                if (!empty($this->authorName)) {
                    return $this->extraInfos['name'] ?? $this->authorName;
                }
                break;

            case 'Group':
                if (!empty($this->groupName)) {
                    return $this->groupName;
                }
                break;
        }

        return parent::getName();
    }

    public function detectParameters($url)
    {
        $params = [];

        // By profile
        $regex = '/^(https?:\/\/)?(www\.)?facebook\.com\/profile\.php\?id\=([^\/?&\n]+)?(.*)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'User';
            $params['u'] = urldecode($matches[3]);
            return $params;
        }

        // By group
        $regex = '/^(https?:\/\/)?(www\.)?facebook\.com\/groups\/([^\/?\n]+)?(.*)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = 'Group';
            $params['g'] = urldecode($matches[3]);
            return $params;
        }

        // By username
        $regex = '/^(https?:\/\/)?(www\.)?facebook\.com\/([^\/?\n]+)/';

        if (preg_match($regex, $url, $matches) > 0) {
            $params['context'] = '';
            $params['u'] = urldecode($matches[3]);
            return $params;
        }

        return null;
    }

    public function getURI()
    {
        $uri = self::URI;

        switch ($this->queriedContext) {
            case 'Group':
                // Discover groups via  https://www.facebook.com/groups/
                // Example group:       https://www.facebook.com/groups/sailors.worldwide
                $uri .= 'groups/' . $this->sanitizeGroup(filter_var($this->getInput('g'), FILTER_SANITIZE_URL));
                break;

            case 'User':
                // Example user 1:      https://www.facebook.com/artetv/
                // Example user 2:      artetv
                $user = $this->sanitizeUser($this->getInput('u'));

                if (!strpos($user, '/')) {
                    $uri .= urlencode($user) . '/';
                } else {
                    $uri .= 'pages/' . $user;
                }

                return $uri;
        }

        // Request the mobile version to reduce page size (no javascript)
        // More information: https://stackoverflow.com/a/11103592
        return $uri .= '?_fb_noscript=1';
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Group':
                $this->collectGroupData();
                break;

            case 'User':
                $this->collectUserData();
                break;

            default:
                throwClientException('Unknown context: "' . $this->queriedContext . '"!');
        }

        $limit = $this->getInput('limit') ?: -1;

        if ($limit > 0 && count($this->items) > $limit) {
            $this->items = array_slice($this->items, 0, $limit);
        }
    }

    #region Group

    private function collectGroupData()
    {
        if (getEnv('HTTP_ACCEPT_LANGUAGE')) {
            $header = ['Accept-Language: ' . getEnv('HTTP_ACCEPT_LANGUAGE')];
        } else {
            $header = [];
        }

        $touchURI = str_replace(
            'https://www.facebook',
            'https://touch.facebook',
            $this->getURI()
        );

        $html = getSimpleHTMLDOM($touchURI, $header);

        if (!$this->isPublicGroup($html)) {
            throwClientException('This group is not public! RSS-Bridge only supports public groups!');
        }

        defaultLinkTo($html, substr(self::URI, 0, strlen(self::URI) - 1));

        $this->groupName = $this->extractGroupName($html);

        $posts = $html->find('div.story_body_container')
            or throwServerException('Failed finding posts!');

        foreach ($posts as $post) {
            $item = [];

            $item['uri'] = $this->extractGroupPostURI($post);
            $item['title'] = $this->extractGroupPostTitle($post);
            $item['author'] = $this->extractGroupPostAuthor($post);
            $item['content'] = $this->extractGroupPostContent($post);
            $item['enclosures'] = $this->extractGroupPostEnclosures($post);

            $this->items[] = $item;
        }
    }

    private function sanitizeGroup($group)
    {
        if (
            filter_var(
                $group,
                FILTER_VALIDATE_URL,
                FILTER_FLAG_PATH_REQUIRED
            )
        ) {
            // User provided a URL

            $urlparts = parse_url($group);

            $this->validateHost($urlparts['host']);

            return explode('/', $urlparts['path'])[2];
        } elseif (strpos($group, '/') !== false) {
            throwClientException('The group you provided is invalid: ' . $group);
        } else {
            return $group;
        }
    }

    private function validateHost($provided_host)
    {
        // Handle mobile links
        if (strpos($provided_host, 'm.') === 0) {
            $provided_host = substr($provided_host, strlen('m.'));
        }
        if (strpos($provided_host, 'touch.') === 0) {
            $provided_host = substr($provided_host, strlen('touch.'));
        }

        $facebook_host = parse_url(self::URI)['host'];

        if (
            $provided_host !== $facebook_host
            && 'www.' . $provided_host !== $facebook_host
        ) {
            throwClientException('The host you provided is invalid! Received "'
                . $provided_host
                . '", expected "'
                . $facebook_host
                . '"!');
        }
    }

    /**
     * @param $html simple_html_dom
     * @return bool
     */
    private function isPublicGroup($html)
    {
        // Facebook touch just presents a login page for non-public groups
        $title = $html->find('title', 0);
        return $title->plaintext !== 'Log in to Facebook | Facebook';
    }

    private function extractGroupName($html)
    {
        $ogtitle = $html->find('._de1', 0)
            or throwServerException('Unable to find group title!');

        return html_entity_decode($ogtitle->plaintext, ENT_QUOTES);
    }

    private function extractGroupPostURI($post)
    {
        $elements = $post->find('a')
            or throwServerException('Unable to find URI!');

        foreach ($elements as $anchor) {
            // Find the one that is a permalink
            if (strpos($anchor->href, 'permalink') !== false) {
                $arr = explode('?', $anchor->href, 2);
                return $arr[0];
            }
        }

        return null;
    }

    private function extractGroupPostContent($post)
    {
        $content = $post->find('div._5rgt', 0)
            or throwServerException('Unable to find user content!');

        $context_text = $content->innertext;
        if ($content->next_sibling() !== null) {
            $context_text .= $content->next_sibling()->innertext;
        }
        return $context_text;
    }

    private function extractGroupPostAuthor($post)
    {
        $element = $post->find('h3 a', 0)
            or throwServerException('Unable to find author information!');

        return $element->plaintext;
    }

    private function extractGroupPostEnclosures($post)
    {
        $elements = $post->find('span._6qdm');
        if ($post->find('div._5rgt', 0)->next_sibling() !== null) {
            array_push($elements, ...$post->find('div._5rgt', 0)->next_sibling()->find('i.img'));
        }

        $enclosures = [];

        $background_img_regex = '/background-image: ?url\\((.+?)\\);/';

        foreach ($elements as $enclosure) {
            if (preg_match($background_img_regex, $enclosure, $matches) > 0) {
                $bg_img_value = trim(html_entity_decode($matches[1], ENT_QUOTES), "'\"");
                $bg_img_url = urldecode(preg_replace('/\\\([0-9a-z]{2}) /', '%$1', $bg_img_value));
                $enclosures[] = urldecode($bg_img_url);
            }
        }

        return empty($enclosures) ? null : $enclosures;
    }

    private function extractGroupPostTitle($post)
    {
        $element = $post->find('h3', 0)
            or throwServerException('Unable to find title!');

        if (strpos($element->plaintext, 'shared') === false) {
            $content = strip_tags($this->extractGroupPostContent($post));

            return $this->extractGroupPostAuthor($post)
            . ' posted: '
            . substr(
                $content,
                0,
                strpos(wordwrap($content, 64), "\n")
            )
            . '...';
        }

        return $element->plaintext;
    }

    #endregion (Group)

    #region User

    /**
     * Checks if $user is a valid username or URI and returns the username
     */
    private function sanitizeUser($user)
    {
        if (filter_var($user, FILTER_VALIDATE_URL)) {
            $urlparts = parse_url($user);

            $this->validateHost($urlparts['host']);

            if (
                !array_key_exists('path', $urlparts)
                || $urlparts['path'] === '/'
            ) {
                throwClientException('The URL you provided doesn\'t contain the user name!');
            }

            $path = explode('/', $urlparts['path'])[1];

            // Numeric profiles are addressed as profile.php?id=<id>
            if ($path === 'profile.php' && isset($urlparts['query'])) {
                parse_str($urlparts['query'], $query);

                if (isset($query['id'])) {
                    return $query['id'];
                }
            }

            return $path;
        } else {
            // First character cannot be a forward slash
            if (strpos($user, '/') === 0) {
                throwClientException('Remove leading slash "/" from the username!');
            }

            // Numeric profiles are addressed as profile.php?id=<id>
            if (strpos($user, 'profile.php?id=') === 0) {
                parse_str(parse_url($user, PHP_URL_QUERY) ?? '', $query);

                if (isset($query['id'])) {
                    return $query['id'];
                }
            }

            return $user;
        }
    }

    /**
     * Returns the HTTP headers of a recent desktop browser.
     *
     * Facebook rejects requests that do not look like they originate from a
     * regular browser with "400 Bad Request". The mobile sites (m, mbasic and
     * touch) no longer serve static markup, so the desktop site is the only
     * remaining source of post data without JavaScript.
     */
    private function getHttpHeaders()
    {
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'sec-ch-ua: "Chromium";v="131", "Not_A Brand";v="24"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Linux"',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
        ];

        if (getEnv('HTTP_ACCEPT_LANGUAGE')) {
            $headers[] = 'Accept-Language: ' . getEnv('HTTP_ACCEPT_LANGUAGE');
        } else {
            $headers[] = 'Accept-Language: en-US,en;q=0.9';
        }

        return $headers;
    }

    /**
     * Returns the first value stored under $key found anywhere in $data
     */
    private function findFirst($data, $key)
    {
        $stack = [$data];

        while ($stack) {
            $current = array_pop($stack);

            if (!is_array($current)) {
                continue;
            }

            if (array_key_exists($key, $current)) {
                return $current[$key];
            }

            foreach ($current as $value) {
                if (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }

        return null;
    }

    /**
     * Extracts the story nodes embedded in the page as JSON.
     *
     * The desktop page ships the most recent post of the timeline inside a
     * <script type="application/json"> element. Additional posts are loaded
     * lazily via GraphQL, which requires tokens that are rate limited, so only
     * the prefetched post is easily available.
     */
    private function extractTimelineNodes($html)
    {
        $nodes = [];

        foreach ($html->find('script[type="application/json"]') as $script) {
            $json = html_entity_decode($script->innertext, ENT_QUOTES, 'UTF-8');

            if (strpos($json, 'timeline_list_feed_units') === false) {
                continue;
            }

            try {
                $data = Json::decode($json);
            } catch (\JsonException $e) {
                continue;
            }

            $stack = [$data];

            while ($stack) {
                $current = array_pop($stack);

                if (!is_array($current)) {
                    continue;
                }

                if (isset($current['timeline_list_feed_units']['edges'])) {
                    foreach ($current['timeline_list_feed_units']['edges'] as $edge) {
                        if (isset($edge['node'])) {
                            $nodes[] = $edge['node'];
                        }
                    }
                }

                foreach ($current as $value) {
                    if (is_array($value)) {
                        $stack[] = $value;
                    }
                }
            }
        }

        return $nodes;
    }

    /**
     * Converts a story node into a feed item
     */
    private function parseTimelineNode($node, $profilePicture)
    {
        $story = $node['comet_sections']['content']['story'] ?? [];

        $message = $this->findFirst($story, 'message');
        $text = is_array($message) ? ($message['text'] ?? '') : '';

        $item = [];
        $item['uri'] = $this->findFirst($story, 'wwwURL') ?? self::URI;
        $item['author'] = $this->findFirst($node['feedback'] ?? [], 'name') ?? $this->authorName;
        $item['timestamp'] = $node['creation_time'] ?? null;
        $item['uid'] = $node['post_id'] ?? null;

        $content = '';
        $enclosures = [];

        foreach ($node['attachments'] ?? [] as $attachment) {
            $image = $this->findFirst($attachment, 'photo_image');

            if (isset($image['uri'])) {
                $content .= '<p><img src="' . $image['uri'] . '" referrerpolicy="no-referrer" /></p>';
                $enclosures[] = $image['uri'];
            }
        }

        $content = '<p>' . nl2br(htmlspecialchars($text, ENT_QUOTES)) . '</p>' . $content;

        $item['content'] = $content;
        $item['title'] = $this->generateTitle($text, $item['timestamp']);
        $item['enclosures'] = $enclosures ?: [$profilePicture];

        return $item;
    }

    /**
     * Builds a title from the first line of the post
     */
    private function generateTitle($text, $timestamp)
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if ($text === '') {
            return 'Post from ' . gmdate('Y-m-d', (int)$timestamp);
        }

        if (mb_strlen($text) > 100) {
            $text = mb_substr($text, 0, 100) . '...';
        }

        return $text;
    }

    private function collectUserData()
    {
        $url = $this->getURI();
        $html = getSimpleHTMLDOM($url, $this->getHttpHeaders());

        // Pages that are unavailable or require a login are served without
        // Open Graph metadata and only contain a login form.
        $title = $html->find('meta[property="og:title"]', 0);

        if (!$title) {
            throwServerException(sprintf(
                'This page is not publicly available. RSS-Bridge only supports public pages: %s',
                $url
            ));
        }

        $this->authorName = html_entity_decode($title->content, ENT_QUOTES, 'UTF-8');

        $image = $html->find('meta[property="og:image"]', 0);
        $profilePicture = $image ? html_entity_decode($image->content, ENT_QUOTES, 'UTF-8') : '';

        $nodes = $this->extractTimelineNodes($html);

        if (!$nodes) {
            throw new \Exception(sprintf('Unable to find any post in %s', $url));
        }

        foreach ($nodes as $node) {
            $this->items[] = $this->parseTimelineNode($node, $profilePicture);
        }
    }

    #endregion (User)
}
