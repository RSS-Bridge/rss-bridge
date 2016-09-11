<?php
class AcrimedBridge extends FeedExpander {

    const MAINTAINER = "qwertygc";
    const NAME = "Acrimed Bridge";
    const URI = "http://www.acrimed.org/";
    const DESCRIPTION = "Returns the newest articles.";

    public function collectData(){
        $this->collectExpandableDatas(static::URI.'spip.php?page=backend');
    }

    protected function parseItem($newsItem){
        $item = $this->parseRSS_2_0_Item($newsItem);

        $hs = new HTMLSanitizer();
        $articlePage = $this->getSimpleHTMLDOM($newsItem->link);
        $article = $hs->sanitize($articlePage->find('article.article1', 0)->innertext);
        $article = HTMLSanitizer::defaultImageSrcTo($article, static::URI);
        $item['content'] = $article;

        return $item;
    }

    public function getCacheDuration(){
        return 4800; // 2 hours
    }
}
