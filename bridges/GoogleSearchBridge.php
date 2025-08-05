<?php

class GoogleSearchBridge extends BridgeAbstract
{
    const MAINTAINER = 'sebsauvage';
    const NAME = 'Google search';
    const URI = 'https://www.google.com/';
    const CACHE_TIMEOUT = 60 * 30; // 30m
    const DESCRIPTION = 'Returns max 100 results from the past year.';

    const PARAMETERS = [[
        'q' => [
            'name' => 'keyword',
            'required' => true,
            'exampleValue' => 'rss-bridge',
        ],
        'verbatim' => [
            'name' => 'Verbatim',
            'type' => 'checkbox',
            'title' => 'Use literal keyword(s) without making improvements',
        ],
    ]];

    public function collectData()
    {
        // todo: wrap this in try..catch because 429 too many requests happens a lot
        $dom = getSimpleHTMLDOM($this->getURI(), ['Accept-language: en-US']);
        if (!$dom) {
            throwServerException('No results for this query.');
        }
        $result = $dom->find('div[id=res]', 0);

        if (!$result) {
            return;
        }

        foreach ($result->find('div[class~=g]') as $element) {
            $item = [];

            $url = $element->find('a[href]', 0)->href;
            $item['uri'] = htmlspecialchars_decode($url);
            $item['title'] = $element->find('h3', 0)->plaintext;

            $resultDom = $element->find('div[data-content-feature=1]', 0);
            if ($resultDom) {
                // Split by — or ·
                $resultParts = preg_split('/( — | · )/', $resultDom->plaintext);
                $resultDate = trim($resultParts[0]);
                $resultContent = trim($resultParts[1] ?? '');
            } else {
                // Some search results don't have this particular dom identifier
                $resultDate = null;
                $resultContent = null;
            }

            if ($resultDate) {
                try {
                    $createdAt = new \DateTime($resultDate);
                    // Set to midnight for consistent datetime
                    $createdAt->setTime(0, 0);
                    $item['timestamp'] = $createdAt->format('U');
                } catch (\Exception $e) {
                    $item['timestamp'] = 0;
                }
            } else {
                $item['timestamp'] = 0;
            }

            if ($resultContent) {
                $item['content'] = $resultContent;
            }

            $this->items[] = $item;
        }
        // Sort by descending date
        usort($this->items, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
    }

    public function getURI()
    {
        if ($this->getInput('q')) {
            $queryParameters = [
                'q'         => $this->getInput('q'),
                'hl'        => 'en',
                'num'       => '100', // get 100 results
                'complete'  => '0',
                // in past year, sort by date, optionally verbatim
                'tbs'       => 'qdr:y,sbd:1' . ($this->getInput('verbatim') ? ',li:1' : ''),
            ];
            return sprintf('https://www.google.com/search?%s', http_build_query($queryParameters));
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('q'))) {
            return $this->getInput('q') . ' - Google search';
        }

        return parent::getName();
    }
}
