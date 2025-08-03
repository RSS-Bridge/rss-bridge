<?php

class WaggaCouncilBridge extends BridgeAbstract
{
    const NAME = 'Wagga Wagga Council';
    const URI = 'https://news.wagga.nsw.gov.au/';
    const DESCRIPTION = 'Wagga Wagga Council updates';
    const MAINTAINER = 'Scrub000';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        [
            'section' => [
                'name' => 'Section',
                'type' => 'list',
                'values' => [
                    'Council' => 'council',
                    'Community' => 'community',
                    'Projects & Works' => 'projects-and-works',
                    'Arts & Culture' => 'arts-and-culture',
                    'Environment' => 'environment',
                    'Events & Tourism' => 'events-and-tourism',
                    'Parks & Recreation' => 'parks-and-recreation',
                ],
                'defaultValue' => 'council',
            ],
        ]
    ];

    public function getURI(): string
    {
        $section = $this->getInput('section') ?: 'council';
        return urljoin(self::URI, $section);
    }

    public function collectData(): void
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('div.container') as $container) {
            $titleElement = $container->find('h5', 0);
            $linkElement = $container->find('a', 0);
            $timeElement = $container->find('small.text-muted', 0);

            if (!$titleElement || !$linkElement || !$timeElement) {
                continue;
            }

            $title = trim($titleElement->plaintext);
            $uri = urljoin(self::URI, $linkElement->href);
            $timestamp = strtotime(str_replace('Published: ', '', $timeElement->plaintext));

            // Load full article
            $articleHtml = getSimpleHTMLDOM($uri);
            $articleContent = '';

            if ($articleHtml) {
                $article = $articleHtml->find('article.article', 0);
                if ($article) {
                    // Remove uneeded content
                    $selectorsToRemove = [
                        'button',
                        'nav',
                        '.visually-hidden',
                        '.carousel-control-prev',
                        '.carousel-control-next',
                        '.article__heading',
                        '.article__badge',
                        'p.text-muted',
                    ];

                    foreach ($selectorsToRemove as $sel) {
                        foreach ($article->find($sel) as $el) {
                            $el->outertext = '';
                        }
                    }

                    foreach ($article->find('iframe') as $iframe) {
                        $src = $iframe->getAttribute('src');
                        $iframe->outertext = '<p><a href="' . htmlspecialchars($src) . '">Embedded content: ' . htmlspecialchars($src) . '</a></p>';
                    }

                    // Enhance list rendering
                    foreach ($article->find('ul') as $ul) {
                        $ul->style = 'margin-left: 1em; padding-left: 1em;';
                    }
                    foreach ($article->find('li') as $li) {
                        $li->innertext = '• ' . $li->innertext;
                    }

                    foreach ($article->children() as $node) {
                        // Skip <p> that contains <figure> to avoid duplication
                        if ($node->tag === 'p' && $node->find('figure', 0)) {
                            continue;
                        }
                        $articleContent .= $node->outertext;
                    }
                }
            }

            $this->items[] = [
                'title' => $title,
                'uri' => $uri,
                'author' => 'Wagga Wagga City Council',
                'timestamp' => $timestamp,
                'content' => $articleContent,
            ];
        }
    }
}
