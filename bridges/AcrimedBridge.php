<?php
class AcrimedBridge extends FeedExpander {

    const MAINTAINER = "qwertygc";
    const NAME = "Acrimed Bridge";
    const URI = "http://www.acrimed.org/";
    const CACHE_TIMEOUT = 4800; //2hours
    const DESCRIPTION = "Returns the newest articles.";

    public function collectData(){
        $this->collectExpandableDatas(static::URI.'spip.php?page=backend');
    }

    protected function parseItem($newsItem){
        $item = parent::parseItem($newsItem);

        $articlePage = getSimpleHTMLDOM($newsItem->link);
        $article = sanitize($articlePage->find('article.article1', 0)->innertext);
        $article = defaultImageSrcTo($article, static::URI);
        $item['content'] = $article;

        return $item;
    }

}
