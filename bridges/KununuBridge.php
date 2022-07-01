<?php

class KununuBridge extends BridgeAbstract
{
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'Kununu Bridge';
    const URI = 'https://www.kununu.com/';
    const CACHE_TIMEOUT = 86400; // 24h
    const DESCRIPTION = 'Returns the latest reviews for a company and site of your choice.';

    const PARAMETERS = [
        'global' => [
            'site' => [
                'name' => 'Site',
                'type' => 'list',
                'title' => 'Select your site',
                'values' => [
                    'Austria' => 'at',
                    'Germany' => 'de',
                    'Switzerland' => 'ch'
                ],
                'exampleValue' => 'de',
            ],
            'include_ratings' => [
                'name' => 'Include ratings',
                'type' => 'checkbox',
                'title' => 'Activate to include ratings in the feed'
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'defaultValue' => 3,
                'title' => "Maximum number of items to return in the feed.\n0 = unlimited"
            ]
        ],
        [
            'company' => [
                'name' => 'Company',
                'required' => true,
                'exampleValue' => 'kununu',
                'title' => 'Insert company name (i.e. Kununu) or URI path (i.e. kununu)'
            ]
        ]
    ];

    private $companyName = '';

    public function getURI()
    {
        if (!is_null($this->getInput('company')) && !is_null($this->getInput('site'))) {
            $company = $this->fixCompanyName($this->getInput('company'));
            $site = $this->getInput('site');

            return sprintf('%s%s/%s', self::URI, $site, $company);
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('company'))) {
            $company = $this->fixCompanyName($this->getInput('company'));
            return ($this->companyName ?: $company) . ' - ' . self::NAME;
        }

        return parent::getName();
    }

    public function getIcon()
    {
        return 'https://www.kununu.com/favicon-196x196.png';
    }

    public function collectData()
    {
        $full = $this->getInput('full');

        // Load page
        $json = json_decode(getContents($this->getAPI()), true);
        $this->companyName = $json['common']['name'];
        $baseURI = $this->getURI() . '/bewertung/';

        $limit = $this->getInput('limit') ?: 0;

        // Go through all articles
        foreach ($json['reviews'] as $review) {
            $item = [];
            $item['author'] = $review['position'] . ' / ' . $review['department'];
            $item['timestamp'] = $review['createdAt'];
            $item['title'] = $review['roundedScore'] . ' : ' . $review['title'];
            $item['uri'] = $baseURI . $review['uuid'];
            $item['content'] = $this->extractArticleDescription($review);
            $this->items[] = $item;

            if ($limit > 0 && count($this->items) >= $limit) {
                break;
            }
        }
    }

    /**
    * Returns JSON API url
    */
    private function getAPI()
    {
        $company = $this->fixCompanyName($this->getInput('company'));
        $site = $this->getInput('site');

        return self::URI . 'middlewares/profiles/' .
               $site . '/' . $company .
               '/reviews?reviewType=employees&urlParams=sort=newest&sort=newest&page=1';
    }

    /*
    * Returns a fixed version of the provided company name
    */
    private function fixCompanyName($company)
    {
        $company = trim($company);
        $company = str_replace(' ', '-', $company);
        $company = strtolower($company);

        $umlauts = ['/ä/','/ö/','/ü/','/Ä/','/Ö/','/Ü/','/ß/'];
        $replace = ['ae','oe','ue','Ae','Oe','Ue','ss'];

        return preg_replace($umlauts, $replace, $company);
    }

    /**
    * Returns the description from a given article
    */
    private function extractArticleDescription($json)
    {
        $retVal = '';
        foreach ($json['texts'] as $text) {
            $retVal .= '<h4>' . $text['id'] . '</h4><p>' . $text['text'] . '</p>';
        }

        if ($this->getInput('include_ratings') && !empty($json['ratings'])) {
            $retVal .= (empty($retVal) ? '' : '<hr>') . '<table>';
            foreach ($json['ratings'] as $rating) {
                $retVal .= <<<EOD
<tr>
	<td>{$rating['id']}
	<td>{$rating['roundedScore']}
	<td>{$rating['text']}
</tr>
EOD;
            }
            $retVal .= '</table>';
        }

        return $retVal;
    }
}
