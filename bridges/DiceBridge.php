<?php

class DiceBridge extends BridgeAbstract
{
    const MAINTAINER = 'rogerdc';
    const NAME = 'Dice Unofficial RSS';
    const URI = 'https://www.dice.com/';
    const DESCRIPTION = 'The Unofficial Dice RSS';
    // const CACHE_TIMEOUT = 86400; // 1 day

    const PARAMETERS = [[
        'for_one' => [
            'name' => 'With at least one of the words',
            'required' => false,
        ],
        'for_all' => [
            'name' => 'With all of the words',
            'required' => false,
        ],
        'for_exact' => [
            'name' => 'With the exact phrase',
            'required' => false,
        ],
        'for_none' => [
            'name' => 'With none of these words',
            'required' => false,
        ],
        'for_jt' => [
            'name' => 'Within job title',
            'required' => false,
        ],
        'for_com' => [
            'name' => 'Within company name',
            'required' => false,
        ],
        'for_loc' => [
            'name' => 'City, State, or ZIP code',
            'required' => false,
        ],
        'radius' => [
            'name' => 'Radius in miles',
            'type' => 'list',
            'required' => false,
            'values' => [
                'Exact Location' => 'El',
                'Within 5 miles' => '5',
                'Within 10 miles' => '10',
                'Within 20 miles' => '20',
                'Within 30 miles' => '0',
                'Within 40 miles' => '40',
                'Within 50 miles' => '50',
                'Within 75 miles' => '75',
                'Within 100 miles' => '100',
            ],
            'defaultValue' => '0',
        ],
        'jtype' => [
            'name' => 'Job type',
            'type' => 'list',
            'required' => false,
            'values' => [
                'Full-Time' => 'Full Time',
                'Part-Time' => 'Part Time',
                'Contract - Independent' => 'Contract Independent',
                'Contract - W2' => 'Contract W2',
                'Contract to Hire - Independent' => 'C2H Independent',
                'Contract to Hire - W2' => 'C2H W2',
                'Third Party - Contract - Corp-to-Corp' => 'Contract Corp-To-Corp',
                'Third Party - Contract to Hire - Corp-to-Corp' => 'C2H Corp-To-Corp',
            ],
            'defaultValue' => 'Full Time',
        ],
        'telecommute' => [
            'name' => 'Telecommute',
            'type' => 'checkbox',
        ],
    ]];

    public function getIcon()
    {
        return 'https://assets.dice.com/techpro/img/favicons/favicon.ico';
    }

    public function collectData()
    {
        $uri = 'https://www.dice.com/jobs/advancedResult.html';
        $uri .= '?for_one=' . urlencode($this->getInput('for_one'));
        $uri .= '&for_all=' . urlencode($this->getInput('for_all'));
        $uri .= '&for_exact=' . urlencode($this->getInput('for_exact'));
        $uri .= '&for_none=' . urlencode($this->getInput('for_none'));
        $uri .= '&for_jt=' . urlencode($this->getInput('for_jt'));
        $uri .= '&for_com=' . urlencode($this->getInput('for_com'));
        $uri .= '&for_loc=' . urlencode($this->getInput('for_loc'));
        if ($this->getInput('jtype')) {
            $uri .= '&jtype=' . urlencode($this->getInput('jtype'));
        }
        $uri .= '&sort=date&limit=100';
        $uri .= '&radius=' . urlencode($this->getInput('radius'));
        if ($this->getInput('telecommute')) {
            $uri .= '&telecommute=true';
        }

        $html = getSimpleHTMLDOM($uri);
        foreach ($html->find('div.complete-serp-result-div') as $element) {
            $item = [];
            // Title
            $masterLink = $element->find('a[id^=position]', 0);
            $item['title'] = $masterLink->title;
            // URL
            $uri = $masterLink->href;
            // $uri = substr($uri, 0, strrpos($uri, '?'));
            $item['uri'] = substr($uri, 0, strrpos($uri, '?'));
            // ID
            $item['id'] = $masterLink->value;
            // Image
            $image = $element->find('img', 0);
            if ($image) {
                $item['image'] = $image->getAttribute('src');
            }
            // Content
            $shortdesc = $element->find('.shortdesc', '0');
            $shortdesc = ($shortdesc) ? $shortdesc->innertext : '';
            $item['content'] = $shortdesc;
            $this->items[] = $item;
        }
    }
}
