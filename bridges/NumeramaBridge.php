<?php
class NumeramaBridge extends FeedExpander {

    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Numerama';
    const URI = 'http://www.numerama.com/';
    const DESCRIPTION = 'Returns the 5 newest posts from Numerama (full text)';

    public function collectData(){
        $this->collectExpandableDatas(self::URI . 'feed/', 5);
    }

    protected function parseItem($newsItem){
        $item = $this->parseRSS_2_0_Item($newsItem);
        $item['content'] = $this->ExtractContent($item['uri']);
        return $item;
    }

    private function ExtractContent($url){
        $article_html = $this->get_cached($url) or $this->returnServerError('Could not request Numerama: '.$url);
        $contents = $article_html->find('section[class=related-article]', 0)->innertext = ''; // remove related articles block
        $contents = '<img alt="" style="max-width:300px;" src="'.$article_html->find('meta[property=og:image]', 0)->getAttribute('content').'">'; // add post picture
        return  $contents . $article_html->find('article[class=post-content]', 0)->innertext; // extract the post
    }

    public function getCacheDuration() {
        return 1800; // 30min
    }
}
