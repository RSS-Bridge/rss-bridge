<?php

class KernelBugTrackerBridge extends BridgeAbstract
{
    const NAME = 'Kernel Bug Tracker';
    const URI = 'https://bugzilla.kernel.org';
    const DESCRIPTION = 'DEPRECATED: Use BugzillaBridge instead.
Returns feeds for bug comments';
    const MAINTAINER = 'logmanoriginal';
    const PARAMETERS = [
        'Bug comments' => [
            'id' => [
                'name' => 'Bug tracking ID',
                'type' => 'number',
                'required' => true,
                'title' => 'Insert bug tracking ID',
                'exampleValue' => 121241
            ],
            'limit' => [
                'name' => 'Number of comments to return',
                'type' => 'number',
                'required' => false,
                'title' => 'Specify number of comments to return',
                'defaultValue' => -1
            ],
            'sorting' => [
                'name' => 'Sorting',
                'type' => 'list',
                'required' => false,
                'title' => 'Defines the sorting order of the comments returned',
                'defaultValue' => 'of',
                'values' => [
                    'Oldest first' => 'of',
                    'Latest first' => 'lf'
                ]
            ]
        ]
    ];

    private $bugid = '';
    private $bugdesc = '';

    public function getIcon()
    {
        return self::URI . '/images/favicon.ico';
    }

    public function collectData()
    {
        $limit = $this->getInput('limit');
        $sorting = $this->getInput('sorting');

        // We use the print preview page for simplicity
        $html = getSimpleHTMLDOMCached(
            $this->getURI() . '&format=multiple',
            86400,
            null,
            null,
            true,
            true,
            DEFAULT_TARGET_CHARSET,
            false, // Do NOT remove line breaks
            DEFAULT_BR_TEXT,
            DEFAULT_SPAN_TEXT
        );

        if ($html === false) {
            returnServerError('Failed to load page!');
        }

        $html = defaultLinkTo($html, self::URI);

        // Store header information into private members
        $this->bugid = $html->find('#bugzilla-body', 0)->find('a', 0)->innertext;
        $this->bugdesc = $html->find('table.bugfields', 0)->find('tr', 0)->find('td', 0)->innertext;

        // Get and limit comments
        $comments = $html->find('div.bz_comment');

        if ($limit > 0 && count($comments) > $limit) {
            $comments = array_slice($comments, count($comments) - $limit, $limit);
        }

        // Order comments
        switch ($sorting) {
            case 'lf':
                $comments = array_reverse($comments, true);
                // fall-through
            case 'of':
                // fall-through
            default: // Nothing to do, keep original order
        }

        foreach ($comments as $comment) {
            $comment = $this->inlineStyles($comment);

            $item = [];
            $item['uri'] = $this->getURI() . '#' . $comment->id;
            $item['author'] = $comment->find('span.bz_comment_user', 0)->innertext;
            $item['title'] = $comment->find('span.bz_comment_number', 0)->find('a', 0)->innertext;
            $item['timestamp'] = strtotime($comment->find('span.bz_comment_time', 0)->innertext);
            $item['content'] = $comment->find('pre.bz_comment_text', 0)->innertext;

            // Fix line breaks (they use LF)
            $item['content'] = str_replace("\n", '<br>', $item['content']);

            // Fix relative URIs
            $item['content'] = $item['content'];

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Bug comments':
                return parent::getURI()
                . '/show_bug.cgi?id='
                . $this->getInput('id');
                break;
            default:
                return parent::getURI();
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Bug comments':
                return 'Bug '
                . $this->bugid
                . ' tracker for '
                . $this->bugdesc
                . ' - '
                . parent::getName();
                break;
            default:
                return parent::getName();
        }
    }

    /**
     * Adds styles as attributes to tags with known classes
     *
     * @param object $html A simplehtmldom object
     * @return object Returns the original object with styles added as
     * attributes.
     */
    private function inlineStyles($html)
    {
        foreach ($html->find('.bz_obsolete') as $element) {
            $element->style = 'text-decoration:line-through;';
        }

        return $html;
    }
}
