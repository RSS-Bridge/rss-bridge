<?php

/**
 * Expands an existing feed
 */
abstract class FeedExpander extends BridgeAbstract
{
    const FEED_TYPE_RSS_1_0 = 'RSS_1_0';
    const FEED_TYPE_RSS_2_0 = 'RSS_2_0';
    const FEED_TYPE_ATOM_1_0 = 'ATOM_1_0';

    private $title;
    private $uri;
    private $icon;
    private $feedType;

    public function collectExpandableDatas($url, $maxItems = -1)
    {
        if (empty($url)) {
            throw new \Exception('There is no $url for this RSS expander');
        }
        $accept = [MrssFormat::MIME_TYPE, AtomFormat::MIME_TYPE, '*/*'];
        $httpHeaders = ['Accept: ' . implode(', ', $accept)];
        // Notice we do not use cache here on purpose. We want a fresh view of the RSS stream each time
        $xmlString = getContents($url, $httpHeaders);
        if ($xmlString === '') {
            throw new \Exception(sprintf('Unable to parse xml from `%s` because we got the empty string', $url), 10);
        }
        // Maybe move this call earlier up the stack frames
        // Disable triggering of the php error-handler and handle errors manually instead
        libxml_use_internal_errors(true);
        // Consider replacing libxml with https://www.php.net/domdocument
        // Intentionally not using the silencing operator (@) because it has no effect here
        $xml = simplexml_load_string(trim($xmlString));
        if ($xml === false) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $xmlError) {
                Debug::log(trim($xmlError->message));
            }
            if ($xmlErrors) {
                // Render only the first error into exception message
                $firstXmlErrorMessage = $xmlErrors[0]->message;
            }
            throw new \Exception(sprintf('Unable to parse xml from `%s` %s', $url, $firstXmlErrorMessage ?? ''), 11);
        }
        // Restore previous behaviour in case other code relies on it being off
        libxml_use_internal_errors(false);

        if (isset($xml->item[0])) {
            $this->feedType = self::FEED_TYPE_RSS_1_0;
            $this->collectRss1($xml, $maxItems);
        } elseif (isset($xml->channel[0])) {
            $this->feedType = self::FEED_TYPE_RSS_2_0;
            $this->collectRss2($xml, $maxItems);
        } elseif (isset($xml->entry[0])) {
            $this->feedType = self::FEED_TYPE_ATOM_1_0;
            $this->collectAtom1($xml, $maxItems);
        } else {
            throw new \Exception(sprintf('Unable to detect feed format from `%s`', $url));
        }
        return $this;
    }

    protected function collectRss1(\SimpleXMLElement $xml, $maxItems)
    {
        // loadRss2Data
        $channel = $xml->channel[0];
        $this->title = trim((string)$channel->title);
        $this->uri = trim((string)$channel->link);
        if (!empty($channel->image)) {
            $this->icon = trim((string)$channel->image->url);
        }
        // todo: set title, link, description, language, and so on
        foreach ($xml->item as $item) {
            $parsedItem = $this->parseItem($item);
            if (!empty($parsedItem)) {
                $this->items[] = $parsedItem;
            }
            if ($maxItems !== -1 && count($this->items) >= $maxItems) {
                break;
            }
        }
    }

    protected function collectRss2(\SimpleXMLElement $xml, $maxItems)
    {
        // loadRss2Data
        $channel = $xml->channel[0];
        $this->title = trim((string)$channel->title);
        $this->uri = trim((string)$channel->link);
        if (!empty($channel->image)) {
            $this->icon = trim((string)$channel->image->url);
        }
        // todo: set title, link, description, language, and so on
        foreach ($channel->item as $item) {
            $parsedItem = $this->parseItem($item);
            if (!empty($parsedItem)) {
                $this->items[] = $parsedItem;
            }
            if ($maxItems !== -1 && count($this->items) >= $maxItems) {
                break;
            }
        }
    }

    protected function collectAtom1(\SimpleXMLElement $xml, $maxItems)
    {
        // loadAtomData
        $this->title = (string)$xml->title;
        // Find best link (only one, or first of 'alternate')
        if (!isset($xml->link)) {
            $this->uri = '';
        } elseif (count($xml->link) === 1) {
            $this->uri = (string)$xml->link[0]['href'];
        } else {
            $this->uri = '';
            foreach ($xml->link as $link) {
                if (strtolower($link['rel']) === 'alternate') {
                    $this->uri = (string)$link['href'];
                    break;
                }
            }
        }
        if (!empty($xml->icon)) {
            $this->icon = (string)$xml->icon;
        } elseif (!empty($xml->logo)) {
            $this->icon = (string)$xml->logo;
        }
        // parse items
        foreach ($xml->entry as $item) {
            $parsedItem = $this->parseItem($item);
            if (!empty($parsedItem)) {
                $this->items[] = $parsedItem;
            }
            if ($maxItems !== -1 && count($this->items) >= $maxItems) {
                break;
            }
        }
    }

    /**
     * @param \SimpleXMLElement $item The feed item to be parsed
     */
    protected function parseItem($item)
    {
        switch ($this->feedType) {
            case self::FEED_TYPE_RSS_1_0:
                return $this->parseRss1Item($item);
            case self::FEED_TYPE_RSS_2_0:
                return $this->parseRss2Item($item);
            case self::FEED_TYPE_ATOM_1_0:
                return $this->parseATOMItem($item);
            default:
                throw new \Exception(sprintf('Unknown version %s!', $this->getInput('version')));
        }
    }

    protected function parseATOMItem(\SimpleXMLElement $feedItem): array
    {
        // Some ATOM entries also contain RSS 2.0 fields
        $item = $this->parseRss2Item($feedItem);

        if (isset($feedItem->id)) {
            $item['uri'] = (string)$feedItem->id;
        }
        if (isset($feedItem->title)) {
            $item['title'] = html_entity_decode((string)$feedItem->title);
        }
        if (isset($feedItem->updated)) {
            $item['timestamp'] = strtotime((string)$feedItem->updated);
        }
        if (isset($feedItem->author)) {
            $item['author'] = (string)$feedItem->author->name;
        }
        if (isset($feedItem->content)) {
            $contentChildren = $feedItem->content->children();
            if (count($contentChildren) > 0) {
                $content = '';
                foreach ($contentChildren as $contentChild) {
                    $content .= $contentChild->asXML();
                }
                $item['content'] = $content;
            } else {
                $item['content'] = (string)$feedItem->content;
            }
        }

        // When "link" field is present, URL is more reliable than "id" field
        if (count($feedItem->link) === 1) {
            $item['uri'] = (string)$feedItem->link[0]['href'];
        } else {
            foreach ($feedItem->link as $link) {
                if (strtolower($link['rel']) === 'alternate') {
                    $item['uri'] = (string)$link['href'];
                }
                if (strtolower($link['rel']) === 'enclosure') {
                    $item['enclosures'][] = (string)$link['href'];
                }
            }
        }

        return $item;
    }

    protected function parseRss091Item(\SimpleXMLElement $feedItem): array
    {
        $item = [];
        if (isset($feedItem->link)) {
            $item['uri'] = (string)$feedItem->link;
        }
        if (isset($feedItem->title)) {
            $item['title'] = html_entity_decode((string)$feedItem->title);
        }
        // rss 0.91 doesn't support timestamps
        // rss 0.91 doesn't support authors
        // rss 0.91 doesn't support enclosures
        if (isset($feedItem->description)) {
            $item['content'] = (string)$feedItem->description;
        }
        return $item;
    }

    protected function parseRss1Item($feedItem): array
    {
        // 1.0 adds optional elements around the 0.91 standard
        $item = $this->parseRss091Item($feedItem);

        $namespaces = $feedItem->getNamespaces(true);
        if (isset($namespaces['dc'])) {
            $dc = $feedItem->children($namespaces['dc']);
            if (isset($dc->date)) {
                $item['timestamp'] = strtotime((string)$dc->date);
            }
            if (isset($dc->creator)) {
                $item['author'] = (string)$dc->creator;
            }
        }

        return $item;
    }

    protected function parseRss2Item(\SimpleXMLElement $feedItem): array
    {
        // Primary data is compatible to 0.91 with some additional data
        $item = $this->parseRss091Item($feedItem);

        $namespaces = $feedItem->getNamespaces(true);
        if (isset($namespaces['dc'])) {
            $dc = $feedItem->children($namespaces['dc']);
        }
        if (isset($namespaces['media'])) {
            $media = $feedItem->children($namespaces['media']);
        }

        if (isset($feedItem->guid)) {
            foreach ($feedItem->guid->attributes() as $attribute => $value) {
                if (
                    $attribute === 'isPermaLink'
                    && (
                        $value === 'true' || (
                            filter_var($feedItem->guid, FILTER_VALIDATE_URL)
                            && (empty($item['uri']) || !filter_var($item['uri'], FILTER_VALIDATE_URL))
                        )
                    )
                ) {
                    $item['uri'] = (string)$feedItem->guid;
                    break;
                }
            }
        }

        if (isset($feedItem->pubDate)) {
            $item['timestamp'] = strtotime((string)$feedItem->pubDate);
        } elseif (isset($dc->date)) {
            $item['timestamp'] = strtotime((string)$dc->date);
        }

        if (isset($feedItem->author)) {
            $item['author'] = (string)$feedItem->author;
        } elseif (isset($feedItem->creator)) {
            $item['author'] = (string)$feedItem->creator;
        } elseif (isset($dc->creator)) {
            $item['author'] = (string)$dc->creator;
        } elseif (isset($media->credit)) {
            $item['author'] = (string)$media->credit;
        }

        if (isset($feedItem->enclosure) && !empty($feedItem->enclosure['url'])) {
            $item['enclosures'] = [(string)$feedItem->enclosure['url']];
        }

        return $item;
    }

    public function getURI()
    {
        if (!empty($this->uri)) {
            return $this->uri;
        }
        return parent::getURI();
    }

    public function getName()
    {
        if (!empty($this->title)) {
            return $this->title;
        }
        return parent::getName();
    }

    public function getIcon()
    {
        if (!empty($this->icon)) {
            return $this->icon;
        }
        return parent::getIcon();
    }
}
