<?php

class PikabuBridge extends BridgeAbstract
{
    const NAME = 'Пикабу';
    const URI = 'https://pikabu.ru';
    const DESCRIPTION = 'Выводит посты по тегу, сообществу или пользователю';
    const MAINTAINER = 'em92';

    const PARAMETERS_FILTER = [
        'name' => 'Фильтр',
        'type' => 'list',
        'values' => [
            'Горячее' => 'hot',
            'Свежее' => 'new',
        ],
        'defaultValue' => 'hot'
    ];

    const PARAMETERS = [
        'По тегу' => [
            'tag' => [
                'name' => 'Тег',
                'exampleValue' => 'it',
                'required' => true
            ],
            'filter' => self::PARAMETERS_FILTER
        ],
        'По сообществу' => [
            'community' => [
                'name' => 'Сообщество',
                'exampleValue' => 'linux',
                'required' => true
            ],
            'filter' => self::PARAMETERS_FILTER
        ],
        'По пользователю' => [
            'user' => [
                'name' => 'Пользователь',
                'exampleValue' => 'admin',
                'required' => true
            ]
        ]
    ];

    protected $title = null;

    public function getURI()
    {
        if ($this->getInput('tag')) {
            return self::URI . '/tag/' . rawurlencode($this->getInput('tag')) . '/' . rawurlencode($this->getInput('filter'));
        } elseif ($this->getInput('user')) {
            return self::URI . '/@' . rawurlencode($this->getInput('user'));
        } elseif ($this->getInput('community')) {
            $uri = self::URI . '/community/' . rawurlencode($this->getInput('community'));
            if ($this->getInput('filter') != 'hot') {
                $uri .= '/' . rawurlencode($this->getInput('filter'));
            }
            return $uri;
        } else {
            return parent::getURI();
        }
    }

    public function getIcon()
    {
        return 'https://cs.pikabu.ru/assets/favicon.ico';
    }

    public function getName()
    {
        if (is_null($this->title)) {
            return parent::getName();
        } else {
            return $this->title . ' - ' . parent::getName();
        }
    }

    public function collectData()
    {
        $link = $this->getURI();

        $text_html = getContents($link);
        $text_html = iconv('windows-1251', 'utf-8', $text_html);
        $html = str_get_html($text_html);

        $this->title = $html->find('title', 0)->innertext;

        foreach ($html->find('article.story') as $post) {
            $time = $post->find('time.story__datetime', 0);
            if (is_null($time)) {
                continue;
            }

            $el_to_remove_selectors = [
                '.story__read-more',
                'script',
                'svg.story-image__stretch',
            ];

            foreach ($el_to_remove_selectors as $el_to_remove_selector) {
                foreach ($post->find($el_to_remove_selector) as $el) {
                    $el->outertext = '';
                }
            }

            foreach ($post->find('[data-type=gifx]') as $el) {
                $src = $el->getAttribute('data-source');
                $el->outertext = '<img src="' . $src . '">';
            }

            foreach ($post->find('img') as $img) {
                $src = $img->getAttribute('src');
                if (!$src) {
                    $src = $img->getAttribute('data-src');
                    if (!$src) {
                        continue;
                    }
                }
                $img->outertext = '<img src="' . $src . '">';

                // it is assumed, that img's parents are links to post itself
                // we don't need them
                $img->parent()->outertext = $img->outertext;
            }

            $categories = [];
            foreach ($post->find('.tags__tag') as $tag) {
                if ($tag->getAttribute('data-tag')) {
                    $categories[] = $tag->innertext;
                }
            }

            $title_element = $post->find('.story__title-link', 0);
            if (str_contains($title_element->href, 'from=cpm')) {
                // skip sponsored posts
                continue;
            }

            $title = $title_element->plaintext;
            $community_link = $post->find('.story__community-link', 0);
            // adding special marker for "Maybe News" section
            // these posts are fake
            if (!is_null($community_link) && $community_link->getAttribute('href') == '/community/maybenews') {
                $title = '[' . trim($community_link->plaintext) . '] ' . $title;
            }

            $item = [];
            $item['categories'] = $categories;
            $item['author'] = $post->find('.user__nick', 0)->innertext;
            $item['title'] = $title;
            $item['content'] = strip_tags(
                backgroundToImg($post->find('.story__content-inner', 0)->innertext),
                '<br><p><img><a><s>
			'
            );
            $item['uri'] = $title_element->href;
            $item['timestamp'] = strtotime($time->getAttribute('datetime'));
            $this->items[] = $item;
        }
    }
}
