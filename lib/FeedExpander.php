<?php

/**
 * Expands an existing feed
 */
abstract class FeedExpander extends BridgeAbstract
{
    private array $feed;

    public function collectExpandableDatas(string $url, $maxItems = -1)
    {
        if (!$url) {
            throw new \Exception('There is no $url for this RSS expander');
        }
        $maxItems = (int) $maxItems;
        if ($maxItems === -1) {
            $maxItems = 999;
        }
        $accept = [MrssFormat::MIME_TYPE, AtomFormat::MIME_TYPE, '*/*'];
        $httpHeaders = ['Accept: ' . implode(', ', $accept)];
        $xmlString = getContents($url, $httpHeaders);
        if ($xmlString === '') {
            throw new \Exception(sprintf('Unable to parse xml from `%s` because we got the empty string', $url), 10);
        }
        // prepare/massage the xml to make it more acceptable
        $badStrings = [
            '&nbsp;',
            '&raquo;',
        ];
        $xmlString = str_replace($badStrings, '', $xmlString);
        $feedParser = new FeedParser();
        $this->feed = $feedParser->parseFeed($xmlString);
        $items = array_slice($this->feed['items'], 0, $maxItems);
        foreach ($items as $item) {
            // Give bridges a chance to modify the item
            $item = $this->parseItem($item);
            if ($item) {
                $this->items[] = $item;
            }
        }
    }

    /**
     * This method is overidden by bridges
     *
     * @return array
     */
    protected function parseItem(array $item)
    {
        return $item;
    }

    public function getURI()
    {
        return $this->feed['uri'] ?? parent::getURI();
    }

    public function getName()
    {
        return $this->feed['title'] ?? parent::getName();
    }

    public function getIcon()
    {
        return $this->feed['icon'] ?? parent::getIcon();
    }
}
