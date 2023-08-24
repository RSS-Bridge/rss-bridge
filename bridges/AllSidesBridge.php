<?php

class AllSidesBridge extends BridgeAbstract
{
    const NAME = 'AllSides';
    const URI = 'https://www.allsides.com';
    const DESCRIPTION = 'Balanced news and media bias ratings.';
    const MAINTAINER = 'Oliver Nutter';
    const PARAMETERS = [
        'global' => [
            'limit' => [
                'name' => 'Number of posts to return',
                'type' => 'number',
                'defaultValue' => 10,
                'required' => false,
                'title' => 'Zero or negative values return all posts (ignored if not fetching full article)',
            ],
            'fetch' => [
                'name' => 'Fetch full article content',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
            ],
        ],
        'Headline Roundups' => [],
    ];

    private const ROUNDUPS_URI = self::URI . '/headline-roundups';

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Headline Roundups':
                $index = getSimpleHTMLDOM(self::ROUNDUPS_URI);
                defaultLinkTo($index, self::ROUNDUPS_URI);
                $entries = $index->find('table.views-table > tbody > tr');

                $limit = (int) $this->getInput('limit');
                $fetch = (bool) $this->getInput('fetch');

                if ($limit > 0 && $fetch) {
                    $entries = array_slice($entries, 0, $limit);
                }

                foreach ($entries as $entry) {
                    $item = [
                        'title' => $entry->find('.views-field-name', 0)->text(),
                        'uri' => $entry->find('a', 0)->href,
                        'timestamp' => $entry->find('.date-display-single', 0)->content,
                        'author' => 'AllSides Staff',
                    ];

                    if ($fetch) {
                        $article = getSimpleHTMLDOMCached($item['uri']);
                        defaultLinkTo($article, $item['uri']);

                        $item['content'] = $article->find('.story-id-page-description', 0);

                        foreach ($article->find('.page-tags a') as $tag) {
                            $item['categories'][] = $tag->text();
                        }
                    }

                    $this->items[] = $item;
                }
                break;
        }
    }

    public function getName()
    {
        if ($this->queriedContext) {
            return self::NAME . " - {$this->queriedContext}";
        }
        return self::NAME;
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Headline Roundups':
                return self::ROUNDUPS_URI;
        }
        return self::URI;
    }
}
