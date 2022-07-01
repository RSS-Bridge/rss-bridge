<?php

class JapanExpoBridge extends BridgeAbstract
{
    const MAINTAINER = 'Ginko';
    const NAME = 'Japan Expo Actualités';
    const URI = 'https://www.japan-expo-paris.com/fr/actualites';
    const CACHE_TIMEOUT = 14400; // 4h
    const DESCRIPTION = 'Returns most recent entries from Japan Expo actualités.';
    const PARAMETERS = [ [
        'mode' => [
            'name' => 'Show full contents',
            'type' => 'checkbox',
        ]
    ]];

    public function getIcon()
    {
        return 'https://s.japan-expo.com/katana/images/JES073/favicons/paris.png';
    }

    public function collectData()
    {
        $convert_article_images = function ($matches) {
            if (is_array($matches) && count($matches) > 1) {
                return '<img src="' . $matches[1] . '" />';
            }
        };

        $html = getSimpleHTMLDOM(self::URI);
        $fullcontent = $this->getInput('mode');
        $count = 0;

        foreach ($html->find('a._tile2') as $element) {
            $url = $element->href;
            $thumbnail = 'https://s.japan-expo.com/katana/images/JES049/paris.png';
            preg_match('/url\(([^)]+)\)/', $element->find('img.rspvimgset', 0)->style, $img_search_result);

            if (count($img_search_result) >= 2) {
                $thumbnail = trim($img_search_result[1], "'");
            }

            if ($fullcontent) {
                if ($count >= 5) {
                    break;
                }

                $article_html = getSimpleHTMLDOMCached($url);
                $header = $article_html->find('header.pageHeadBox', 0);
                $timestamp = strtotime($header->find('time', 0)->datetime);
                $title_html = $header->find('div.section', 0)->next_sibling();
                $title = $title_html->plaintext;
                $headings = $title_html->next_sibling()->outertext;
                $article = $article_html->find('div.content', 0)->innertext;
                $article = preg_replace_callback(
                    '/<img [^>]+ style="[^\(]+\(\'([^\']+)\'[^>]+>/i',
                    $convert_article_images,
                    $article
                );

                $content = $headings . $article;
            } else {
                $date_text = $element->find('span.date', 0)->plaintext;
                $timestamp = $this->frenchPubDateToTimestamp($date_text);
                $title = trim($element->find('span._title', 0)->plaintext);
                $content = '<img src="'
                . $thumbnail
                . '"></img><br />'
                . $date_text
                . '<br /><a href="'
                . $url
                . '">Lire l\'article</a>';
            }

            $item = [];
            $item['uri'] = $url;
            $item['title'] = $title;
            $item['timestamp'] = $timestamp;
            $item['enclosures'] = [$thumbnail];
            $item['content'] = $content;
            $this->items[] = $item;
            $count++;
        }
    }

    private function frenchPubDateToTimestamp($date_to_parse)
    {
        return strtotime(
            strtr(
                strtolower(str_replace('Publié le ', '', $date_to_parse)),
                [
                    'janvier' => 'jan',
                    'février' => 'feb',
                    'mars' => 'march',
                    'avril' => 'apr',
                    'mai' => 'may',
                    'juin' => 'jun',
                    'juillet' => 'jul',
                    'août' => 'aug',
                    'septembre' => 'sep',
                    'octobre' => 'oct',
                    'novembre' => 'nov',
                    'décembre' => 'dec'
                ]
            )
        );
    }
}
