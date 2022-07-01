<?php

class DesoutterBridge extends BridgeAbstract
{
    const CATEGORY_NEWS = 'News & Events';
    const CATEGORY_INDUSTRY = 'Industry 4.0 News';

    const NAME = 'Desoutter Bridge';
    const URI = 'https://www.desouttertools.com';
    const DESCRIPTION = 'Returns feeds for news from Desoutter';
    const MAINTAINER = 'logmanoriginal';
    const CACHE_TIMEOUT = 86400; // 24 hours

    const PARAMETERS = [
        self::CATEGORY_NEWS => [
            'news_lang' => [
                'name' => 'Language',
                'type' => 'list',
                'title' => 'Select your language',
                'defaultValue' => 'https://www.desouttertools.com/about-desoutter/news-events',
                'values' => [
                    'Corporate'
                    => 'https://www.desouttertools.com/about-desoutter/news-events',
                    'Česko'
                    => 'https://www.desouttertools.cz/o-desoutter/aktuality-udalsoti',
                    'Deutschland'
                    => 'https://www.desoutter.de/ueber-desoutter/news-events',
                    'España'
                    => 'https://www.desouttertools.es/sobre-desoutter/noticias-eventos',
                    'México'
                    => 'https://www.desouttertools.mx/acerca-desoutter/noticias-eventos',
                    'France'
                    => 'https://www.desouttertools.fr/a-propos-de-desoutter/actualites-evenements',
                    'Magyarország'
                    => 'https://www.desouttertools.hu/a-desoutter-vallalatrol/hirek-esemenyek',
                    'Italia'
                    => 'https://www.desouttertools.it/su-desoutter/news-eventi',
                    '日本'
                    => 'https://www.desouttertools.jp/desotanituite/niyusu-ibento',
                    '대한민국'
                    => 'https://www.desouttertools.co.kr/desoteoe-daehaeseo/nyuseu-mic-ibenteu',
                    'Polska'
                    => 'https://www.desouttertools.pl/o-desoutter/aktualnosci-wydarzenia',
                    'Brasil'
                    => 'https://www.desouttertools.com.br/sobre-desoutter/noti%C2%ADcias-eventos',
                    'Portugal'
                    => 'https://www.desouttertools.pt/sobre-desoutter/notIcias-eventos',
                    'România'
                    => 'https://www.desouttertools.ro/despre-desoutter/noutati-evenimente',
                    'Российская Федерация'
                    => 'https://www.desouttertools.com.ru/o-desoutter/novosti-mieropriiatiia',
                    'Slovensko'
                    => 'https://www.desouttertools.sk/o-spolocnosti-desoutter/novinky-udalosti',
                    'Slovenija'
                    => 'https://www.desouttertools.si/o-druzbi-desoutter/novice-dogodki',
                    'Sverige'
                    => 'https://www.desouttertools.se/om-desoutter/nyheter-evenemang',
                    'Türkiye'
                    => 'https://www.desoutter.com.tr/desoutter-hakkinda/haberler-etkinlikler',
                    '中国'
                    => 'https://www.desouttertools.com.cn/guan-yu-ma-tou/xin-wen-he-huo-dong',
                ]
            ],
        ],
        self::CATEGORY_INDUSTRY => [
            'industry_lang' => [
                'name' => 'Language',
                'type' => 'list',
                'title' => 'Select your language',
                'defaultValue' => 'Corporate',
                'values' => [
                    'Corporate'
                    => 'https://www.desouttertools.com/industry-4-0/news',
                    'Česko'
                    => 'https://www.desouttertools.cz/prumysl-4-0/novinky',
                    'Deutschland'
                    => 'https://www.desoutter.de/industrie-4-0/news',
                    'España'
                    => 'https://www.desouttertools.es/industria-4-0/noticias',
                    'México'
                    => 'https://www.desouttertools.mx/industria-4-0/noticias',
                    'France'
                    => 'https://www.desouttertools.fr/industrie-4-0/actualites',
                    'Magyarország'
                    => 'https://www.desouttertools.hu/industry-4-0/hirek',
                    'Italia'
                    => 'https://www.desouttertools.it/industry-4-0/news',
                    '日本'
                    => 'https://www.desouttertools.jp/industry-4-0/news',
                    '대한민국'
                    => 'https://www.desouttertools.co.kr/industry-4-0/news',
                    'Polska'
                    => 'https://www.desouttertools.pl/przemysl-4-0/wiadomosci',
                    'Brasil'
                    => 'https://www.desouttertools.com.br/industria-4-0/noticias',
                    'Portugal'
                    => 'https://www.desouttertools.pt/industria-4-0/noticias',
                    'România'
                    => 'https://www.desouttertools.ro/industry-4-0/noutati',
                    'Российская Федерация'
                    => 'https://www.desouttertools.com.ru/industry-4-0/news',
                    'Slovensko'
                    => 'https://www.desouttertools.sk/priemysel-4-0/novinky',
                    'Slovenija'
                    => 'https://www.desouttertools.si/industrija-4-0/novice',
                    'Sverige'
                    => 'https://www.desouttertools.se/industri-4-0/nyheter',
                    'Türkiye'
                    => 'https://www.desoutter.com.tr/endustri-4-0/haberler',
                    '中国'
                    => 'https://www.desouttertools.com.cn/industry-4-0/news',
                ]
            ],
        ],
        'global' => [
            'full' => [
                'name' => 'Load full articles',
                'type' => 'checkbox',
                'title' => 'Enable to load the full article for each item'
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 3,
                'title' => "Maximum number of items to return in the feed.\n0 = unlimited"
            ]
        ]
    ];

    private $title;

    public function getURI()
    {
        switch ($this->queriedContext) {
            case self::CATEGORY_NEWS:
                return $this->getInput('news_lang') ?: parent::getURI();
            case self::CATEGORY_INDUSTRY:
                return $this->getInput('industry_lang') ?: parent::getURI();
        }

        return parent::getURI();
    }

    public function getName()
    {
        return isset($this->title) ? $this->title . ' - ' . parent::getName() : parent::getName();
    }

    public function collectData()
    {
        // Uncomment to generate list of languages automtically (dev mode)
        /*
        switch($this->queriedContext) {
            case self::CATEGORY_NEWS:
                $this->extractNewsLanguages(); die;
            case self::CATEGORY_INDUSTRY:
                $this->extractIndustryLanguages(); die;
        }
        */

        $html = getSimpleHTMLDOM($this->getURI());

        $html = defaultLinkTo($html, $this->getURI());

        $this->title = html_entity_decode($html->find('title', 0)->plaintext, ENT_QUOTES);

        $limit = $this->getInput('limit') ?: 0;

        foreach ($html->find('article') as $article) {
            $item = [];

            $item['uri'] = $article->find('a', 0)->href;
            $item['title'] = $article->find('a[title]', 0)->title;

            if ($this->getInput('full')) {
                $item['content'] = $this->getFullNewsArticle($item['uri']);
            } else {
                $item['content'] = $article->find('div.tile-body p', 0)->plaintext;
            }

            $this->items[] = $item;

            if ($limit > 0 && count($this->items) >= $limit) {
                break;
            }
        }
    }

    private function getFullNewsArticle($uri)
    {
        $html = getSimpleHTMLDOMCached($uri);

        $html = defaultLinkTo($html, $this->getURI());

        return $html->find('section.article', 0);
    }

    /**
     * Generates a HTML page with a PHP formatted array of languages,
     * pointing to the corresponding news pages. Implementation is based
     * on the 'Corporate' site.
     * @return void
     */
    private function extractNewsLanguages()
    {
        $html = getSimpleHTMLDOMCached('https://www.desouttertools.com/about-desoutter/news-events');

        $html = defaultLinkTo($html, static::URI);

        $items = $html->find('ul[class="dropdown-menu"] li');

        $list = "\t'Corporate'\n\t=> 'https://www.desouttertools.com/about-desoutter/news-events',\n";

        foreach ($items as $item) {
            $lang = trim($item->plaintext);
            $uri = $item->find('a', 0)->href;

            $list .= "\t'{$lang}'\n\t=> '{$uri}',\n";
        }

        echo $list;
    }

    /**
     * Generates a HTML page with a PHP formatted array of languages,
     * pointing to the corresponding news pages. Implementation is based
     * on the 'Corporate' site.
     * @return void
     */
    private function extractIndustryLanguages()
    {
        $html = getSimpleHTMLDOMCached('https://www.desouttertools.com/industry-4-0/news');

        $html = defaultLinkTo($html, static::URI);

        $items = $html->find('ul[class="dropdown-menu"] li');

        $list = "\t'Corporate'\n\t=> 'https://www.desouttertools.com/industry-4-0/news',\n";

        foreach ($items as $item) {
            $lang = trim($item->plaintext);
            $uri = $item->find('a', 0)->href;

            $list .= "\t'{$lang}'\n\t=> '{$uri}',\n";
        }

        echo $list;
    }
}
