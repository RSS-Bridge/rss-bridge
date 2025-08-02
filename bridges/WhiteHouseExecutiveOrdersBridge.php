<?php
class WhiteHouseExecutiveOrdersBridge extends BridgeAbstract
{
    const MAINTAINER = 'sij-ai'; 
    const NAME = 'White House Executive Orders';
    const URI = 'https://www.whitehouse.gov/presidential-actions/executive-orders/';
    const CACHE_TIMEOUT = 3600;
    const DESCRIPTION = 'Returns the latest executive orders from The White House.';

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $articles = $html->find('ul.wp-block-post-template li.wp-block-post');

        foreach ($articles as $article) {
            $item = [];
            $titleElement = $article->find('h2.wp-block-post-title a', 0);
            if (!$titleElement) {
                continue;
            }

            $item['title'] = trim($titleElement->plaintext);
            $item['uri'] = $titleElement->href;

            $dateElement = $article->find('div.wp-block-post-date time', 0);
            if ($dateElement && !empty($dateElement->datetime)) {
                $item['timestamp'] = strtotime($dateElement->datetime);
            }
          
            $item['content'] = '<p><a href="' . $item['uri'] . '">Read the full executive order: ' . $item['title'] . '</a></p>';
            $item['uid'] = $item['uri'];
            $this->items[] = $item;
        }
    }
}
