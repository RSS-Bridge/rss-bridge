<?php
/**
* RssBridgeLeBonCoin
* Search LeBonCoin for most recent ads in a specific region and topic.
* Returns the most recent classified ads in results, sorting by date (most recent first).
* Region identifiers : alsace, aquitaine, auvergne, basse_normandie, bourgogne, bretagne, centre,
*     champagne_ardenne, corse, franche_comte, haute_normandie, ile_de_france, languedoc_roussillon,
*     limousin, lorraine, midi_pyrenees, nord_pas_de_calais, pays_de_la_loire, picardie,
*     poitou_charentes, provence_alpes_cote_d_azur, rhone_alpes, guadeloupe, martinique, guyane, reunion.
* 2014-07-22
*
* @name LeBonCoin
* @homepage http://www.leboncoin.fr
* @description Returns most recent results from LeBonCoin for a region and a keyword.
* @maintainer 16mhz
* @use1(r="Region identifier", k="Keyword")
*/

class LeBonCoinBridge extends BridgeAbstract{

    public function collectData(array $param){

        $html = '';
        $link = 'http://www.leboncoin.fr/annonces/offres/' . $param[r] . '/?f=a&th=1&q=' . $param[k];
        $html = file_get_html($link) or $this->returnError('Could not request LeBonCoin.', 404);

        $list = $html->find('.list-lbc', 0);
        $tags = $list->find('a');

        foreach($tags as $element) {
                $item = new \Item();
                $item->uri = $element->href;
                $title = $element->getAttribute('title');

                $content = '<img src="' . $element->find('div.image', 0)->find('img', 0)->getAttribute('src') . '" alt="thumbnail">';
                $date = $element->find('div.date', 0)->find('div', 0) . $element->find('div.date', 0)->find('div', 1) . '<br/>';
                $detailsList = $element->find('div.detail', 0);

                for ($i = 1; $i < 4; $i++) {
                    $line = $detailsList->find('div', $i);
                    $content .= $line;
                }

                $item->title = $title . ' - ' . $detailsList->find('div', 3);
                $item->content = $content . $date;
                $this->items[] = $item;
        }
  }

    public function getName(){
        return 'LeBonCoin';
    }

    public function getURI(){
        return 'http://www.leboncoin.fr';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
