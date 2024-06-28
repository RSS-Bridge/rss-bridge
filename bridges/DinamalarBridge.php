<?php

class DinamalarBridge extends BridgeAbstract
{
    const NAME = 'Dinamalar';
    const URI = 'https://www.dinamalar.com';
    const DESCRIPTION = 'Retrieve news from dinamalar.com';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'topic',
                'type' => 'list',
                'values' => [
                    'main' => [
                        'latest-tamil-news' => 'newsdata?cat=5010',
                        'premium-news' => 'newsdata?cat=651',
                        'tamil-nadu-news' => 'newsdata?cat=89',
                        'india-tamil-news' => 'newsdata?cat=100',
                        'world-tamil-news' => 'newsdata?cat=34',
                    ],
                    'district' => [
                        'Ariyalur' => 'districtdata?cat=297',
                        'Chengalpattu' => 'districtdata?cat=315',
                        'Chennai' => 'districtdata?cat=267',
                        'Coimbatore' => 'districtdata?cat=287',
                        'Cuddalore' => 'districtdata?cat=273',
                        'Dharmapuri' => 'districtdata?cat=278',
                        'Dindigul' => 'districtdata?cat=290',
                        'Erode' => 'districtdata?cat=280',
                        'Kallakurichi' => 'districtdata?cat=314',
                        'Kancheepuram' => 'districtdata?cat=269',
                        'Kanniyakumari' => 'districtdata?cat=295',
                        'Karur' => 'districtdata?cat=285',
                        'Krishnagiri' => 'districtdata?cat=296',
                        'Madurai' => 'districtdata?cat=291',
                        'Mayiladuthurai' => 'districtdata?cat=318',
                        'Nagapattinam' => 'districtdata?cat=282',
                        'Namakkal' => 'districtdata?cat=283',
                        'Nilgiris' => 'districtdata?cat=289',
                        'Perambalur' => 'districtdata?cat=274',
                        'Puducherry' => 'districtdata?cat=234',
                        'Pudukottai' => 'districtdata?cat=277',
                        'Ramanathapuram' => 'districtdata?cat=286',
                        'Ranipet' => 'districtdata?cat=317',
                        'Salem' => 'districtdata?cat=276',
                        'Sivagangai' => 'districtdata?cat=284',
                        'Tenkasi' => 'districtdata?cat=313',
                        'Thanjavur' => 'districtdata?cat=281',
                        'Theni' => 'districtdata?cat=288',
                        'Thirunelveli' => 'districtdata?cat=293',
                        'Thiruvallur' => 'districtdata?cat=270',
                        'Thiruvallur' => 'districtdata?cat=275',
                        'Thoothukudi' => 'districtdata?cat=294',
                        'Tirupathur' => 'districtdata?cat=316',
                        'Tiruppur' => 'districtdata?cat=298',
                        'Tiruvannamalai' => 'districtdata?cat=271',
                        'Trichirappalli' => 'districtdata?cat=279',
                        'Vellore' => 'districtdata?cat=272',
                        'Viluppuram' => 'districtdata?cat=268',
                        'Virudhunagar' => 'districtdata?cat=292',
                    ],
                ],
            ],
        ],
    ];

    public function getName()
    {
        $topic = $this->getKey('topic');
        return self::NAME . ($topic ? ' - ' . $topic : '');
    }

    public function collectData()
    {
        $url = self::URI . '/api/alone/pageview?p1=/' . $this->getInput('topic');
        $json = json_decode(getContents($url));
        $data = $json->data->newlist->data ?? $json->data->districtlisting->data;

        foreach ($data as $element) {
            $this->items[] = [
                'content' => $this->collectContent($element->newsid),
                'timestamp' => $element->newsdate . '+05:30',
                'title' => $element->newstitle,
                'uid' => $element->newsid,
                'uri' => self::URI . $element->slug,
            ];
        }
    }

    private function collectContent($id)
    {
        $url = self::URI . '/api/alone/pageview?p1=detaildata?newsid=' . $id;
        $json = json_decode(getContents($url));
        $data = $json->data->detailnews->detailpage[0];

        $image = '';
        if (isset($data->largeimages) && !empty($data->largeimages)) {
            $image = '<p><img src="' . $data->largeimages . '"></p>';
        }

        $content = '<p>' . $data->newsdescription . '</p>';

        return $image . $content;
    }
}
