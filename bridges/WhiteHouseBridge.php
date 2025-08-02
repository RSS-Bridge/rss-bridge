<?php
abstract class WhiteHouseBridge extends BridgeAbstract
{
    // This is the core scraping logic that will be shared by all White House bridges.
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
            
            // Add categories from the meta tags for better filtering
            $categories = [];
            foreach($article->find('div.taxonomy-category a') as $categoryLink) {
                $categories[] = trim($categoryLink->plaintext);
            }
            if(!empty($categories)) {
                $item['categories'] = $categories;
            }

            $item['content'] = '<p><a href="' . $item['uri'] . '">' . $item['title'] . '</a></p>';
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}
