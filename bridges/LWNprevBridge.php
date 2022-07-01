<?php

class LWNprevBridge extends BridgeAbstract
{
    const MAINTAINER = 'Pierre Mazière';
    const NAME = 'LWN Free Weekly Edition';
    const URI = 'https://lwn.net/';
    const CACHE_TIMEOUT = 604800; // 1 week
    const DESCRIPTION = 'LWN Free Weekly Edition available one week late';

    private $editionTimeStamp;

    public function getURI()
    {
        return self::URI . 'free/bigpage';
    }

    private function jumpToNextTag(&$node)
    {
        while ($node && $node->nodeType === XML_TEXT_NODE) {
            $nextNode = $node->nextSibling;
            if (!$nextNode) {
                break;
            }
            $node = $nextNode;
        }
    }

    private function jumpToPreviousTag(&$node)
    {
        while ($node && $node->nodeType === XML_TEXT_NODE) {
            $previousNode = $node->previousSibling;
            if (!$previousNode) {
                break;
            }
            $node = $previousNode;
        }
    }

    public function collectData()
    {
        // Because the LWN page is written in loose HTML and not XHTML,
        // Simple HTML Dom is not accurate enough for the job
        $content = getContents($this->getURI());

        $contents = explode('<b>Page editor</b>', $content);

        foreach ($contents as $content) {
            if (strpos($content, '<html>') === false) {
                $content = <<<EOD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><title>LWN</title></head><body>{$content}</body></html>
EOD;
            } else {
                $content = $content . '</body></html>';
            }

            libxml_use_internal_errors(true);
            $html = new DOMDocument();
            $html->loadHTML($content);
            libxml_clear_errors();

            $edition = $html->getElementsByTagName('h1');
            if ($edition->length !== 0) {
                $text = $edition->item(0)->textContent;
                $this->editionTimeStamp = strtotime(
                    substr($text, strpos($text, 'for ') + strlen('for '))
                );
            }

            if (strpos($content, 'Cat1HL') === false) {
                $items = $this->getFeatureContents($html);
            } elseif (strpos($content, 'Cat3HL') === false) {
                $items = $this->getBriefItems($html);
            } else {
                $items = $this->getAnnouncements($html);
            }

            $this->items = array_merge($this->items, $items);
        }
    }

    private function getArticleContent(&$title)
    {
        $link = $title->firstChild;
        $this->jumpToNextTag($link);
        $item['uri'] = self::URI;
        if ($link->nodeName === 'a') {
            $item['uri'] .= $link->getAttribute('href');
        }

        $item['timestamp'] = $this->editionTimeStamp;

        $node = $title;
        $content = '';
        $contentEnd = false;
        while (!$contentEnd) {
            $node = $node->nextSibling;
            if (
                !$node || (
                    $node->nodeType !== XML_TEXT_NODE &&
                    $node->nodeName === 'h2' || (
                        !is_null($node->attributes) &&
                        !is_null($class = $node->attributes->getNamedItem('class')) &&
                        in_array($class->nodeValue, ['Cat1HL','Cat2HL'])
                    )
                )
            ) {
                $contentEnd = true;
            } else {
                $content .= $node->C14N();
            }
        }
        $item['content'] = $content;
        return $item;
    }

    private function getFeatureContents(&$html)
    {
        $items = [];
        foreach ($html->getElementsByTagName('h3') as $title) {
            if ($title->getAttribute('class') !== 'SummaryHL') {
                continue;
            }

            $item = [];

            $author = $title->nextSibling;
            $this->jumpToNextTag($author);
            if ($author->getAttribute('class') === 'FeatureByline') {
                $item['author'] = $author->getElementsByTagName('b')->item(0)->textContent;
            } else {
                continue;
            }

            $item['title'] = $title->textContent;

            $items[] = array_merge($item, $this->getArticleContent($title));
        }
        return $items;
    }

    private function getItemPrefix(&$cat, &$cats)
    {
        $cat1 = '';
        $cat2 = '';
        $cat3 = '';
        switch ($cat->getAttribute('class')) {
            case 'Cat3HL':
                $cat3 = $cat->textContent;
                $cat = $cat->previousSibling;
                $this->jumpToPreviousTag($cat);
                $cats[2] = $cat3;
                if ($cat->getAttribute('class') !== 'Cat2HL') {
                    break;
                }
                // fall-through? Looks like a bug
            case 'Cat2HL':
                $cat2 = $cat->textContent;
                $cat = $cat->previousSibling;
                $this->jumpToPreviousTag($cat);
                $cats[1] = $cat2;
                if (empty($cat3)) {
                    $cats[2] = '';
                }
                if ($cat->getAttribute('class') !== 'Cat1HL') {
                    break;
                }
                // fall-through? Looks like a bug
            case 'Cat1HL':
                $cat1 = $cat->textContent;
                $cats[0] = $cat1;
                if (empty($cat3)) {
                    $cats[2] = '';
                }
                if (empty($cat2)) {
                    $cats[1] = '';
                }
                break;
            default:
                break;
        }

        $prefix = '';
        if (!empty($cats[0])) {
            $prefix .= '[' . $cats[0] . ($cats[1] ? '/' . $cats[1] : '') . '] ';
        }
        return $prefix;
    }

    private function getAnnouncements(&$html)
    {
        $items = [];
        $cats = ['','',''];

        foreach ($html->getElementsByTagName('p') as $newsletters) {
            if ($newsletters->getAttribute('class') !== 'Cat3HL') {
                continue;
            }

            $item = [];

            $item['uri'] = self::URI . '#' . count($items);

            $item['timestamp'] = $this->editionTimeStamp;

            $item['author'] = 'LWN';

            $cat = $newsletters->previousSibling;
            $this->jumpToPreviousTag($cat);
            $prefix = $this->getItemPrefix($cat, $cats);
            $item['title'] = $prefix . ' ' . $newsletters->textContent;

            $node = $newsletters;
            $content = '';
            $contentEnd = false;
            while (!$contentEnd) {
                $node = $node->nextSibling;
                if (
                    !$node || (
                        $node->nodeType !== XML_TEXT_NODE && (
                            !is_null($node->attributes) &&
                            !is_null($class = $node->attributes->getNamedItem('class')) &&
                            in_array($class->nodeValue, ['Cat1HL','Cat2HL','Cat3HL'])
                        )
                    )
                ) {
                    $contentEnd = true;
                } else {
                    $content .= $node->C14N();
                }
            }
            $item['content'] = $content;
            $items[] = $item;
        }

        foreach ($html->getElementsByTagName('h2') as $title) {
            if ($title->getAttribute('class') !== 'SummaryHL') {
                continue;
            }

            $item = [];

            $cat = $title->previousSibling;
            $this->jumpToPreviousTag($cat);
            $cat = $cat->previousSibling;
            $this->jumpToPreviousTag($cat);
            $prefix = $this->getItemPrefix($cat, $cats);
            $item['title'] = $prefix . ' ' . $title->textContent;
            $items[] = array_merge($item, $this->getArticleContent($title));
        }

        return $items;
    }

    private function getBriefItems(&$html)
    {
        $items = [];
        $cats = ['','',''];
        foreach ($html->getElementsByTagName('h2') as $title) {
            if ($title->getAttribute('class') !== 'SummaryHL') {
                continue;
            }

            $item = [];

            $cat = $title->previousSibling;
            $this->jumpToPreviousTag($cat);
            $cat = $cat->previousSibling;
            $this->jumpToPreviousTag($cat);
            $prefix = $this->getItemPrefix($cat, $cats);
            $item['title'] = $prefix . ' ' . $title->textContent;
            $items[] = array_merge($item, $this->getArticleContent($title));
        }

        return $items;
    }
}
