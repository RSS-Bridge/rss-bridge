<?php
/**
* RssBridgeMsnMonde 
* Returns the 10 newest posts from MSN Actualités (full text)
*
* @name MSN Actu Monde
* @homepage http://news.fr.msn.com/m6-actualite/monde/
* @description Returns the 10 newest posts from MSN Actualités (full text)
* @maintainer pit-fgfjiudghdf 
* @update 2014-05-26
*/
class MsnMondeBridge extends BridgeAbstract{

    public function collectData(array $param){

    function MsnMondeExtractContent($url) {
        $html2 = file_get_html($url);
        $html2->find('div[id=m6_diaponews_placeholder]', 0)->outertext=''; //Supression de la partie "et aussi"
        $text = $html2->find('div[class=svsubtorabs]', 0)->innertext; // ajout du resume
        $text .= $html2->find('div[id=page1]', 0)->innertext;   // article
        $text = preg_replace('/<p><strong>Lire aussi.*/i','',$text); //Supression de la partie "Lire aussi"

        return $text;
    }

        $html = file_get_html('http://news.fr.msn.com/m6-actualite/RSS/News_RSS_Monde.aspx') or $this->returnError('Could not request MsnMonde.', 404);
        $limit = 0;

        foreach($html->find('item') as $element) {
         if($limit < 10) {
         $item = new \Item();
         $item->title = $element->find('title', 0)->innertext;
         $item->uri = $element->find('guid', 0)->plaintext;
         $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
         $item->content = MsnMondeExtractContent($item->uri);
         $this->items[] = $item;
         $limit++;
         }
        }

    }

    public function getName(){
        return 'MSN Actu Monde';
    }

    public function getURI(){
        return 'http://news.fr.msn.com/m6-actualite/monde/';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}

