<?php

/**
 * This bridge generates feeds for threads from forums running XenForo version 2
 *
 * Examples:
 * - https://xenforo.com/community/
 * - http://www.ign.com/boards/
 *
 * Notice: XenForo does provide RSS feeds for forums. For example:
 * - https://xenforo.com/community/forums/-/index.rss
 *
 * For more information on XenForo, visit
 * - https://xenforo.com/
 * - https://en.wikipedia.org/wiki/XenForo
 */
class XenForoBridge extends BridgeAbstract
{
    // Bridge specific constants
    const CONTEXT_THREAD = 'Thread';
    const XENFORO_VERSION_1 = '1.0';
    const XENFORO_VERSION_2 = '2.0';

    // RSS-Bridge constants
    const NAME = 'XenForo Bridge';
    const URI = 'https://xenforo.com/';
    const DESCRIPTION = 'Generates feeds for threads in forums powered by XenForo';
    const MAINTAINER = 'logmanoriginal';
    const PARAMETERS = [
        self::CONTEXT_THREAD => [
            'url' => [
                'name' => 'Thread URL',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert URL to the thread for which the feed should be generated',
                'exampleValue' => 'https://xenforo.com/community/threads/guide-to-suggestions.2285/'
            ]
        ],
        'global' => [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Specify maximum number of elements to return in the feed',
                'defaultValue' => 10
            ]
        ]
    ];
    const CACHE_TIMEOUT = 7200; // 10 minutes

    private $title = '';
    private $threadurl = '';
    private $version; // Holds the XenForo version

    public function getName()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_THREAD:
                return $this->title . ' - ' . static::NAME;
        }

        return parent::getName();
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_THREAD:
                return $this->threadurl;
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $this->threadurl = filter_var(
            $this->getInput('url'),
            FILTER_VALIDATE_URL,
            FILTER_FLAG_PATH_REQUIRED
        );

        if ($this->threadurl === false) {
            returnClientError('The URL you provided is invalid!');
        }

        $urlparts = parse_url($this->threadurl, PHP_URL_SCHEME);

        // Scheme must be "http" or "https"
        if (preg_match('/http[s]{0,1}/', parse_url($this->threadurl, PHP_URL_SCHEME)) == false) {
            returnClientError('The URL you provided doesn\'t specify a valid scheme (http or https)!');
        }

        // Path cannot be root (../)
        if (parse_url($this->threadurl, PHP_URL_PATH) === '/') {
            returnClientError('The URL you provided doesn\'t link to a valid thread (root path)!');
        }

        // XenForo adds a thread ID to the URL, like "...-thread.454934283". It must be present
        if (preg_match('/.+\.\d+[\/]{0,1}/', parse_URL($this->threadurl, PHP_URL_PATH)) == false) {
            returnClientError('The URL you provided doesn\'t link to a valid thread (ID missing)!');
        }

        // We want to start at the first page in the thread. XenForo uses "../page-n" syntax
        // to identify pages (except for the first page).
        // Notice: XenForo uses the concept of "sentinels" to find and replace parts in the
        // URL. Technically forum hosts can change the syntax!
        if (preg_match('/.+\/(page-\d+.*)$/', $this->threadurl, $matches) != false) {
            // before: https://xenforo.com/community/threads/guide-to-suggestions.2285/page-5
            // after : https://xenforo.com/community/threads/guide-to-suggestions.2285/
            $this->threadurl = str_replace($matches[1], '', $this->threadurl);
        }

        $html = getSimpleHTMLDOMCached($this->threadurl);

        $html = defaultLinkTo($html, $this->threadurl);

        // Notice: The DOM structure changes depending on the XenForo version used
        if ($mainContent = $html->find('div.mainContent', 0)) {
            $this->version = self::XENFORO_VERSION_1;
        } elseif ($mainContent = $html->find('div[class~="p-body"]', 0)) {
            $this->version = self::XENFORO_VERSION_2;
        } else {
            returnServerError('This forum is currently not supported!');
        }

        switch ($this->version) {
            case self::XENFORO_VERSION_1:
                $titleBar = $mainContent->find('div.titleBar > h1', 0)
                    or returnServerError('Error finding title bar!');

                $this->title = $titleBar->plaintext;

                // Store items from current page (we'll use $this->items as LIFO buffer)
                $this->extractThreadPostsV1($html, $this->threadurl);
                $this->extractPagesV1($html);

                break;

            case self::XENFORO_VERSION_2:
                $titleBar = $mainContent->find('div[class~="p-title"] h1', 0)
                    or returnServerError('Error finding title bar!');

                $this->title = $titleBar->plaintext;
                $this->extractThreadPostsV2($html, $this->threadurl);
                $this->extractPagesV2($html);

                break;
        }

        usort($this->items, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $this->items = array_slice($this->items, 0, $this->getInput('limit'));
    }

    /**
     * Extracts thread posts
     * @param $html A simplehtmldom object
     * @param $url The url from which $html was loaded
     */
    private function extractThreadPostsV1($html, $url)
    {
        $lang = $html->find('html', 0)->lang;

        // Posts are contained in an "ol"
        $messageList = $html->find('#messageList > li')
            or returnServerError('Error finding message list!');

        foreach ($messageList as $post) {
            if (!isset($post->attr['id'])) { // Skip ads
                continue;
            }

            $item = [];

            $item['uri'] = $url . '#' . $post->getAttribute('id');

            $content = $post->find('.messageContent > article', 0);

            // Add some style to quotes
            foreach ($content->find('.bbCodeQuote') as $quote) {
                $quote->style = '
					color: #495566;
					background-color: rgb(248,251,253);
					border: 1px solid rgb(111, 140, 180);
					border-color: rgb(111, 140, 180);
					font-style: italic;';
            }

            // Remove script tags
            foreach ($content->find('script') as $script) {
                $script->outertext = '';
            }

            $item['content'] = $content->innertext;

            // Remove quotes (for the title)
            foreach ($content->find('.bbCodeQuote') as $quote) {
                $quote->innertext = '';
            }

            $title = trim($content->plaintext);

            if (strlen($title) > 70) {
                $item['title'] = substr($title, 0, strpos($title, ' ', 70)) . '...';
            } else {
                $item['title'] = $title;
            }

            /**
             * Timestamps are presented in two forms:
             *
             * 1) short version (for older posts?)
             * <span
             *  class="DateTime"
             *  title="22 Oct. 2018 at 23:47"
             * >22 Oct. 2018</span>
             *
             * This form has to be interpreted depending on the current language.
             *
             * 2) long version (for newer posts?)
             * <abbr
             *  class="DateTime"
             *  data-time="1541008785"
             *  data-diff="310694"
             *  data-datestring="31 Oct. 2018"
             *  data-timestring="18:59"
             *  title="31 Oct. 2018 at 18:59"
             * >Wednesday at 18:59</abbr>
             *
             * This form has the timestamp embedded (data-time)
             */
            if ($timestamp = $post->find('abbr.DateTime', 0)) { // long version (preffered)
                $item['timestamp'] = $timestamp->{'data-time'};
            } elseif ($timestamp = $post->find('span.DateTime', 0)) { // short version
                $item['timestamp'] = $this->fixDate($timestamp->title, $lang);
            }

            $item['author'] = $post->getAttribute('data-author');

            // Bridge specific properties
            $item['id'] = $post->getAttribute('id');

            $this->items[] = $item;
        }
    }

    private function extractThreadPostsV2($html, $url)
    {
        $lang = $html->find('html', 0)->lang;

        $messageList = $html->find('div[class~="block-body"] article')
            or returnServerError('Error finding message list!');

        foreach ($messageList as $post) {
            if (!isset($post->attr['id'])) { // Skip ads
                continue;
            }

            $item = [];

            $item['uri'] = $url . '#' . $post->getAttribute('id');

            $title = $post->find('div[class~="message-content"] article', 0)->plaintext;
            $end = strpos($title, ' ', min(70, strlen($title)));
            $item['title'] = substr($title, 0, $end);

            if ($post->find('time[datetime]', 0)) {
                $item['timestamp'] = $post->find('time[datetime]', 0)->datetime;
            } else {
                $item['timestamp'] = $this->fixDate($post->find('time', 0)->title, $lang);
            }
            $item['author'] = $post->getAttribute('data-author');
            $item['content'] = $post->find('div[class~="message-content"] article', 0);

            // Bridge specific properties
            $item['id'] = $post->getAttribute('id');

            $this->items[] = $item;
        }
    }

    private function extractPagesV1($html)
    {
        // A navigation bar becomes available if the number of posts grows too
        // high. When this happens we need to load further pages (from last backwards)
        if (($pageNav = $html->find('div.PageNav', 0))) {
            $lastpage = $pageNav->{'data-last'};
            $baseurl  = $pageNav->{'data-baseurl'};
            $sentinel = $pageNav->{'data-sentinel'};

            $hosturl  = parse_url($this->threadurl, PHP_URL_SCHEME)
            . '://'
            . parse_url($this->threadurl, PHP_URL_HOST)
            . '/';

            $page = $lastpage;

            // Load at least the last page
            do {
                $pageurl = str_replace($sentinel, $lastpage, $baseurl);

                // We can optimize performance by caching all but the last page
                if ($page != $lastpage) {
                    $html = getSimpleHTMLDOMCached($pageurl)
                        or returnServerError('Error loading contents from ' . $pageurl . '!');
                } else {
                    $html = getSimpleHTMLDOM($pageurl)
                        or returnServerError('Error loading contents from ' . $pageurl . '!');
                }

                $html = defaultLinkTo($html, $hosturl);

                $this->extractThreadPostsV1($html, $pageurl);

                $page--;
            } while (count($this->items) < $this->getInput('limit') && $page != 1);
        }
    }

    private function extractPagesV2($html)
    {
        // A navigation bar becomes available if the number of posts grows too
        // high. When this happens we need to load further pages (from last backwards)
        if (($pageNav = $html->find('div.pageNav', 0))) {
            foreach ($pageNav->find('li') as $nav) {
                $lastpage = $nav->plaintext;
            }

            // Manually extract baseurl and inject sentinel
            $baseurl = $pageNav->find('li > a', -1)->href;
            $baseurl = str_replace('page-' . $lastpage, 'page-{{sentinel}}', $baseurl);

            $sentinel = '{{sentinel}}';

            $hosturl  = parse_url($this->threadurl, PHP_URL_SCHEME)
            . '://'
            . parse_url($this->threadurl, PHP_URL_HOST);

            $page = $lastpage;

            // Load at least the last page
            do {
                $pageurl = str_replace($sentinel, $lastpage, $baseurl);

                // We can optimize performance by caching all but the last page
                if ($page != $lastpage) {
                    $html = getSimpleHTMLDOMCached($pageurl)
                        or returnServerError('Error loading contents from ' . $pageurl . '!');
                } else {
                    $html = getSimpleHTMLDOM($pageurl)
                        or returnServerError('Error loading contents from ' . $pageurl . '!');
                }

                $html = defaultLinkTo($html, $hosturl);

                $this->extractThreadPostsV2($html, $pageurl);

                $page--;
            } while (count($this->items) < $this->getInput('limit') && $page != 1);
        }
    }

    /**
     * Fixes dates depending on the choosen language:
     *
     * de : dd.mm.yy
     * en : dd.mm.yy
     * it : dd/mm/yy
     *
     * Basically strtotime doesn't convert dates correctly due to formats
     * being hard to interpret. So we use the DateTime object.
     *
     * We don't know the timezone, so just assume +00:00 (or whatever
     * DateTime chooses)
     */
    private function fixDate($date, $lang = 'en-US')
    {
        $mnamesen = [
            'January',
            'Feburary',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        switch ($lang) {
            case 'en-US': // example: Jun 9, 2018 at 11:46 PM
                $df = date_create_from_format('M d, Y \a\t H:i A', $date);
                break;

            case 'de-DE': // example: 19 Juli 2018 um 19:27 Uhr
                $mnamesde = [
                    'Januar',
                    'Februar',
                    'März',
                    'April',
                    'Mai',
                    'Juni',
                    'Juli',
                    'August',
                    'September',
                    'Oktober',
                    'November',
                    'Dezember'
                ];

                $mnamesdeshort = [
                    'Jan.',
                    'Feb.',
                    'Mär.',
                    'Apr.',
                    'Mai',
                    'Juni',
                    'Juli',
                    'Aug.',
                    'Sep.',
                    'Okt.',
                    'Nov.',
                    'Dez.'
                ];

                $date = str_ireplace($mnamesde, $mnamesen, $date);
                $date = str_ireplace($mnamesdeshort, $mnamesen, $date);

                $df = date_create_from_format('d M Y \u\m H:i \U\h\r', $date);
                break;
        }

        // Debug::log(date_format($df, 'U'));

        return date_format($df, 'U');
    }
}
