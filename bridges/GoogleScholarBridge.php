<?php

class GoogleScholarBridge extends BridgeAbstract
{
    const NAME = 'Google Scholar v2';
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
                'default' => '',
                'title' => 'Parameter defines the year from which you want the results to be included.'
            ],
            'untilYear' => [
                'name' => 'Until Year',
                'required' => false,
                'type' => 'number',
                'default' => '',
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

    public function collectData()
    {
        switch ($this->queriedContext)
        {
            case 'user':
                $html = getSimpleHTMLDOM($this->getUserURI()) or returnServerError('Could not fetch Google Scholar data.');

                $publications = $html->find('tr[class="gsc_a_tr"]');

                foreach ($publications as $publication) {
                    $articleUrl = self::URI . htmlspecialchars_decode($publication->find('a[class="gsc_a_at"]', 0)->href);
                    $articleTitle = $publication->find('a[class="gsc_a_at"]', 0)->plaintext;

                    // Break the loop if 'Check for Updates' is found in the article title
                    if (strpos($articleTitle, 'Check for updates') !== false) {
                        break;
                    }

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
                $html = getSimpleHTMLDOM($this->getQueryURI()) or returnServerError('Could not fetch Google Scholar data.');

                $publications = $html->find('div[class="gs_r gs_or gs_scl"]');
                $minCitations = (int)$this->getInput('minCitations');

                foreach ($publications as $publication) {
                    $articleTitleElement = $publication->find('h3[class="gs_rt"]', 0);
                    $articleUrl = $articleTitleElement->find('a', 0)->href;
                    $articleTitle = $articleTitleElement->plaintext;

                    $articleDateElement = $publication->find('div[class="gs_a"]', 0);
                    $articleDate = $articleDateElement ? $articleDateElement->plaintext : '';
                    $timeStamp = strtotime($articleDate) ?? '';

                    $articleAbstractElement = $publication->find('div[class="gs_rs"]', 0);
                    $articleAbstract = $articleAbstractElement ? $articleAbstractElement->plaintext : '';

                    $articleAuthorElement = $publication->find('div[class="gs_a"]', 0);
                    $articleAuthor = $articleAuthorElement ? $articleAuthorElement->plaintext : '';

                    $item = [
                        'title' => $articleTitle,
                        'uri' => $articleUrl,
                        'timestamp' => $timeStamp,
                        'author' => $articleAuthor,
                        'content' => $articleAbstract
                    ];

                    $citeRowDiv = $publication->find('div[class="gs_fl gs_flb"]', 0);

                    if ($citeRowDiv) {
                        $citedBy = 0;
                        foreach ($citeRowDiv->find('a') as $anchorTag) {
                            if (strpos($anchorTag->plaintext, 'Cited') !== false) {
                                $parts = explode('Cited by ', $anchorTag->plaintext);
                                if (isset($parts[1])) {
                                    $citedBy = (int)$parts[1];
                                }
                                break;
                            }
                        }
                        if ($citedBy >= $minCitations) {
                            $this->items[] = $item;
                        }
                    }
                    else {
                        $this->items[] = $item;
                    }
                break;
            } 
        }
    }

    public function getIcon()
    {
        return 'https://scholar.google.com/favicon.ico';
    }

    public function getUserURI()
    {
        $queryParameters = [
            'hl'      => 'en',
            'view_op' => 'list_works', 
            'sortby'  => 'pubdate',
            'user'    => $this->getInput('userId'),            
        ];
        return sprintf('https://scholar.google.com/citations?%s', http_build_query($queryParameters));
    }

    public function getQueryURI()
    {
        $queryParameters = [
            'q'      => $this->getInput('q'),
            'as_ylo' => $this->getInput('sinceYear'),
            'as_yhi' => $this->getInput('untilYear'),
            'hl'     => $this->getInput('language'),
            'as_sdt' => $this->getInput('includePatents') ? '7' : '0',
            'as_vis' => $this->getInput('includeCitations') ? '0' : '1',
            'as_rr'  => $this->getInput('reviewArticles') ? : '1': '0',
            'scisbd'  => $this->getInput('sortBy') ? '1' : '',
            'num'    => $this->getInput('numResults')
        ];

        $queryParameters = array_filter($queryParameters, function($value) {
            return $value !== null && $value !== '';
        });

        return sprintf('https://scholar.google.com/scholar?%s', http_build_query($queryParameters));
    }
}