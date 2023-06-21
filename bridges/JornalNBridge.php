<?php

class JornalNBridge extends BridgeAbstract
{
    const NAME = 'Jornal N';
    const URI = 'https://www.jornaln.pt/';
    const DESCRIPTION = 'Returns news from the Portuguese local newspaper Jornal N';
    const MAINTAINER = 'rmscoelho';
    const CACHE_TIMEOUT = 86400;
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
                    'Cultura' => 'cultura',
                    'Desporto' => 'desporto',
                    'Economia' => 'economia',
                    'Política' => 'politica',
                    'Opinião' => 'opiniao',
                    'Sociedade' => 'sociedade',
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
        $feed = $this->getInput('feed');
        if ($this->getInput('feed') !== null && $this->getInput('feed') !== '') {
            if ($feed === 'santa-maria-da-feira') {
                $name = 'Santa Maria da Feira';
            } else {
                $name = ucfirst($feed);
            }
            return self::NAME . ' | ' . $name;
        }
        return self::NAME;
    }

    public function getURI()
    {
        return self::URI . $this->getInput('feed');
    }

    public function collectData()
    {
        $url = sprintf('https://www.jornaln.pt/%s', $this->getInput('feed'));
        $dom = getSimpleHTMLDOMCached($url);
        $domSelector = '.elementor-widget-container > .elementor-posts-container';
        $dom = $dom->find($domSelector, 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('article') as $article) {
            //Get thumbnail
            $image = $article->find('img', 0)->src;
            //Timestamp
            $date = $article->find('.elementor-post-date', 0)->plaintext;
            $date = preg_replace('/ de /i', '/', $date);
            $date = preg_replace('/, /', '/', $date);
            $date = explode('/', $date);
            $year = $date[2];
            $month = $date[1];
            $day = $date[0];
            foreach (self::PT_MONTH_NAMES as $key => $item) {
                if ($key === strtolower($month)) {
                    $month = $item;
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
