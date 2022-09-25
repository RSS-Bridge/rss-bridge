<?php

declare(strict_types=1);

final class UsenixBridge extends BridgeAbstract
{
    const NAME = 'USENIX';
    const URI = 'https://www.usenix.org/publications';
    const DESCRIPTION = 'Digital publications from USENIX (usenix.org)';
    const MAINTAINER = 'dvikan';
    const PARAMETERS = [
        'USENIX ;login:' => [
        ],
    ];

    public function collectData()
    {
        if ($this->queriedContext === 'USENIX ;login:') {
            $this->collectLoginOnlineItems();
            return;
        }
        returnClientError('Illegal Context');
    }

    private function collectLoginOnlineItems(): void
    {
        $url = 'https://www.usenix.org/publications/loginonline';
        $dom = getSimpleHTMLDOMCached($url);
        $items = $dom->find('div.view-content > div');

        foreach ($items as $item) {
            $title = $item->find('.views-field-title > span', 0);
            $author = $item->find('.views-field-pseudo-author-list > span.field-content', 0);
            $relativeUrl = $item->find('.views-field-nothing-1 > span > a', 0);
            $uri = sprintf('https://www.usenix.org%s', $relativeUrl->href);
            // June 2, 2022
            $createdAt = $item->find('div.views-field-field-lv2-publication-date > div > span', 0);

            $item = [
                'title' => $title->innertext,
                'author' => strstr($author->plaintext, ',', true) ?: $author->plaintext,
                'uri' => $uri,
                'timestamp' => $createdAt->innertext,
            ];

            $this->items[] = array_merge($item, $this->getItemContent($uri));
        }
    }

    private function getItemContent(string $uri): array
    {
        $html = getSimpleHTMLDOMCached($uri);
        $content = $html->find('.paragraphs-items-full', 0)->innertext;
        $extra = $html->find('fieldset', 0);
        if (!empty($extra)) {
            $content .= $extra->innertext;
        }

        $tags = [];
        foreach ($html->find('.field-name-field-lv2-tags div.field-item') as $tag) {
            $tags[] = $tag->plaintext;
        }

        return [
            'content' => $content,
            'categories' => $tags
        ];
    }
}
