<?php

class FlashbackBridge extends BridgeAbstract
{
    const MAINTAINER = 'fatuus';
    const NAME = 'Flashback forum';
    const URI = 'https://www.flashback.org';
    const DESCRIPTION = 'Returns post from forum';
    const CACHE_TIMEOUT = 10800; // 3h

    const PARAMETERS = [
    'Category' => [
    'c' => [
                'name' => 'Category number',
                'type' => 'number',
                'exampleValue' => '249',
                'required' => true
                ]
    ],
    'Tag' => [
    'a' => [
                'name' => 'Tag',
                'type' => 'text',
                'exampleValue' => 'stockholm',
                'required' => true
                ]
    ],
    'Thread' => [
    't' => [
                'name' => 'Thread number',
                'type' => 'number',
                'exampleValue' => '1420554',
                'required' => true
                ]
    ],
    /*'User' => array(
    'u' => array(
                'name' => 'User number',
                'type' => 'text',
                'exampleValue' => 'not working, need login',
                'required' => true
                )
    ),*/
    'Search string' => [
    's' => [
                'name' => 'Words',
                'type' => 'text',
                'exampleValue' => 'sök',
                'required' => true
                ],
    'type' => [
                'name' => 'Type of search',
                'type' => 'list',
                'defaultValue' => 'Posts',
                'values' => [
                    'Posts' => 'posts',
                    'Subjects' => 'subjects'
                ]
    ]
    ]
    ];

    public function getName()
    {
        if ($this->getInput('c')) {
            $category = $this->getInput('c');
            return 'Category ' . $category . ' - Flashback';
        } elseif ($this->getInput('a')) {
            $tag = $this->getInput('a');
            return 'Tag: ' . $tag . ' - Flashback';
        } elseif ($this->getInput('t')) {
            $thread = $this->getInput('t');
            return 'Thread ' . $thread . ' - Flashback';
        } elseif ($this->getInput('u')) {
            $user = $this->getInput('u');
            return 'User ' . $user . ' - Flashback';
        } elseif ($this->getInput('s')) {
            $search = $this->getInput('s');
            return 'Search: ' . $search . ' - Flashback';
        }

        return self::NAME;
    }

    public function collectData()
    {
        if ($this->getInput('c')) {
            $page = self::URI . '/f' . $this->getInput('c');
        } elseif ($this->getInput('a')) {
            $page = self::URI . '/find_threads_by_tag.php?tag=' . $this->getInput('a');
        } elseif ($this->getInput('t')) {
            $page = self::URI . '/t' . $this->getInput('t');
            $page = $page . 's'; // last-page
        } elseif ($this->getInput('u')) {
            $page = self::URI . '/find_posts_by_user.php?userid=' . $this->getInput('u');
        } elseif ($this->getInput('s')) {
            if ($this->getInput('type') == 'posts') {
                $page = self::URI . '/sok/?query=' . $this->getInput('s') . '&search_post=1&sp=1&so=pd';
            } else {
                $page = self::URI . '/sok/?query=' . $this->getInput('s') . '&search_post=0&sp=1&so=pd';
            }
        }

        $html = getSimpleHTMLDOM($page);

        if ($this->getInput('c') || $this->getInput('a')) {
            $category = $this->getInput('c');
            $array = $html->find('table#threadslist tbody tr');
            foreach ($array as $key => $element) {
                $item = [];
                $item['uri'] = self::URI . $element->find('td.td_title a', 0)->href;
                $item['title'] = trim(utf8_encode($element->find('td.td_title a', 0)->innertext));
                $item['author'] = trim(utf8_encode(
                    $element->find('td.td_title span.thread-poster span', 0)->innertext
                ));
                $timestamp = $element->find('td.td_last_post div', 0);
                if (isset($timestamp->plaintext)) {
                    $item['timestamp'] = strtotime(str_replace(
                        ['Ig&aring;r', 'Idag'],
                        ['yesterday', 'today'],
                        trim($timestamp->plaintext)
                    ));
                }
                $item['content'] = $item['title'] . '<br />' . trim(preg_replace(
                    '/\t+/',
                    '',
                    $element->find('td.td_replies', 0)->innertext
                ));
                $item['uid'] = preg_split('/(\/)/', $element->find('td.td_title a', 0)->href)[1];
                $this->items[] = $item;
            }
        } elseif ($this->getInput('t')) {
            $tags = $html->find('div.hidden-xs a.tag');
            $array = $html->find('div.post');

            foreach ($array as $key => $element) {
                $item = [];
                $item['uri_post'] = self::URI . $element->find('div.post-heading a', 2)->href;
                $item['uri'] = self::URI . '/' . preg_split('/(\/s)/', $item['uri_post'])[1] . '#' .
                    preg_split('/(\/s)/', $item['uri_post'])[1];
                $item['uri_thread'] = $page;
                $item['author'] = utf8_encode($element->find('div.post-user ul li', 0)->innertext);
                $item['author_link'] = self::URI . $element->find('div.post-user ul li a', 0)->href;
                $item['post_nr'] = $element->find('div.post-heading a strong', 0)->innertext;
                $item['timestamp'] = strtotime(
                    str_replace(
                        ['Ig&aring;r', 'Idag'],
                        ['yesterday', 'today'],
                        current(explode("\t", str_replace("\t\t", "\t", trim(
                            $element->find('div.post-heading', 0)->plaintext
                        ))))
                    )
                );
                if ($element->find('div.smallfont strong', 0)) {
                    $item['title'] = trim(utf8_encode($element->find('div.smallfont strong', 0)->innertext));
                }
                if (empty($item['title'])) {
                    $item['title'] = date('D j M y H:i', $item['timestamp']);
                }
                $item['content'] = trim(preg_replace('/\t+/', '', $element->find('div.post_message', 0)));
                $item['uid'] = preg_split('/(\#|\/)/', $element->find('div.post-heading a', 2)->href)[1];
                foreach ($tags as $tag_key => $tag) {
                    $item['categories'][] = trim(utf8_encode($tag->innertext));
                }
                $this->items[] = $item;
            }
            // } elseif ( $this->getInput('u') ) {
        } elseif ($this->getInput('s')) {
            $array = $html->find('div.post');
            foreach ($array as $key => $element) {
                $item = [];
                $item['uri'] = self::URI . $element->find('div.post-body a', 0)->href;
                $item['uri_thread'] = $page . $element->find('div.post-heading a', 0)->href . 's';
                $item['author'] = $element->find('div.post-body a', 1)->innertext;
                $item['author_link'] = self::URI . $element->find('div.post-body a', 1)->href;
                $time = preg_split('/(\>)/', $element->find('div.post-heading', 0)->innertext);
                $item['timestamp'] = strtotime(trim(end($time)));
                $item['title'] = trim(utf8_encode($element->find('div.post-body strong', 0)->innertext));
                if (empty($item['title'])) {
                    $item['title'] = date('D j M y H:i', $item['timestamp']);
                }

                $item['datetime'] = (trim(end($time)));
                $item['categories'][] = trim(utf8_encode($element->find('div.post-heading a', 0)->innertext));
                $item['content'] = trim(preg_replace('/\t+/', '', $element->find('div.post_message', 0)));
                $item['uid'] = preg_split('/(\#|\/)/', $element->find('div.post-body a', 0)->href)[1];
                $this->items[] = $item;
            }
        }
    }
}
