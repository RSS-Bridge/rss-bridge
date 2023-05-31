 --<?php

class GoogleScholarV2Bridge extends BridgeAbstract
{
    const NAME = 'Google Scholar v2';
    const URI = 'https://scholar.google.com/';
    const DESCRIPTION = 'Search for scientific publications on Google Scholar.';
    const MAINTAINER = 'nicholas.mccarthy@github.com';
    const CACHE_TIMEOUT = 86400; // 24h

    const PARAMETERS = [

        'query' => [
	        'q' => [
	        	'name' => 'Search Query',
	            'title' => 'Search Query',           
	            'required' => true,
	            'exampleValue' => 'machine learning'
	        ]
        ],
        'global' => [
        	'cites' => [
                'name' => 'Cites',
                'required' => false,
                'default' => '',
                'exampleValue' => '1275980731835430123',
                'title' => 'Parameter defines unique ID for an article to trigger Cited By searches. Usage of cites will bring up a list of citing documents in Google Scholar. Example value: cites=1275980731835430123. Usage of cites and q parameters triggers search within citing articles.'
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
                'title' => 'Parameter defines articles added in the last year, sorted by date. Alternatively sorts by relevance. This overrides Since-Until Year values.',
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
                'title' => 'Parameter defines whether you would like to show only review articles or not (these articles consist of topic reviews, or discuss the works or authors you have searched for).',
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

		if ($sinceYear != 0) {
			$uri = $uri . '&as_ylo=' . $sinceYear;	
		}

		if ($untilYear != 0) {
			$uri = $uri . '&as_yhi=' . $untilYear;	
		}

        if ($language != '') {
            $uri = $uri . '&hl=' . $language;  
        }

		if ($includePatents) {
			$uri = $uri . '&as_vis=7';
		} else {
			$uri = $uri . '&as_vis=0';
		}

		if ($includeCitations) {
			$uri = $uri . '&as_vis=0';
		} elseif ($includePatents) {
			$uri = $uri . '&as_vis=1';
		}

		if ($reviewArticles) {
			$uri = $uri . '&as_rr=1';
		}
		
		if ($sortBy) {
			$uri = $uri . '&scisbd=1';
		}

		if ($numResults) {
			$uri = $uri . '&num=' . $numResults;
		}

        echo $uri;

        $html = getSimpleHTMLDOM($uri)
            or returnServerError('Could not fetch Google Scholar data.');

        $publications = $html->find('div[class="gs_r gs_or gs_scl"]');

        foreach ($publications as $publication) {

            $articleTitleElement = $publication->find('h3[class="gs_rt"]', 0);
            $articleUrl = $articleTitleElement->find('a', 0)->href;
            $articleTitle = $articleTitleElement->plaintext;

            $articleDateElement = $publication->find('div[class="gs_a"]', 0);
            $articleDate = $articleDateElement ? $articleDateElement->plaintext : '';

            $articleAbstractElement = $publication->find('div[class="gs_rs"]', 0);
            $articleAbstract = $articleAbstractElement ? $articleAbstractElement->plaintext : '';

            $articleAuthorElement = $publication->find('div[class="gs_a"]', 0);
            $articleAuthor = $articleAuthorElement ? $articleAuthorElement->plaintext : '';

            $bottomRowElement = $publication->find('div[class="gs_fl"]', 0);

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
            
            echo $citedBy . ' >= ' . $minCitations . ' ? -- ';

            if ($citedBy >= $minCitations) {

                $item = [
                    'title' => $articleTitle,
                    'uri' => $articleUrl,
                    'timestamp' => strtotime($articleDate),
                    'author' => $articleAuthor,
                    'content' => $articleAbstract
                ];

                $this->items[] = $item;
            }
            // if (count($this->items) >= 10) {
            //     break;
            // }
        }
    }
}