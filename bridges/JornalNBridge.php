<?php

class JornalNBridge extends BridgeAbstract
{
    const NAME = 'Jornal N';
    const URI = 'https://www.jornaln.pt/';
    const DESCRIPTION = 'Returns news from the Portuguese local newspaper Jornal N';
    const MAINTAINER = 'rmscoelho';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'News Feed',
                'type' => 'list',
                'title' => 'Feeds from the Portuguese sports newspaper A BOLA.PT',
                'values' => [
                    'Concelhos' => [
                        'Espinho' => 'espinho',
                        'Ovar' => 'ovar',
                        'Santa Maria da Feira' => 'santa-maria-da-feira',
                    ],
                    'Cultura' => 'ovar/cultura',
                    'Desporto' => 'desporto',
                    'Economia' => 'santa-maria-da-feira/economia',
                    'Política' => 'santa-maria-da-feira/politica',
                    'Opinião' => 'santa-maria-da-feira/opiniao',
                    'Sociedade' => 'santa-maria-da-feira/sociedade',
                ]
            ]
        ]
    ];

    const PT_MONTH_NAMES = [
        'janeiro' => '01',
        'fevereiro' => '02',
        'março' => '03',
        'abril' => '04',
        'maio' => '05',
        'junho' => '06',
        'julho' => '07',
        'agosto' => '08',
        'setembro' => '09',
        'outubro' => '10',
        'novembro' => '11',
        'dezembro' => '12',
    ];

    public function getIcon()
    {
        return 'https://www.jornaln.pt/wp-content/uploads/2023/01/cropped-NovoLogoJornal_Instagram-192x192.png';
    }

    public function getName()
    {
        if ($this->getKey('feed')) {
            return self::NAME . ' | ' . $this->getKey('feed');
        }
        return self::NAME;
    }

    public function getURI()
    {
        return self::URI . $this->getInput('feed');
    }

    public function collectData()
    {
        $url = sprintf(self::URI . '/%s', $this->getInput('feed'));
        $dom = getSimpleHTMLDOMCached($url);
        $domSelector = '.elementor-widget-container > .elementor-posts-container';
        $dom = $dom->find($domSelector, 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('article') as $article) {
            //Get thumbnail
            $image = $article->find('.elementor-post__thumbnail img', 0)->src;
            //Timestamp
            $date = $article->find('.elementor-post-date', 0)->plaintext;
            $date = trim($date, "\t ");
            $date = preg_replace('/ de /i', '/', $date);
            $date = preg_replace('/, /', '/', $date);
            $date = explode('/', $date);
            $year = (int) $date[2];
            $month = (int) $date[1];
            $day = (int) $date[0];
            foreach (self::PT_MONTH_NAMES as $key => $item) {
                if ($key === strtolower($month)) {
                    $month = (int) $item;
                }
            }
            $timestamp = mktime(0, 0, 0, $month, $day, $year);
            //Content
            $content = '<img src="' . $image . '" alt="' . $article->find('.elementor-post__title > a', 0)->plaintext . '" />';
            $this->items[] = [
                'title' => $article->find('.elementor-post__title > a', 0)->plaintext,
                'uri' => $article->find('a', 0)->href,
                'content' => $content,
                'timestamp' => $timestamp
            ];
        }
    }
}
