<?php

class SeznamZpravyBridge extends BridgeAbstract
{
    const NAME = 'Seznam Zprávy Bridge';
    const URI = 'https://seznamzpravy.cz';
    const DESCRIPTION = 'Returns newest stories from Seznam Zprávy';
    const MAINTAINER = 'thezeroalpha';
    const PARAMETERS = [
        'By Author' => [
            'author' => [
                'name' => 'Author String',
                'type' => 'text',
                'required' => true,
                'title' => 'The dash-separated author string, as shown in the URL bar.',
                'pattern' => '[a-z]+-[a-z]+-[0-9]+',
                'exampleValue' => 'radek-nohl-1'
            ],
        ]
    ];

    private $feedName;

    public function getName()
    {
        if (isset($this->feedName)) {
            return $this->feedName;
        }
        return parent::getName();
    }

    public function collectData()
    {
        $ONE_DAY = 86500;
        switch ($this->queriedContext) {
            case 'By Author':
                $url = 'https://www.seznamzpravy.cz/autor/';
                $selectors = [
                'breadcrumbs' => 'div[data-dot=ogm-breadcrumb-navigation]',
                'articleList' => 'ul.ogm-document-timeline-page li article[data-dot=mol-timeline-item]',
                'articleTitle' => 'a[data-dot=mol-article-card-title]',
                'articleDM' => 'span.mol-formatted-date__date',
                'articleTime' => 'span.mol-formatted-date__time',
                'articleContent' => 'div[data-dot=ogm-article-content]',
                'articleImage' => 'div[data-dot=ogm-main-media] img',
                'articleParagraphs' => 'div[data-dot=mol-paragraph]'
                ];

                $html = getSimpleHTMLDOMCached($url . $this->getInput('author'), $ONE_DAY);
                $mainBreadcrumbs = $html->find($selectors['breadcrumbs'], 0)
                or throwServerException('Could not get breadcrumbs for: ' . $this->getURI());

                $author = $mainBreadcrumbs->last_child()->plaintext
                or throwServerException('Could not get author for: ' . $this->getURI());

                $this->feedName = $author . ' - Seznam Zprávy';

                $articles = $html->find($selectors['articleList'])
                or throwServerException('Could not find articles for: ' . $this->getURI());

                foreach ($articles as $article) {
                    // Get article URL
                    $titleLink = $article->find($selectors['articleTitle'], 0)
                    or throwServerException('Could not find title for: ' . $this->getURI());
                    $articleURL = $titleLink->href;

                    $articleContentHTML = getSimpleHTMLDOMCached($articleURL, $ONE_DAY);

                    // Article header image
                    $articleImageElem = $articleContentHTML->find($selectors['articleImage'], 0);

                    // Article text content
                    $contentElem = $articleContentHTML->find($selectors['articleContent'], 0)
                    or throwServerException('Could not get article content for: ' . $articleURL);
                    $contentParagraphs = $contentElem->find($selectors['articleParagraphs'])
                    or throwServerException('Could not find paragraphs for: ' . $articleURL);

                    // If the article has an image, put that image at the start
                    $contentInitialValue = isset($articleImageElem) ? $articleImageElem->outertext : '';
                    $contentText = array_reduce($contentParagraphs, function ($s, $elem) {
                        return $s . $elem->innertext;
                    }, $contentInitialValue);

                    // Article categories
                    $breadcrumbsElem = $articleContentHTML->find($selectors['breadcrumbs'], 0)
                        or throwServerException('Could not find breadcrumbs for: ' . $articleURL);
                    $breadcrumbs = $breadcrumbsElem->children();
                    $numBreadcrumbs = count($breadcrumbs);
                    $categories = [];
                    foreach ($breadcrumbs as $cat) {
                        if (--$numBreadcrumbs <= 0) {
                            break;
                        }
                        $categories[] = trim($cat->plaintext);
                    }

                    // Article date & time
                    $articleTimeElem = $article->find($selectors['articleTime'], 0)
                    or throwServerException('Could not find article time for: ' . $articleURL);
                    $articleTime = $articleTimeElem->plaintext;

                    $articleDMElem = $article->find($selectors['articleDM'], 0);
                    if (isset($articleDMElem)) {
                        $articleDMText = $articleDMElem->plaintext;
                    } else {
                        // If there is no date but only a time, the article was published today
                        $articleDMText = date('d.m.');
                    }
                    $articleDMY = preg_replace('/[^0-9\.]/', '', $articleDMText) . date('Y');

                    // Add article to items, potentially with header image as enclosure
                    $item = [
                    'title' => $titleLink->plaintext,
                    'uri' => $titleLink->href,
                    'timestamp' => strtotime($articleDMY . ' ' . $articleTime),
                    'author' => $author,
                    'content' => $contentText,
                    'categories' => $categories
                    ];
                    if (isset($articleImageElem)) {
                        $item['enclosures'] = ['https:' . $articleImageElem->src];
                    }
                    $this->items[] = $item;
                }
                break;
        }
        $this->items[] = $item;
    }
}
