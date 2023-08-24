<?php

class Drive2ruBridge extends BridgeAbstract
{
    const MAINTAINER = 'dotter-ak';
    const NAME = 'Drive2.ru';
    const URI = 'https://drive2.ru/';
    const DESCRIPTION = 'Лента новостей и тестдрайвов, бортжурналов по выбранной марке или модели
		(также работает с фильтром по категориям), блогов пользователей и публикаций по темам.';
    const PARAMETERS = [
        'Новости и тест-драйвы' => [],
        'Бортжурналы (По модели или марке)' => [
            'url' => [
                'name' => 'Ссылка на страницу с бортжурналом',
                'type' => 'text',
                'required' => true,
                'title' => 'Например: https://www.drive2.ru/experience/suzuki/g4895/',
                'exampleValue' => 'https://www.drive2.ru/experience/suzuki/g4895/'
            ],
        ],
        'Личные блоги' => [
            'username' => [
                'name' => 'Никнейм пользователя на сайте',
                'type' => 'text',
                'required' => true,
                'title' => 'Например: Mickey',
                'exampleValue' => 'Mickey'
            ]
        ],
        'Публикации по темам (Стоит почитать)' => [
            'topic' => [
                'name' => 'Темы',
                'type' => 'list',
                'values' => [
                    'Автозвук' => '16',
                    'Автомобильный дизайн' => '10',
                    'Автоспорт' => '11',
                    'Автошоу, музеи, выставки' => '12',
                    'Безопасность' => '18',
                    'Беспилотные автомобили' => '15',
                    'Видеосюжеты' => '20',
                    'Вне дорог' => '21',
                    'Встречи' => '22',
                    'Выбор и покупка машины' => '23',
                    'Гаджеты' => '30',
                    'Гибридные машины' => '32',
                    'Грузовики, автобусы, спецтехника' => '31',
                    'Доработка интерьера' => '35',
                    'Законодательство' => '40',
                    'История автомобилестроения' => '50',
                    'Мототехника' => '60',
                    'Новые модели и концепты' => '85',
                    'Обучение вождению' => '70',
                    'Путешествия' => '80',
                    'Ремонт и обслуживание' => '90',
                    'Реставрация ретро-авто' => '91',
                    'Сделай сам' => '104',
                    'Смешное' => '103',
                    'Спорткары' => '102',
                    'Стайлинг' => '101',
                    'Тест-драйвы' => '110',
                    'Тюнинг' => '111',
                    'Фотосессии' => '120',
                    'Шины и диски' => '140',
                    'Электрика' => '130',
                    'Электромобили' => '131'
                ],
                'defaultValue' => '16',
            ]
        ],
        'global' => [
            'full_articles' => [
                'name' => 'Загружать в ленту полный текст',
                'type' => 'checkbox'
            ]
        ]
    ];

    private $title;

    private function getUserContent($url)
    {
        $html = getSimpleHTMLDOM($url);
        $this->title = $html->find('title', 0)->innertext;
        $articles = $html->find('div.js-entity');
        foreach ($articles as $article) {
            $item = [];
            $item['title'] = $article->find('a.c-link--text', 0)->plaintext;
            $item['uri'] = urljoin(self::URI, $article->find('a.c-link--text', 0)->href);
            if ($this->getInput('full_articles')) {
                $item['content'] = $this->addCommentsLink(
                    $this->adjustContent(getSimpleHTMLDomCached($item['uri'])->find('div.c-post__body', 0))->innertext,
                    $item['uri']
                );
            } else {
                $item['content'] = $this->addReadMoreLink($article->find('div.c-post-preview__lead', 0), $item['uri']);
            }
            $item['author'] = $article->find('a.c-username--wrap', 0)->plaintext;
            if (!is_null($article->find('img', 1))) {
                $item['enclosures'][] = $article->find('img', 1)->src;
            }
            $this->items[] = $item;
        }
    }

    private function getLogbooksContent($url)
    {
        $html = getSimpleHTMLDOM($url);
        $this->title = $html->find('title', 0)->innertext;
        $articles = $html->find('div.js-entity');
        foreach ($articles as $article) {
            $item = [];
            $item['title'] = $article->find('a.c-link--text', 1)->plaintext;
            $item['uri'] = urljoin(self::URI, $article->find('a.c-link--text', 1)->href);
            if ($this->getInput('full_articles')) {
                $item['content'] = $this->addCommentsLink(
                    $this->adjustContent(getSimpleHTMLDomCached($item['uri'])->find('div.c-post__body', 0))->innertext,
                    $item['uri']
                );
            } else {
                $item['content'] = $this->addReadMoreLink($article->find('div.c-post-preview__lead', 0), $item['uri']);
            }
            $item['author'] = $article->find('a.c-username--wrap', 0)->plaintext;
            if (!is_null($article->find('img', 1))) {
                $item['enclosures'][] = $article->find('img', 1)->src;
            }
            $this->items[] = $item;
        }
    }

    private function getNews()
    {
        $html = getSimpleHTMLDOM('https://www.drive2.ru/editorial/');
        $this->title = $html->find('title', 0)->innertext;
        $articles = $html->find('div.c-article-card');
        foreach ($articles as $article) {
            $item = [];
            $item['title'] = $article->find('a.c-link--text', 0)->plaintext;
            $item['uri'] = urljoin(self::URI, $article->find('a.c-link--text', 0)->href);
            if ($this->getInput('full_articles')) {
                $item['content'] = $this->addCommentsLink(
                    $this->adjustContent(getSimpleHTMLDomCached($item['uri'])->find('div.article', 0))->innertext,
                    $item['uri']
                );
            } else {
                $item['content'] = $this->addReadMoreLink($article->find('div.c-article-card__lead', 0), $item['uri']);
            }
            $item['author'] = 'Новости и тест-драйвы на Drive2.ru';
            if (!is_null($article->find('img', 0))) {
                $item['enclosures'][] = $article->find('img', 0)->src;
            }
            $this->items[] = $item;
        }
    }

    private function adjustContent($content)
    {
        foreach ($content->find('div.o-group') as $node) {
            $node->outertext = '';
        }
        foreach ($content->find('div, span') as $attrs) {
            foreach ($attrs->getAllAttributes() as $attr => $val) {
                $attrs->removeAttribute($attr);
            }
        }
        foreach ($content->getElementsByTagName('figcaption') as $attrs) {
            $attrs->setAttribute(
                'style',
                'font-style: italic; font-size: small; margin: 0 100px 75px;'
            );
        }
        foreach ($content->find('script') as $node) {
            $node->outertext = '';
        }
        foreach ($content->find('iframe') as $node) {
            preg_match('/embed\/(.*?)\?/', $node->src, $match);
            $node->outertext = '<a href="https://www.youtube.com/watch?v=' . $match[1] .
                '">https://www.youtube.com/watch?v=' . $match[1] . '</a>';
        }
        return $content;
    }

    private function addCommentsLink($content, $url)
    {
        return $content . '<br><a href="' . $url . '#comments">Перейти к комментариям</a>';
    }

    private function addReadMoreLink($content, $url)
    {
        if (!is_null($content)) {
            return preg_replace('!\s+!', ' ', str_replace('Читать дальше', '', $content->plaintext)) .
                '<br><a href="' . $url . '">Читать далее</a>';
        } else {
            return '';
        }
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            default:
            case 'Новости и тест-драйвы':
                $this->getNews();
                break;
            case 'Бортжурналы (По модели или марке)':
                if (!preg_match('/^https:\/\/www.drive2.ru\/experience/', $this->getInput('url'))) {
                    returnServerError('Invalid url');
                }
                $this->getLogbooksContent($this->getInput('url'));
                break;
            case 'Личные блоги':
                if (!preg_match('/^[a-zA-Z0-9-]{3,16}$/', $this->getInput('username'))) {
                    returnServerError('Invalid username');
                }
                $this->getUserContent('https://www.drive2.ru/users/' . $this->getInput('username'));
                break;
            case 'Публикации по темам (Стоит почитать)':
                $this->getUserContent('https://www.drive2.ru/topics/' . $this->getInput('topic'));
                break;
        }
    }

    public function getName()
    {
        return $this->title ?: parent::getName();
    }

    public function getIcon()
    {
        return 'https://www.drive2.ru/favicon.ico';
    }
}
