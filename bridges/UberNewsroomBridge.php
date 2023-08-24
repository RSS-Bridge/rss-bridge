<?php

class UberNewsroomBridge extends BridgeAbstract
{
    const NAME = 'Uber Newsroom Bridge';
    const URI = 'https://www.uber.com';
    const URI_API_DATA = 'https://newsroomapi.uber.com/wp-json/newsroom/v1/data?locale=';
    const URI_API_POST = 'https://newsroomapi.uber.com/wp-json/wp/v2/posts/';
    const DESCRIPTION = 'Returns news posts';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [[
        'region' => [
            'name' => 'Region',
            'type' => 'list',
            'values' => [
                'Africa' => [
                    'Egypt' => 'ar-EG',
                    'Ghana' => 'en-GH',
                    'Kenya' => 'en-KE',
                    'Morocco' => 'fr-MA',
                    'Nigeria' => 'en-NG',
                    'South Africa' => 'en-ZA',
                    'Tanzania' => 'en-TZ',
                    'Uganda' => 'en-UG',
                ],
                'Asia' => [
                    'Bangladesh' => 'en-BD',
                    'Cambodia' => 'km-KH',
                    'China' => 'zh-CN',
                    'Hong Kong' => 'zh-HK',
                    'India' => 'en-IN',
                    'Indonesia' => 'en-ID',
                    'Japan' => 'ja-JP',
                    'Korea' => 'ko-KR',
                    'Macau' => 'zh-MO',
                    'Malaysia' => 'en-MY',
                    'Myanmar' => 'en-MM',
                    'Philippines' => 'en-PH',
                    'Singapore' => 'en-SG',
                    'Sri Lanka' => 'en-LK',
                    'Taiwan' => 'zh-TW',
                    'Thailand' => 'th-TH',
                    'Vietnam' => 'vi-VN',
                ],
                'Central America' => [
                    'Costa Rica' => 'es-CR',
                    'Dominican Republic' => 'es-DO',
                    'El Salvador' => 'es-SV',
                    'Guatemala' => 'es-GT',
                    'Honduras' => 'es-HN',
                    'Mexico' => 'es-MX',
                    'Nicaragua' => 'es-NI',
                    'Panama' => 'es-PA',
                    'Puerto Rico' => 'es-PR',
                ],
                'Europe' => [
                    'Austria' => 'de-AT',
                    'Azerbaijan' => 'az',
                    'Belarus' => 'ru-BY',
                    'Belgium' => 'fr-BE',
                    'Bulgaria' => 'bg',
                    'Croatia' => 'hr',
                    'Czech Republic' => 'cs-CZ',
                    'Denmark' => 'da-DK',
                    'Estonia' => 'et-EE',
                    'Finland' => 'fi',
                    'France' => 'fr',
                    'Germany' => 'de',
                    'Greece' => 'el-GR',
                    'Hungary' => 'hu',
                    'Ireland' => 'en-IE',
                    'Italy' => 'it',
                    'Kazakhstan' => 'ru-KZ',
                    'Lithuania' => 'lt',
                    'Netherlands' => 'nl',
                    'Norway' => 'nb-NO',
                    'Poland' => 'pl',
                    'Portugal' => 'pt',
                    'Romania' => 'ro',
                    'Russia' => 'ru',
                    'Slovakia' => 'sk',
                    'Spain' => 'es-ES',
                    'Sweden' => 'sv-SE',
                    'Switzerland' => 'fr-CH',
                    'Turkey' => 'tr',
                    'Ukraine' => 'uk-UA',
                    'United Kingdom' => 'en-GB',
                ],
                'Middle East' => [
                    'Bahrain' => 'en-BH',
                    'Israel' => 'he-IL',
                    'Jordan' => 'en-JO',
                    'Kuwait' => 'en-KW',
                    'Lebanon' => 'en-LB',
                    'Pakistan' => 'en-PK',
                    'Qatar' => 'en-QA',
                    'Saudi Arabia' => 'ar-SA',
                    'United Arab Emirates' => 'en-AE',
                ],
                'North America' => [
                    'Canada' => 'en-CA',
                    'United States' => 'en-US',
                ],
                'Pacific' => [
                    'Australia' => 'en-AU',
                    'New Zealand' => 'en-NZ',
                ],
                'South America' => [
                    'Argentina' => 'es-AR',
                    'Bolivia' => 'es-BO',
                    'Brazil' => 'pt-BR',
                    'Chile' => 'es-CL',
                    'Colombia' => 'es-CO',
                    'Ecuador' => 'es-EC',
                    'Paraguay' => 'es-PY',
                    'Peru' => 'es-PE',
                    'Trinidad & Tobago' => 'en-TT',
                    'Uruguay' => 'es-UY',
                    'Venezuela' => 'es-VE',
                ],
            ],
            'defaultValue' => 'en-US',
        ]
    ]];

    const CACHE_TIMEOUT = 3600;

    private $regionName = '';

    public function collectData()
    {
        $json = getContents(self::URI_API_DATA . $this->getInput('region'));
        $data = json_decode($json);

        $this->regionName = $data->region->name;

        foreach ($data->articles as $article) {
            $json = getContents(self::URI_API_POST . $article->id);
            $post = json_decode($json);

            $item = [];
            $item['title'] = $post->title->rendered;
            $item['timestamp'] = $post->date;
            $item['uri'] = $post->link;
            $item['content'] = $this->formatContent($post->content->rendered);
            $item['enclosures'][] = $article->image_full;

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (is_null($this->getInput('region')) === false) {
            return self::URI . '/' . $this->getInput('region') . '/newsroom';
        }

        return parent::getURI() . '/newsroom';
    }

    public function getName()
    {
        if (is_null($this->getInput('region')) === false) {
            return $this->regionName . ' - Uber Newsroom';
        }

        return parent::getName();
    }

    private function formatContent($html)
    {
        $html = str_get_html($html);

        foreach ($html->find('div.wp-video') as $div) {
            $div->style = '';
        }

        foreach ($html->find('video') as $video) {
            $video->width = '100%';
            $video->height = '';
        }

        return $html;
    }
}
