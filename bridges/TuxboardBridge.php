<?php
/**
 *
 * @name Tuxboard
* @homepage http://www.tuxboard.com/
 * @description Tuxboard
 * @update 15/01/2014
* initial maintainer: superbaillot.net
 */
class TuxboardBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://www.tuxboard.com') or $this->returnError('Could not request Tuxboard.', 404);

        foreach($html->find('div.posts') as $element) {
            $a = $element->find("h2 a", 0);
            $category = $element->find("div#category", 0);
            $catTxt = $category->innertext;
            $posFinDate = strpos(" -", $catTxt);
            $list = explode(" ", trim(substr($catTxt, $posFinDate)));
            $jour = $list[0];
            $mois = 1;
            $annee = $list[2];

            switch (strtolower($list[1]))
            {
                case "janvier" :
                    $mois = 1;
                    break;
                case "février" :
                case "fevrier" :
                    $mois = 2;
                    break;
                case "mars" :
                    $mois = 3;
                    break;
                case "avril" :
                    $mois = 4;
                    break;
                case "mai" :
                    $mois = 5;
                    break;
                case "juin" :
                    $mois = 6;
                    break;
                case "juillet" :
                    $mois = 7;
                    break;
                case "aout" :
                case "août" :
                    $mois = 8;
                    break;
                case "septembre" :
                    $mois = 9;
                    break;
                case "octobre" :
                    $mois = 10;
                    break;
                case "novembre" :
                    $mois = 11;
                    break;
                case "decembre" :
                case "décembre" :
                    $mois = 12;
                    break;
            }

            $item = new Item();

            $item->uri = $a->href;
            $item->title = $a->innertext;
            $item->content = trim($element->find("div.clear", 0)->innertext);
            $item->timestamp = mktime(0, 0, 0, $mois, $jour, $annee);
             
            $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Tuxboard';
    }

    public function getURI(){
        return 'http://www.tuxboard.com';
    }

    public function getDescription(){
        return 'Tuxboard via rss-bridge';
    }

    public function getCacheDuration(){
        return 14600; // 4 hours
    }
}
?>
