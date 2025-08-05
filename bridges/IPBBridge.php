<?php

class IPBBridge extends FeedExpander
{
    const NAME = 'IPB Bridge';
    const URI = 'https://www.invisionpower.com';
    const DESCRIPTION = 'Returns feeds for forums powered by IPB';
    const MAINTAINER = 'logmanoriginal';
    const PARAMETERS = [
        [
            'uri' => [
                'name' => 'URI',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert forum, subforum or topic URI',
                'exampleValue' => 'https://invisioncommunity.com/forums/forum/499-feedback-and-ideas/'
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Specifies the number of items to return on each request (-1: all)',
                'defaultValue' => 10
            ]
        ]
    ];
    const CACHE_TIMEOUT = 3600;

    // Constants for internal use
    const FORUM_TYPE_LIST_FILTER = '.cForumTopicTable';
    const FORUM_TYPE_TABLE_FILTER = '#forum_table';

    const TOPIC_TYPE_ARTICLE = 'article';
    const TOPIC_TYPE_DIV = 'div.post_block';

    public function getURI()
    {
        return $this->getInput('uri') ?: parent::getURI();
    }

    public function collectData()
    {
        // The URI cannot be the mainpage (or anything related)
        switch (parse_url($this->getInput('uri'), PHP_URL_PATH)) {
            case null:
            case '/index.php':
                throwClientException('Provided URI is invalid!');
                break;
            default:
                break;
        }

        // Sanitize the URI (because else it won't work)
        $uri = rtrim($this->getInput('uri'), '/'); // No trailing slashes!

        // Forums might provide feeds, though that's optional *facepalm*
        // Let's check if there is a valid feed available
        $headers = get_headers($uri . '.xml');

        if ($headers[0] === 'HTTP/1.1 200 OK') { // Heureka! It's a valid feed!
            return $this->collectExpandableDatas($uri . '.xml');
        }

        // No valid feed, so do it the hard way
        $html = getSimpleHTMLDOM($uri);

        $limit = $this->getInput('limit');

        // Determine if this is a topic or a forum
        switch (true) {
            case $this->isTopic($html):
                $this->collectTopic($html, $limit);
                break;
            case $this->isForum($html):
                $this->collectForum($html);
                break;
            default:
                throwClientException('Unknown type!');
                break;
        }
    }

    private function isForum($html)
    {
        return !is_null($html->find('div[data-controller*=forums.front.forum.forumPage]', 0))
        || !is_null($html->find(static::FORUM_TYPE_TABLE_FILTER, 0));
    }

    private function isTopic($html)
    {
        return !is_null($html->find('div[data-controller*=core.front.core.commentFeed]', 0))
        || !is_null($html->find(static::TOPIC_TYPE_DIV, 0));
    }

    private function collectForum($html)
    {
        // There are multiple forum designs in use (depends on version?)
        // 1 - Uses an ordered list (based on https://invisioncommunity.com/forums)
        // 2 - Uses a table (based on https://onehallyu.com)

        switch (true) {
            case !is_null($html->find(static::FORUM_TYPE_LIST_FILTER, 0)):
                $this->collectForumList($html);
                break;
            case !is_null($html->find(static::FORUM_TYPE_TABLE_FILTER, 0)):
                $this->collectForumTable($html);
                break;
            default:
                throwClientException('Unknown forum format!');
                break;
        }
    }

    private function collectForumList($html)
    {
        foreach ($html->find(static::FORUM_TYPE_LIST_FILTER, 0)->children() as $row) {
            // Columns: Title, Statistics, Last modified
            $item = [];

            $item['uri'] = $row->find('a', 0)->href;
            $item['title'] = $row->find('a', 0)->title;
            $item['author'] = $row->find('a', 1)->innertext;
            $item['timestamp'] = strtotime($row->find('time', 0)->getAttribute('datetime'));

            $this->items[] = $item;
        }
    }

    private function collectForumTable($html)
    {
        foreach ($html->find(static::FORUM_TYPE_TABLE_FILTER, 0)->children() as $row) {
            // Columns: Icon, Content, Preview, Statistics, Last modified
            $item = [];

            // Skip header row
            if (!is_null($row->find('th', 0))) {
                continue;
            }

            $item['uri'] = $row->find('a', 0)->href;
            $item['title'] = $row->find('.title', 0)->plaintext;
            $item['timestamp'] = strtotime($row->find('[itemprop=dateCreated]', 0)->plaintext);

            $this->items[] = $item;
        }
    }

    private function collectTopic($html, $limit)
    {
        // There are multiple topic designs in use (depends on version?)
        // 1 - Uses articles (based on https://invisioncommunity.com/forums)
        // 2 - Uses divs (based on https://onehallyu.com)

        switch (true) {
            case !is_null($html->find(static::TOPIC_TYPE_ARTICLE, 0)):
                $this->collectTopicHistory($html, $limit, 'collectTopicArticle');
                break;
            case !is_null($html->find(static::TOPIC_TYPE_DIV, 0)):
                $this->collectTopicHistory($html, $limit, 'collectTopicDiv');
                break;
            default:
                throwClientException('Unknown topic format!');
                break;
        }
    }

    private function collectTopicHistory($html, $limit, $callback)
    {
        // Make sure the callback is valid!
        if (!method_exists($this, $callback)) {
            throwServerException('Unknown function (\'' . $callback . '\')!');
        }

        $next = null; // Holds the URI of the next page

        while (true) {
            $next = $this->$callback($html, is_null($next));

            if (is_null($next) || ($limit > 0 && count($this->items) >= $limit)) {
                break;
            }

            $html = getSimpleHTMLDOMCached($next);
        }

        // We might have more items than specified, remove excess
        $this->items = array_slice($this->items, 0, $limit);
    }

    private function collectTopicArticle($html, $firstrun = true)
    {
        $title = $html->find('h1.ipsType_pageTitle', 0)->plaintext;

        // Are we on last page?
        if ($firstrun && !is_null($html->find('.ipsPagination', 0))) {
            $last = $html->find('.ipsPagination_last a', 0)->{'data-page'};
            $active = $html->find('.ipsPagination_active a', 0)->{'data-page'};

            if ($active !== $last) {
                // Load last page into memory (cached)
                $html = getSimpleHTMLDOMCached($html->find('.ipsPagination_last a', 0)->href);
            }
        }

        foreach (array_reverse($html->find(static::TOPIC_TYPE_ARTICLE)) as $article) {
            $item = [];

            $item['uri'] = $article->find('time', 0)->parent()->href;
            $item['author'] = $article->find('aside a', 0)->plaintext;
            $item['title'] = $item['author'] . ' - ' . $title;
            $item['timestamp'] = strtotime($article->find('time', 0)->getAttribute('datetime'));

            $content = $article->find('[data-role=commentContent]', 0);
            $content = $this->scaleImages($content);
            $item['content'] = $this->fixContent($content);
            $item['enclosures'] = $this->findImages($article->find('[data-role=commentContent]', 0)) ?: null;

            $this->items[] = $item;
        }

        // Return whatever page comes next (previous, as we add in inverse order)
        // Do we have a previous page? (inactive means no)
        if (!is_null($html->find('li[class=ipsPagination_prev ipsPagination_inactive]', 0))) {
            return null; // No, or no more
        } elseif (!is_null($html->find('li[class=ipsPagination_prev]', 0))) {
            return $html->find('.ipsPagination_prev a', 0)->href;
        }

        return null;
    }

    private function collectTopicDiv($html, $firstrun = true)
    {
        $title = $html->find('h1.ipsType_pagetitle', 0)->plaintext;

        // Are we on last page?
        if ($firstrun && !is_null($html->find('.pagination', 0))) {
            $active = $html->find('li[class=page active]', 0)->plaintext;

            // There are two ways the 'last' page is displayed:
            // - With a distict 'last' button (only if there are enough pages)
            // - With a button for each page (use last button)
            if (!is_null($html->find('li.last', 0))) {
                $last = $html->find('li.last a', 0);
            } else {
                $last = $html->find('li[class=page] a', -1);
            }

            if ($active !== $last->plaintext) {
                // Load last page into memory (cached)
                $html = getSimpleHTMLDOMCached($last->href);
            }
        }

        foreach (array_reverse($html->find(static::TOPIC_TYPE_DIV)) as $article) {
            $item = [];

            $item['uri'] = $article->find('a[rel=bookmark]', 0)->href;
            $item['author'] = $article->find('.author', 0)->plaintext;
            $item['title'] = $item['author'] . ' - ' . $title;
            $item['timestamp'] = strtotime($article->find('.published', 0)->getAttribute('title'));

            $content = $article->find('[itemprop=commentText]', 0);
            $content = $this->scaleImages($content);
            $item['content'] = $this->fixContent($content);

            $item['enclosures'] = $this->findImages($article->find('.post_body', 0)) ?: null;

            $this->items[] = $item;
        }

        // Return whatever page comes next (previous, as we add in inverse order)
        // Do we have a previous page?
        if (!is_null($html->find('li.prev', 0))) {
            return $html->find('li.prev a', 0)->href;
        }

        return null;
    }

    /** Returns all images from the provide HTML DOM */
    private function findImages($html)
    {
        $images = [];

        foreach ($html->find('img') as $img) {
            $images[] = $img->src;
        }

        return $images;
    }

    /** Sets the maximum width and height for all images */
    private function scaleImages($html, $width = 400, $height = 400)
    {
        foreach ($html->find('img') as $img) {
            $img->style = "max-width: {$width}px; max-height: {$height}px;";
        }

        return $html;
    }

    /** Removes all unnecessary tags and adds formatting */
    private function fixContent($html)
    {
        // Restore quote highlighting
        foreach ($html->find('blockquote') as $quote) {
            $quote->style = <<<EOD
padding: 0px 15px;
border-width: 1px 1px 1px 2px;
border-style: solid;
border-color: #ededed #e8e8e8 #dbdbdb #666666;
background: #fbfbfb;
EOD;
        }

        // Remove unnecessary tags
        $content = strip_tags(
            $html->innertext,
            '<p><a><img><ol><ul><li><table><tr><th><td><strong><blockquote><br><hr><h>'
        );

        return $content;
    }
}
