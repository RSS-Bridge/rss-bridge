<?php

class FindACrewBridge extends BridgeAbstract
{
    const MAINTAINER = 'couraudt';
    const NAME = 'Find A Crew Bridge';
    const URI = 'https://www.findacrew.net';
    const DESCRIPTION = 'Returns the newest sailing offers.';
    const PARAMETERS = [
        [
            'type' => [
                'name' => 'Type of search',
                'title' => 'Choose between finding a boat or a crew',
                'type' => 'list',
                'values' => [
                    'Find a boat' => 'boat',
                    'Find a crew' => 'crew'
                ]
            ],
            'long' => [
                'name' => 'Longitude of the searched location',
                'title' => 'Center the search at that longitude (e.g: -42.02)'
            ],
            'lat' => [
                'name' => 'Latitude of the searched location',
                'title' => 'Center the search at that latitude (e.g: 12.42)'
            ],
            'distance' => [
                'name' => 'Limit boundary of search in KM',
                'title' => 'Boundary of the search in kilometers when using longitude and latitude'
            ],
            'limit' => self::LIMIT,
        ]
    ];

    public function collectData()
    {
        $url = $this->getURI();

        if ($this->getInput('type') == 'boat') {
            $data = ['SrhLstBtAction' => 'Create'];
        } else {
            $data = ['SrhLstCwAction' => 'Create'];
        }

        if ($this->getInput('long') && $this->getInput('lat')) {
            $data['real_LocSrh_Lng'] = $this->getInput('long');
            $data['real_LocSrh_Lat'] = $this->getInput('lat');
            if ($this->getInput('distance')) {
                $data['LocDis'] = (int)$this->getInput('distance') * 1000;
            }
        }

        $header = [
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $opts = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data) . "\n"
        ];

        $html = getSimpleHTMLDOM($url, $header, $opts) or returnClientError('No results for this query.');

        $annonces = $html->find('.css_SrhRst');
        $limit = $this->getInput('limit') ?? 10;
        foreach (array_slice($annonces, 0, $limit) as $annonce) {
            $item = [];

            $link = parent::getURI() . $annonce->find('.lstsum-btn-con a', 0)->href;
            $htmlDetail = getSimpleHTMLDOMCached($link . '?mdl=2'); // add ?mdl=2 for xhr content not full html page

            $img = parent::getURI() . $htmlDetail->find('img.img-responsive', 0)->getAttribute('src');
            $item['title'] = $htmlDetail->find('div.label-account', 0)->plaintext;
            $item['uri'] = $link;
            $content = $htmlDetail->find('.panel-body div.clearfix.row > div', 1)->innertext;
            $content .= $htmlDetail->find('.panel-body > div', 1)->innertext;
            $content = defaultLinkTo($content, parent::getURI());
            $item['content'] = $content;
            $item['enclosures'] = [$img];
            $item['categories'] = [$annonce->find('.css_AccLocCur', 0)->plaintext];
            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        $uri = parent::getURI();
        // Those params must be in the URL
        $uri .= '/en/' . $this->getInput('type') . '/search?srhtyp=srhrst&mdl=2';
        return $uri;
    }
}
