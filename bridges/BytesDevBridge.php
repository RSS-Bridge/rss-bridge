<?php
class BytesDevBridge extends BridgeAbstract {

    const MAINTAINER = 'DNO';
    const NAME = 'Bytes Dev Bridge v0.25';
    const URI = 'https://bytes.dev/archives';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns the newest articles from Bytes Dev';
    public function collectData() {
        $html = getSimpleHTMLDOM($this->getURI())
            or returnServerError('Failed to load ' . $this->getURI());

        $counter = 0;
        foreach ($html->find('ul.grid li') as $element) {
            if ($counter >= 10 ) { break; }
            $counter++;
            $item = array();
            $item['uri'] = "https://bytes.dev". $element->find('a', 0)->href;
            $item['title'] = $element->find('h3', 0)->plaintext;
            $item['timestamp'] = strtotime($element->find('span', 0)->plaintext);
            $imageElement = $element->find('img', 0);
            if ($imageElement) {
                $item['enclosures'][] = $imageElement->src;
            }
            

            // Get Article Text first 1000 chars
            $ARTICLE_LENGHT = 1000;
            $linkedArticle = getSimpleHTMLDOMCached($item['uri']);
            $linkedArticleContent = $linkedArticle->find('.post', 0);
            $linkedArticleContentPlain = $linkedArticleContent->plaintext;
            // Find the first image URL
            // $matches = array();
            // preg_match('/<img[^>]+src="([^">]+)"/', $linkedArticleContent, $matches);
            // if (!empty($matches)) {
            //     $item['content'] = "<img src='{$matches[1]}'><br><br>";
            // }
            $dom = new DOMDocument();
            @$dom->loadHTML($linkedArticleContent);
            $imageURLs = array();
            $dojaCatIndex = -1;

            // Find all image elements in the HTML
            $imageElements = $dom->getElementsByTagName('img');
            foreach ($imageElements as $index => $element) {
                // Get the src attribute value of each image
                $imageURL = $element->getAttribute('src');
                $imageURLs[] = $imageURL;
            }

            $item['content'] = "<div style='display: flex; align-items: flex-start; max-width: 300px;'>";
            $item['content'] .= "<img src='{$imageURLs[0]}' style='vertical-align: top;'>";
            $item['content'] .= "<img src='{$imageURLs[2]}' style='vertical-align: top;'>";
            $item['content'] .= "</div>";
                        
            $linkedArticleContentShow = strlen($linkedArticleContentPlain) > $ARTICLE_LENGHT ? substr($linkedArticleContentPlain, 0, $ARTICLE_LENGHT) . '...' : $linkedArticleContentPlain;
            $item['content'] .= "\n\n" . $linkedArticleContentShow;

            $this->items[] = $item;
        }
    }

    public function getName() {
        return $this->name ?? parent::getName();
    }

    public function getURI() {
        return $this->uri ?? parent::getURI();
    }

    public function getDescription() {
        return $this->description ?? parent::getDescription();
    }

    public function getCacheDuration() {
        return 3600; // Cache for 1 hour
    }

}
