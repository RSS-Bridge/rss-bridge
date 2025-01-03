<?php

class GoogleScholarBridge extends BridgeAbstract
{
    const NAME = 'Google Scholar';
    const URI = 'https://scholar.google.com/';
    const DESCRIPTION = 'Search for publications or follow authors on Google Scholar.';
    const MAINTAINER = 'nicholasmccarthy';
    const CACHE_TIMEOUT = 86400; // 24h

    const PARAMETERS = [
        'user' => [
            'userId' => [
                'name' => 'User ID',
                'exampleValue' => 'qc6CJjYAAAAJ',
                'required' => true
            ]
        ],
        'query' => [
            'q' => [
                'name' => 'Search Query',
                'title' => 'Search Query',
                'required' => true,
                'exampleValue' => 'machine learning'
            ],
            'cites' => [
                'name' => 'Cites',
                'required' => false,
                'default' => '',
                'exampleValue' => '1275980731835430123',
                'title' => 'Parameter defines unique ID for an article to trigger Cited By searches. Usage of cites
                will bring up a list of citing documents in Google Scholar. Example value: cites=1275980731835430123.
                Usage of cites and q parameters triggers search within citing articles.'
            ],
            'language' => [
                'name' => 'Language',
                'required' => false,
                'default' => '',
                'exampleValue' => 'en',
                'title' => 'Parameter defines the language to use for the Google Scholar search. '
            ],
            'minCitations' => [
                'name' => 'Minimum Citations',
                'required' => false,
                'type' => 'number',
                'default' => '0',
                'title' => 'Parameter defines the minimum number of citations in order for the results to be included.'
            ],
            'sinceYear' => [
                'name' => 'Since Year',
                'required' => false,
                'type' => 'number',
                'default' => '0',
                'title' => 'Parameter defines the year from which you want the results to be included.'
            ],
            'untilYear' => [
                'name' => 'Until Year',
                'required' => false,
                'type' => 'number',
                'default' => '0',
                'title' => 'Parameter defines the year until which you want the results to be included.'
            ],
            'sortBy' => [
                'name' => 'Sort By Date',
                'type' => 'checkbox',
                'default' => false,
                'title' => 'Parameter defines articles added in the last year, sorted by date. Alternatively sorts
                by relevance. This overrides Since-Until Year values.',
            ],
            'includePatents' => [
                'name' => 'Include Patents',
                'type' => 'checkbox',
                'default' => false,
                'title' => 'Include Patents',
            ],
            'includeCitations' => [
                'name' => 'Include Citations',
                'type' => 'checkbox',
                'default' => true,
                'title' => 'Parameter defines whether you would like to include citations or not.',
            ],
            'reviewArticles' => [
                'name' => 'Only Review Articles',
                'type' => 'checkbox',
                'default' => false,
                'title' => 'Parameter defines whether you would like to show only review articles or not (these
                articles consist of topic reviews, or discuss the works or authors you have searched for).',
            ],
            'numResults' => [
                'name' => 'Number of Results (max 20)',
                'required' => false,
                'type' => 'number',
                'default' => 10,
                'exampleValue' => 10,
                'title' => 'Number of results to return'
            ]
        ],
    ];


    public function getIcon()
    {
        return 'https://scholar.google.com/favicon.ico';
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'user':
                $userId = $this->getInput('userId');
                $uri = self::URI . '/citations?hl=en&view_op=list_works&sortby=pubdate&user=' . $userId;
                $html = getSimpleHTMLDOM($uri);

                $publications = $html->find('tr[class="gsc_a_tr"]');

                foreach ($publications as $publication) {
                    $articleUrl = self::URI . htmlspecialchars_decode($publication->find('a[class="gsc_a_at"]', 0)->href);
                    $articleTitle = $publication->find('a[class="gsc_a_at"]', 0)->plaintext;

                    # fetch the article itself to extract rest of content
                    $contentArticle = getSimpleHTMLDOMCached($articleUrl);
                    $articleEntries = $contentArticle->find('div[class="gs_scl"]');

                    $articleDate = '';
                    $articleAbstract = '';
                    $articleAuthor = '';
                    $content = '';

                    foreach ($articleEntries as $entry) {
                        $field = $entry->find('div[class="gsc_oci_field"]', 0)->plaintext;
                        $value = $entry->find('div[class="gsc_oci_value"]', 0)->plaintext;

                        if ($field == 'Publication date') {
                            $articleDate = $value;
                        } elseif ($field == 'Description') {
                            $articleAbstract = $value;
                        } elseif ($field == 'Authors') {
                            $articleAuthor = $value;
                        } elseif ($field == 'Scholar articles' || $field == 'Total citations') {
                            continue;
                        } else {
                            $content = $content . $field . ': ' . $value . '<br><br>';
                        }
                    }

                    $content = $content . $articleAbstract;

                    $item = [];

                    $item['title'] = $articleTitle;
                    $item['uri'] = $articleUrl;
                    $item['timestamp'] = strtotime($articleDate);
                    $item['author'] = $articleAuthor;
                    $item['content'] = $content;

                    $this->items[] = $item;

                    if (count($this->items) >= 10) {
                        break;
                    }
                }
                break;
            case 'query':
                $query = urlencode($this->getInput('q'));
                $cites = $this->getInput('cites');
                $language = $this->getInput('language');
                $sinceYear = $this->getInput('sinceYear');
                $untilYear = $this->getInput('untilYear');
                $minCitations = (int)$this->getInput('minCitations');
                $includeCitations = $this->getInput('includeCitations');
                $includePatents = $this->getInput('includePatents');
                $reviewArticles = $this->getInput('reviewArticles');
                $sortBy = $this->getInput('sortBy');
                $numResults = $this->getInput('numResults');

                # Build URI
                $uri = self::URI . 'scholar?q=' . $query;
                $uri .= $sinceYear != 0 ? '&as_ylo=' . $sinceYear : '';
                $uri .= $untilYear != 0 ? '&as_yhi=' . $untilYear : '';
                $uri .= $language != '' ? '&hl=' . $language : '';
                $uri .= $includePatents ? '&as_vis=7' : '&as_vis=0';
                $uri .= $includeCitations ? '&as_vis=0' : ($includePatents ? '&as_vis=1' : '');
                $uri .= $reviewArticles ? '&as_rr=1' : '';
                $uri .= $sortBy ? '&scisbd=1' : '';
                $uri .= $numResults ? '&num=' . $numResults : '';

                $html = getSimpleHTMLDOM($uri);

                $publications = $html->find('div[class="gs_r gs_or gs_scl"]');

                foreach ($publications as $publication) {
                    $articleTitleElement = $publication->find('h3[class="gs_rt"]', 0);
                    $articleUrl = $articleTitleElement->find('a', 0)->href;
                    $articleTitle = $articleTitleElement->plaintext;

                    // Break the loop if 'Check for Updates' is found in the article title
                    if (strpos($articleTitle, 'Check for updates') !== false) {
                        break;
                    }

                    $articleDateElement = $publication->find('div[class="gs_a"]', 0);
                    $articleDate = $articleDateElement ? $articleDateElement->plaintext : '';

                    $articleAbstractElement = $publication->find('div[class="gs_rs"]', 0);
                    $articleAbstract = $articleAbstractElement ? $articleAbstractElement->plaintext : '';

                    $articleAuthorElement = $publication->find('div[class="gs_a"]', 0);
                    $articleAuthor = $articleAuthorElement ? $articleAuthorElement->plaintext : '';

                    $bottomRowElement = $publication->find('div[class="gs_fl"]', 0);

                    $item = [
                        'title' => $articleTitle,
                        'uri' => $articleUrl,
                        'timestamp' => strtotime($articleDate),
                        'author' => $articleAuthor,
                        'content' => $articleAbstract
                    ];

                    switch ($this->queriedContext) {
                        case 'user':
                            $this->items[] = $item;
                            break;
                        case 'query':
                            $citedBy = 0;
                            if ($bottomRowElement) {
                                $anchorTags = $bottomRowElement->find('a');
                                foreach ($anchorTags as $anchorTag) {
                                    if (strpos($anchorTag->plaintext, 'Cited') !== false) {
                                        $parts = explode('Cited by ', $anchorTag->plaintext);
                                        if (isset($parts[1])) {
                                            $citedBy = (int)$parts[1];
                                        }
                                        break;
                                    }
                                }
                            }
                            if ($citedBy >= $minCitations) {
                                $this->items[] = $item;
                            }
                            break;
                    }
                }
                break;
        }
    }
}
