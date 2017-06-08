<?php
/**
* RssBridgeBouffonduroi
* Retrieve lastest quotes from Bouffonduroi.
* Returns the most recent quotes, sorting by date (most recent first).
*
* @name Bouffonduroi Bridge
* @description Returns latest quotes from Bouffonduroi.
*/
class BouffonduroiBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $link = 'http://Bouffonduroi.over-blog.fr/';
        $html = file_get_html($link) or $this->returnError('Could not request Bouffonduroi.', 404);
        $data = $html->find('div.article[itemscope=*]');
        $Cnt = 0;
        foreach( $data as $element) {

                $item = new \Item();
                $item->uri = $element->find('a', 0)->href;
                $item->title = 'Bouffonduroi - '.$element->find('a', 0)->plaintext;
                $item->content = $element->find('a', 0)->innertext;
                $img = $element->find('img', 0);
                if (isset($img))
                {
                        $item->content .= '<br />'.$img;
                }
                $video = $element->find('div.contenuArticle iframe', 0)->src;
                if (isset($video))
                {
                        $item->content .= '<br /><a href="' . $video . '"> Video !! </a>';
                }
                foreach($element->find('div.contenuArticle span') as $contentarticle) {
                        $item->content .= '<br />'.$contentarticle->plaintext;
                       }
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Bouffonduroi';
    }

    public function getURI(){
        return 'http://Bouffonduroi.over-blog.fr/';

    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
