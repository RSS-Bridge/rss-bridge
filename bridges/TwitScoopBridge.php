<?php

class TwitScoopBridge extends BridgeAbstract
{
    const NAME = 'TwitScoop Bridge';
    const URI = 'https://www.twitscoop.com';
    const DESCRIPTION = 'Returns trending Twitter topics by country';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [
        [
            'country' => [
                'name' => 'Country',
                'type' => 'list',
                'values' => [
                    'Worldwide' => 'worldwide',
                    'Algeria' => 'algeria',
                    'Argentina' => 'argentina',
                    'Australia' => 'australia',
                    'Austria' => 'austria',
                    'Bahrain' => 'bahrain',
                    'Belarus' => 'belarus',
                    'Belgium' => 'belgium',
                    'Brazil' => 'brazil',
                    'Canada' => 'canada',
                    'Chile' => 'chile',
                    'Colombia' => 'colombia',
                    'Denmark' => 'denmark',
                    'Dominican Republic' => 'dominican-republic',
                    'Ecuador' => 'ecuador',
                    'Egypt' => 'egypt',
                    'France' => 'france',
                    'Germany' => 'germany',
                    'Ghana' => 'ghana',
                    'Greece' => 'greece',
                    'Guatemala' => 'guatemala',
                    'India' => 'india',
                    'Indonesia' => 'indonesia',
                    'Ireland' => 'ireland',
                    'Israel' => 'israel',
                    'Italy' => 'italy',
                    'Japan' => 'japan',
                    'Jordan' => 'jordan',
                    'Kenya' => 'kenya',
                    'Korea' => 'korea',
                    'Kuwait' => 'kuwait',
                    'Latvia' => 'latvia',
                    'Lebanon' => 'lebanon',
                    'Malaysia' => 'malaysia',
                    'Mexico' => 'mexico',
                    'Netherlands' => 'netherlands',
                    'New Zealand' => 'new-zealand',
                    'Nigeria' => 'nigeria',
                    'Norway' => 'norway',
                    'Oman' => 'oman',
                    'Pakistan' => 'pakistan',
                    'Panama' => 'panama',
                    'Peru' => 'peru',
                    'Philippines' => 'philippines',
                    'Poland' => 'poland',
                    'Portugal' => 'portugal',
                    'Puerto Rico' => 'puerto-rico',
                    'Qatar' => 'qatar',
                    'Russia' => 'russia',
                    'Saudi Arabia' => 'saudi-arabia',
                    'Singapore' => 'singapore',
                    'South Africa' => 'south-africa',
                    'Spain' => 'spain',
                    'Sweden' => 'sweden',
                    'Switzerland' => 'switzerland',
                    'Thailand' => 'thailand',
                    'Turkey' => 'turkey',
                    'Ukraine' => 'ukraine',
                    'United Arab Emirates' => 'united-arab-emirates',
                    'United Kingdom' => 'united-kingdom',
                    'United States' => 'united-states',
                    'Venezuela' => 'venezuela',
                    'Vietnam' => 'vietnam',
                ]
            ],
            'limit' => [
                'name' => 'Topics',
                'type' => 'number',
                'title' => 'Number of trending topics to return. Max 50',
                'defaultValue' => 20,
            ]
        ]
    ];

    const CACHE_TIMEOUT = 900; // 15 mins

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        $updated = $html->find('time', 0)->datetime;
        $trends = $html->find('div.trends', 0);

        $limit = $this->getInput('limit');

        if ($limit > 50 || $limit < 1) {
            $limit = 50;
        }

        foreach ($trends->find('ol.items > li') as $index => $li) {
            $number = $index + 1;

            $item = [];

            $name = rtrim($li->find('span.trend.name', 0)->plaintext, '&nbsp');
            $tweets = str_replace(' tweets', '', $li->find('span.tweets', 0)->plaintext);
            $tweets = str_replace('<', '', $tweets);

            $item['title'] = '#' . $number . ' - ' . $name . ' (' . $tweets . ' tweets)';
            $item['uri'] = 'https://twitter.com/search?q=' . rawurlencode($name);

            if ($tweets === '10K') {
                $tweets = 'less than 10K';
            }

            $item['content'] = <<<EOD
<strong>Rank</strong><br>
<p>{$number}</p>
<Strong>Topic</strong><br>
<p>{$name}</p>
<Strong>Tweets</strong><br>
<p>{$tweets}</p>
EOD;
            $item['timestamp'] = $updated;

            $this->items[] = $item;

            if (count($this->items) >= $limit) {
                break;
            }
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('country'))) {
            return self::URI . '/' . $this->getInput('country');
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('country'))) {
            $parameters = $this->getParameters();
            $values = array_flip($parameters[0]['country']['values']);

            return $values[$this->getInput('country')] . ' - TwitScoop';
        }

        return parent::getName();
    }
}
