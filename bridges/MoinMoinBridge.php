<?php

class MoinMoinBridge extends BridgeAbstract
{
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'MoinMoin Bridge';
    const URI = 'https://moinmo.in';
    const DESCRIPTION = 'Generates feeds for pages of a MoinMoin (compatible) wiki';
    const PARAMETERS = [
        [
            'source' => [
                'name' => 'Source',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert wiki page URI (e.g.: https://moinmo.in/MoinMoin)',
                'exampleValue' => 'https://moinmo.in/MoinMoin'
            ],
            'separator' => [
                'name' => 'Separator',
                'type' => 'list',
                'requied' => true,
                'title' => 'Defines the separtor for splitting content into feeds',
                'defaultValue' => 'h2',
                'values' => [
                    'Header (h1)' => 'h1',
                    'Header (h2)' => 'h2',
                    'Header (h3)' => 'h3',
                    'List element (li)' => 'li',
                    'Anchor (a)' => 'a'
                ]
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Number of items to return (from top)',
                'defaultValue' => -1
            ],
            'content' => [
                'name' => 'Content',
                'type' => 'list',
                'required' => false,
                'title' => 'Defines how feed contents are build',
                'defaultValue' => 'separator',
                'values' => [
                    'By separator' => 'separator',
                    'Follow link (only for anchor)' => 'follow',
                    'None' => 'none'
                ]
            ]
        ]
    ];

    private $title = '';

    public function collectData()
    {
        /* MoinMoin uses a rather unpleasent representation of HTML. Instead of
         * using tags like <article/>, <navigation/>, <header/>, etc... it uses
         * <div/>, <span/> and <p/>. Also each line is literaly identified via
         * IDs. The only way to distinguish content is via headers, though not
         * in all cases.
         *
         * Example (indented for the sake of readability):
         * ...
         * <span class="anchor" id="line-1"></span>
         * <span class="anchor" id="line-2"></span>
         * <span class="anchor" id="line-3"></span>
         * <span class="anchor" id="line-4"></span>
         * <span class="anchor" id="line-5"></span>
         * <span class="anchor" id="line-6"></span>
         * <span class="anchor" id="line-7"></span>
         * <span class="anchor" id="line-8"></span>
         * <span class="anchor" id="line-9"></span>
         *   <p class="line867">MoinMoin is a Wiki software implemented in
         *     <a class="interwiki" href="/Python" title="MoinMoin">Python</a>
         *   and distributed as Free Software under
         *     <a class="interwiki" href="/GPL" title="MoinMoin">GNU GPL license</a>.
         * ...
         */
        $html = getSimpleHTMLDOM($this->getInput('source'));

        // Some anchors link to local sites or local IDs (both don't work well
        // in feeds)
        $html = $this->fixAnchors($html);

        $this->title = $html->find('title', 0)->innertext . ' | ' . self::NAME;

        // Here we focus on simple author and timestamp information from the given
        // page. Later we update this information in case the anchor is followed.
        $author = $this->findAuthor($html);
        $timestamp = $this->findTimestamp($html);

        $sections = $this->splitSections($html);

        foreach ($sections as $section) {
            $item = [];

            $item['uri'] = $this->findSectionAnchor($section[0]);

            switch ($this->getInput('content')) {
                case 'none': // Do not return any content
                    break;
                case 'follow': // Follow the anchor
                    // We can only follow anchors (use default otherwise)
                    if ($this->getInput('separator') === 'a') {
                        $content = $this->followAnchor($item['uri']);

                        // Return only actual content
                        $item['content'] = $content->find('div#page', 0)->innertext;

                        // Each page could have its own author and timestamp
                        $author = $this->findAuthor($content);
                        $timestamp = $this->findTimestamp($content);

                        break;
                    }
                    // fall-through
                case 'separator':
                default: // Use contents from the current page
                    $item['content'] = $this->cleanArticle($section[2]);
            }

            if (!is_null($author)) {
                $item['author'] = $author;
            }
            if (!is_null($timestamp)) {
                $item['timestamp'] = $timestamp;
            }
            $item['title'] = strip_tags($section[1]);

            // Skip items with empty title
            if (empty(trim($item['title']))) {
                continue;
            }

            $this->items[] = $item;

            if (
                $this->getInput('limit') > 0
                && count($this->items) >= $this->getInput('limit')
            ) {
                break;
            }
        }
    }

    public function getName()
    {
        return $this->title ?: parent::getName();
    }

    public function getURI()
    {
        return $this->getInput('source') ?: parent::getURI();
    }

    /**
     * Splits the html into sections.
     *
     * Returns an array with one element per section. Each element consists of:
     * [0] The entire section
     * [1] The section title
     * [2] The section content
     */
    private function splitSections($html)
    {
        $content = $html->find('div#page', 0)->innertext
            or throwServerException('Unable to find <div id="page"/>!');

        $sections = [];

        $regex = implode(
            '',
            [
                "\<{$this->getInput('separator')}.+?(?=\>)\>",
                "(.+?)(?=\<\/{$this->getInput('separator')}\>)",
                "\<\/{$this->getInput('separator')}\>",
                "(.+?)((?=\<{$this->getInput('separator')})|(?=\<div\sid=\"pagebottom\")){1}"
            ]
        );

        preg_match_all(
            '/' . $regex . '/m',
            $content,
            $sections,
            PREG_SET_ORDER
        );

        // Some pages don't use headers, return page as one feed
        if (count($sections) === 0) {
            return [
                [
                    $content,
                    $html->find('title', 0)->innertext,
                    $content
                ]
            ];
        }

        return $sections;
    }

    /**
     * Returns the anchor for a given section
     */
    private function findSectionAnchor($section)
    {
        $html = str_get_html($section);

        // For IDs
        $anchor = $html->find($this->getInput('separator') . '[id=]', 0);
        if (!is_null($anchor)) {
            return $this->getInput('source') . '#' . $anchor->id;
        }

        // For actual anchors
        $anchor = $html->find($this->getInput('separator') . '[href=]', 0);
        if (!is_null($anchor)) {
            return $anchor->href;
        }

        // Nothing found
        return $this->getInput('source');
    }

    /**
     * Returns the author
     *
     * Notice: Some pages don't provide author information
     */
    private function findAuthor($html)
    {
        /* Example:
         * <p id="pageinfo" class="info" dir="ltr" lang="en">MoinMoin: LocalSpellingWords
         * (last edited 2017-02-16 15:36:31 by <span title="??? @ hosted-by.leaseweb.com
         * [178.162.199.143]">hosted-by</span>)</p>
        */
        $pageinfo = $html->find('[id="pageinfo"]', 0);

        if (is_null($pageinfo)) {
            return null;
        } else {
            $author = $pageinfo->find('[title=]', 0);
            if (is_null($author)) {
                return null;
            } else {
                return trim(explode('@', $author->title)[0]);
            }
        }
    }

    /**
     * Returns the time of last edit
     *
     * Notice: Some pages don't provide this information
     */
    private function findTimestamp($html)
    {
        // See example of findAuthor()
        $pageinfo = $html->find('[id="pageinfo"]', 0);

        if (is_null($pageinfo)) {
            return null;
        } else {
            $timestamp = $pageinfo->innertext;
            $matches = [];
            preg_match('/.+?(?=\().+?(?=\d)([0-9\-\s\:]+)/m', $pageinfo, $matches);
            return strtotime($matches[1]);
        }
    }

    /**
     * Returns the original HTML with all anchors fixed (makes relative anchors
     * absolute)
     */
    private function fixAnchors($html, $source = null)
    {
        $source = $source ?: $this->getURI();

        foreach ($html->find('a') as $anchor) {
            switch (substr($anchor->href, 0, 1)) {
                case 'h': // http or https, no actions required
                    break;
                case '/': // some relative path
                    $anchor->href = $this->findDomain($source) . $anchor->href;
                    break;
                case '#': // it's an ID
                default: // probably something like ? or &, skip empty ones
                    if (!isset($anchor->href)) {
                        break;
                    }
                    $anchor->href = $source . $anchor->href;
            }
        }

        return $html;
    }

    /**
     * Loads the full article of a given anchor (if the anchor is from the same
     * wiki domain)
     */
    private function followAnchor($anchor)
    {
        if (strrpos($anchor, $this->findDomain($this->getInput('source')) === false)) {
            return null;
        }

        $html = getSimpleHTMLDOMCached($anchor);
        if (!$html) { // Cannot load article
            return null;
        }

        return $this->fixAnchors($html, $anchor);
    }

    /**
     * Finds the domain for a given URI
     */
    private function findDomain($uri)
    {
        $matches = [];
        preg_match('/(http[s]{0,1}:\/\/.+?(?=\/))/', $uri, $matches);
        return $matches[1];
    }

    /* This function is a copy from CNETBridge */
    private function stripWithDelimiters($string, $start, $end)
    {
        while (strpos($string, $start) !== false) {
            $section_to_remove = substr($string, strpos($string, $start));
            $section_to_remove = substr($section_to_remove, 0, strpos($section_to_remove, $end) + strlen($end));
            $string = str_replace($section_to_remove, '', $string);
        }

        return $string;
    }

    /* This function is based on CNETBridge */
    private function cleanArticle($article_html)
    {
        $article_html = $this->stripWithDelimiters($article_html, '<script', '</script>');
        return $article_html;
    }
}
