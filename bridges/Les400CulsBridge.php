<?php
class Les400CulsBridge extends FeedExpander{

    const MAINTAINER = "unknown";
    const NAME = "Les 400 Culs";
    const URI = "http://sexes.blogs.liberation.fr/";
    const DESCRIPTION = "La planete sexe vue par Agnes Girard via rss-bridge";

    public function collectData(){
        $this->collectExpandableDatas(self::URI . 'feeds/');
    }

    protected function parseItem($newsItem){
        return $this->parseRSS_2_0_Item($newsItem);
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
