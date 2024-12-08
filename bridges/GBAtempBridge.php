<?php

class GBAtempBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'GBAtemp';
    const URI = 'https://gbatemp.net/';
    const DESCRIPTION = 'GBAtemp is a user friendly underground video game community.';

    const PARAMETERS = [ [
        'type' => [
            'name' => 'Type',
            'type' => 'list',
            'values' => [
                'News' => 'N',
                'Reviews' => 'R',
                'Tutorials' => 'T',
                'Forum' => 'F'
            ]
        ]
    ]];

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        switch ($this->getInput('type')) {
            case 'N':
                foreach ($html->find('li.news_item.full') as $newsItem) {
                    $url = urljoin(self::URI, $newsItem->find('a', 0)->href);
                    $img = $this->findItemImage($newsItem, 'a.news_image');
                    $time = $this->findItemDate($newsItem);
                    $author = $newsItem->find('a.username', 0)->plaintext;
                    $title = $this->decodeHtmlEntities($newsItem->find('h2.news_title', 0)->plaintext);
                    $content = $this->fetchPostContent($url, self::URI);
                    $this->items[] = $this->buildItem($url, $title, $author, $time, $img, $content);
                    unset($newsItem); // Some items are heavy, freeing the item proactively helps saving memory
                }
                break;
            case 'R':
                foreach ($html->find('li.portal_review') as $reviewItem) {
                    $url = urljoin(self::URI, $reviewItem->find('a.review_boxart', 0)->href);
                    $img = $this->findItemImage($reviewItem, 'a.review_boxart');
                    $title = $this->decodeHtmlEntities($reviewItem->find('div.review_title', 0)->find('h2', 0)->plaintext);
                    $content = getSimpleHTMLDOMCached($url);
                    $author = $content->find('span.author--name', 0)->plaintext;
                    $time = $this->findItemDate($content);
                    $intro = '<p><b>' . ($content->find('div#review_introduction', 0)->plaintext) . '</b></p>';
                    $review = $content->find('div#review_main', 0)->innertext;
                    $content = $this->cleanupPostContent($intro . $review, self::URI);
                    $this->items[] = $this->buildItem($url, $title, $author, $time, $img, $content);
                    unset($reviewItem); // Free up memory
                }
                break;
            case 'T':
                foreach ($html->find('li.portal-tutorial') as $tutorialItem) {
                    $url = urljoin(self::URI, $tutorialItem->find('a', 1)->href);
                    $title = $this->decodeHtmlEntities($tutorialItem->find('a', 1)->plaintext);
                    $time = $this->findItemDate($tutorialItem);
                    $author = $tutorialItem->find('a.username', 0)->plaintext;
                    $content = $this->fetchPostContent($url, self::URI);
                    $this->items[] = $this->buildItem($url, $title, $author, $time, null, $content);
                    unset($tutorialItem); // Free up memory
                }
                break;
            case 'F':
                foreach ($html->find('li.rc_item') as $postItem) {
                    $url = urljoin(self::URI, $postItem->find('a', 1)->href);
                    $title = $this->decodeHtmlEntities($postItem->find('a', 1)->plaintext);
                    $time = $this->findItemDate($postItem);
                    $author = $postItem->find('a.username', 0)->plaintext;
                    $content = $this->fetchPostContent($url, self::URI);
                    $this->items[] = $this->buildItem($url, $title, $author, $time, null, $content);
                    unset($postItem); // Free up memory
                }
                break;
        }
    }

    private function fetchPostContent($uri, $site_url)
    {
        $html = getSimpleHTMLDOMCached($uri);
        if (!$html) {
            return 'Could not request GBAtemp: ' . $uri;
        }
        $var = $html->find('#review_main', 0);
        if (!$var) {
            $var = $html->find('div.message-userContent article.message-body', 0);
        }
        return $this->cleanupPostContent($var->innertext, $site_url);
    }

    private function buildItem($uri, $title, $author, $timestamp, $thumbnail, $content)
    {
        $item = [];
        $item['uri'] = $uri;
        $item['title'] = $title;
        $item['author'] = $author;
        $item['timestamp'] = $timestamp;
        $item['content'] = $content;
        if (!empty($thumbnail)) {
            $item['enclosures'] = [$thumbnail];
        }
        return $item;
    }

    private function decodeHtmlEntities($text)
    {
        $text = html_entity_decode($text);
        $convmap = [0x0, 0x2FFFF, 0, 0xFFFF];
        return trim(mb_decode_numericentity($text, $convmap, 'UTF-8'));
    }

    private function cleanupPostContent($content, $site_url)
    {
        $content = defaultLinkTo($content, self::URI);
        $content = stripWithDelimiters($content, '<script', '</script>');
        $content = stripWithDelimiters($content, '<svg', '</svg>');
        $content = stripRecursiveHTMLSection($content, 'div', '<div class="reactionsBar');
        return $this->decodeHtmlEntities($content);
    }

    private function findItemDate($item)
    {
        $time = 0;
        $dateField = $item->find('time', 0);
        if (is_object($dateField)) {
            $time = strtotime($dateField->datetime);
        }
        return $time;
    }

    private function findItemImage($item, $selector)
    {
        $img = extractFromDelimiters($item->find($selector, 0)->style, 'url(', ')');
        $paramPos = strpos($img, '?');
        if ($paramPos !== false) {
            $img = substr($img, 0, $paramPos);
        }
        if (!str_ends_with($img, '.png') && !str_ends_with($img, '.jpg')) {
            $img = $img . '#.image';
        }
        return urljoin(self::URI, $img);
    }

    public function getName()
    {
        if (!is_null($this->getInput('type'))) {
            return 'GBAtemp ' . $this->getKey('type') . ' Bridge';
        }

        return parent::getName();
    }
}
