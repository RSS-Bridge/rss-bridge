<?php

/**
 * Expands an existing feed
 */
abstract class FeedExpander extends BridgeAbstract
{
    private array $feed;

    public function collectExpandableDatas(string $url, $maxItems = -1, $headers = [])
    {
        if (!$url) {
            throw new \Exception('There is no $url for this RSS expander');
        }
        $maxItems = (int) $maxItems;
        if ($maxItems === -1) {
            $maxItems = 999;
        }
        $accept = [MrssFormat::MIME_TYPE, AtomFormat::MIME_TYPE, '*/*'];
        $httpHeaders = array_merge(['Accept: ' . implode(', ', $accept)], $headers);
        $xmlString = getContents($url, $httpHeaders);
        if ($xmlString === '') {
            throw new \Exception(sprintf('Unable to parse xml from `%s` because we got the empty string', $url), 10);
        }
        $xmlString = $this->prepareXml($xmlString);
        $feedParser = new FeedParser();
        try {
            $this->feed = $feedParser->parseFeed($xmlString);
        } catch (\Exception $e) {
            // FeedMergeBridge relies on this string
            throw new \Exception(sprintf('Failed to parse xml from %s: %s', $url, create_sane_exception_message($e)));
        }

        $items = array_slice($this->feed['items'], 0, $maxItems);
        // todo: extract parse logic out from FeedParser
        foreach ($items as $item) {
            // Give bridges a chance to modify the item
            $item = $this->parseItem($item);
            if ($item) {
                $this->items[] = $item;
            }
        }
    }

    /**
     * This method is overridden by bridges
     *
     * @return array
     */
    protected function parseItem(array $item)
    {
        return $item;
    }

    /**
    * Prepare XML document to make it more acceptable by the parser
    * This method can be overriden by bridges to change this behavior
    *
    * @return string
    */
    protected function prepareXml($xmlString)
    {
        // Remove problematic escape sequences
        $problematicStrings = [
            '&nbsp;',
            '&raquo;',
            '&rsquo;',
        ];
        $xmlString = str_replace($problematicStrings, '', $xmlString);

        // Remove extra content at the end of the document, if any.
        // First retrieve tag name of root node, which is the first node following <?xml prolog,
        // Then find the last matching </tag> in xml string and strip anything beyond that.
        if (preg_match('/(?:<\?xml[^>]*\?>[^<]*<)([^ "\'>]+)/i', $xmlString, $matches) === 1)
        {
            $root_node_tag = $matches[1];
            $last_closing_occurrence = strripos($xmlString, '</' . $root_node_tag);
            if ($last_closing_occurrence !== false)
            {
                $closing_node_end = strpos($xmlString, '>', $last_closing_occurrence);
                if ($closing_node_end !== false)
                {
                    $xmlString = substr($xmlString, 0, $closing_node_end + 1);
                }
            }
        }

        return $xmlString;
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
