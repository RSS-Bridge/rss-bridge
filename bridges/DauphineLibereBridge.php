<?php
class DauphineLibereBridge extends FeedExpander {

    const MAINTAINER = "qwertygc";
    const NAME = "Dauphine Bridge";
    const URI = "http://www.ledauphine.com/";
    const DESCRIPTION = "Returns the newest articles.";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'Catégorie de l\'article',
            'type'=>'list',
            'values'=>array(
                'À la une'=>'',
                'France Monde'=>'france-monde',
                'Faits Divers'=>'faits-divers',
                'Économie et Finance'=>'economie-et-finance',
                'Politique'=>'politique',
                'Sport'=>'sport',
                'Ain'=>'ain',
                'Alpes-de-Haute-Provence'=>'haute-provence',
                'Hautes-Alpes'=>'hautes-alpes',
                'Ardèche'=>'ardeche',
                'Drôme'=>'drome',
                'Isère Sud'=>'isere-sud',
                'Savoie'=>'savoie',
                'Haute-Savoie'=>'haute-savoie',
                'Vaucluse'=>'vaucluse'
            )
        )
    ));

    public function collectData(){
        $url = self::URI . 'rss';

        if (empty($this->getInput('u'))) {
            $url = self::URI . $this->getInput('u') . '/rss';
        }

        $this->collectExpandableDatas($url, 10);
    }

    protected function parseItem($newsItem){
        $item = $this->parseRSS_2_0_Item($newsItem);
        $item['content'] = $this->ExtractContent($item['uri']);
        return $item;
    }

    private function ExtractContent($url) {
        $html2 = $this->get_cached($url);
        $text = $html2->find('div.column', 0)->innertext;
        $text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
        return $text;
    }

    public function getCacheDuration(){
        return 3600*2; // 2 hours
    }
}
?>
