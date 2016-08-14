<?php
class JapanExpoBridge extends BridgeAbstract{

    public function loadMetadatas() {
        $this->maintainer = 'Ginko';
        $this->name = 'Japan Expo Actualités';
        $this->uri = 'http://www.japan-expo-paris.com/fr/actualites';
        $this->description = 'Returns most recent entries from Japan Expo actualités.';
        $this->update = '2016-08-09';
        $this->parameters[] =
        '[
            {
                "name" : "Mode",
                "type" : "list",
                "identifier" : "mode",
                "values" :
                [
                    {
                        "name" : "Titles only",
                        "value" : "light"
                    },
                    {
                        "name" : "Full Contents",
                        "value" : "full"
                    }
                ]
            }
        ]';
    }

    public function collectData(array $param) {

        function french_pubdate_to_timestamp($date_to_parse) {
            return strtotime(
                strtr(
                    strtolower(str_replace('Publié le ', '', $date_to_parse)),
                    array(
                        'janvier'   => 'jan',
                        'février'   => 'feb',
                        'mars'      => 'march',
                        'avril'     => 'apr',
                        'mai'       => 'may',
                        'juin'      => 'jun',
                        'juillet'   => 'jul',
                        'août'      => 'aug',
                        'septembre' => 'sep',
                        'octobre'   => 'oct',
                        'novembre'  => 'nov',
                        'décembre'  => 'dec'
                    )
                )
            );
        }

        $convert_article_images = function ($matches) {
            if (is_array($matches) && count($matches) > 1) {
                return '<img src="'.$matches[1].'" />';
            }
        };

        $link = 'http://www.japan-expo-paris.com/fr/actualites';
        $html = $this->file_get_html($link) or $this->returnError('Could not request JapanExpo: '.$link , 500);
        $fullcontent = (!empty($param['mode']) && $param['mode'] == 'full');
        $count = 0;

        foreach ($html->find('a._tile2') as $element) {

            $url = $element->href;
            $thumbnail = 'http://s.japan-expo.com/katana/images/JES049/paris.png';
            preg_match('/url\(([^)]+)\)/', $element->find('img.rspvimgset', 0)->style, $img_search_result);
            if (count($img_search_result) >= 2)
                $thumbnail = trim($img_search_result[1], "'");

            if ($fullcontent) {
                if ($count < 5) {
                    $article_html = $this->file_get_html($url) or $this->returnError('Could not request JapanExpo: '.$url , 500);
                    $header = $article_html->find('header.pageHeadBox', 0);
                    $timestamp = strtotime($header->find('time', 0)->datetime);
                    $title_html = $header->find('div.section', 0)->next_sibling();
                    $title = $title_html->plaintext;
                    $headings = $title_html->next_sibling()->outertext;
                    $article = $article_html->find('div.content', 0)->innertext;
                    $article = preg_replace_callback('/<img [^>]+ style="[^\(]+\(\'([^\']+)\'[^>]+>/i', $convert_article_images, $article);
                    $content = $headings.$article;
                } else {
                    break;
                }
            } else {
                $date_text = $element->find('span.date', 0)->plaintext;
                $timestamp = french_pubdate_to_timestamp($date_text);
                $title = trim($element->find('span._title', 0)->plaintext);
                $content = '<img src="'.$thumbnail.'"></img><br />'.$date_text.'<br /><a href="'.$url.'">Lire l\'article</a>';
            }

            $item = new \Item();
            $item->uri = $url;
            $item->title = $title;
            $item->timestamp = $timestamp;
            $item->content = $content;
            $this->items[] = $item;
            $count++;
        }
    }

    public function getCacheDuration(){
        return 14400; // 4 hours
    }
}
