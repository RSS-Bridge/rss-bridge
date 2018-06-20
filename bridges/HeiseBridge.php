<?php

class HeiseBridge extends FeedExpander {
    const MAINTAINER = 'Dreckiger-Dan';
    const NAME = 'Heise Online Bridge';
    const URI = 'https://heise.de/';
	const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = array(array(
        'category' => array(
            'name' => 'Category',
            'type' => 'list',
            'required' => true,
            'values' => array(
                'Alle News' => 'www.heise.de/newsticker/heise-atom.xml',
                'Top-News' => 'www.heise.de/newsticker/heise-top-atom.xml',
                'Internet-Störungen' => 'https://www.heise.de/netze/netzwerk-tools/imonitor-internet-stoerungen/feed/aktuelle-meldungen/',
                'Alle News von heise Developer' => 'www.heise.de/developer/rss/news-atom.xml'
            )
        )
    ));

    public function collectData() {
        $this->collectExpandableDatas($this->getInput('category')) or returnServerError('Error while downloading the website content');
    }

    protected function parseItem($feedItem) {
        $item = parent::parseItem($feedItem);
        
        $article = getSimpleHTMLDOMCached($item['uri']) or returnServerError('Could not open article: ' . $url);
        $article = $article->find('div.article-content', 0)->innertext;
        
        $item['content'] = $article;

        return $item;
    }
}
