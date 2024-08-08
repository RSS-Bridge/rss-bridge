<?php

declare(strict_types=1);

/**
 * Very basic and naive feed parser.
 *
 * Scrapes out rss 0.91, 1.0, 2.0 and atom 1.0.
 *
 * Produces array meant to be used inside rss-bridge.
 *
 * The item structure is tweaked so that it works with FeedItem
 */
final class FeedParser
{
    public function parseFeed(string $xmlString): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string(trim($xmlString));
        $xmlErrors = libxml_get_errors();
        libxml_use_internal_errors(false);
        if ($xml === false) {
            if ($xmlErrors) {
                $firstXmlErrorMessage = $xmlErrors[0]->message;
            }
            throw new \Exception(sprintf('Unable to parse xml: %s', $firstXmlErrorMessage ?? ''));
        }
        $feed = [
            'title'     => null,
            'uri'       => null,
            'icon'      => null,
            'items'     => [],
        ];
        if (isset($xml->item[0])) {
            // rss 1.0
            $channel = $xml->channel[0];
            $feed['title'] = trim((string)$channel->title);
            $feed['uri'] = trim((string)$channel->link);
            if (isset($channel->image->url)) {
                $feed['icon'] = trim((string)$channel->image->url);
            }
            foreach ($xml->item as $item) {
                $feed['items'][] = $this->parseRss1Item($item);
            }
        } elseif (isset($xml->channel[0])) {
            // rss 2.0
            $channel = $xml->channel[0];
            $feed['title'] = trim((string)$channel->title);
            $feed['uri'] = trim((string)$channel->link);
            if (isset($channel->image->url)) {
                $feed['icon'] = trim((string)$channel->image->url);
            }
            foreach ($channel->item as $item) {
                $feed['items'][] = $this->parseRss2Item($item);
            }
        } elseif (isset($xml->entry[0])) {
            // atom 1.0
            $feed['title'] = (string)$xml->title;
            // Find best link (only one, or first of 'alternate')
            if (!isset($xml->link)) {
                $feed['uri'] = '';
            } elseif (count($xml->link) === 1) {
                $feed['uri'] = (string)$xml->link[0]['href'];
            } else {
                $feed['uri'] = '';
                foreach ($xml->link as $link) {
                    if (strtolower((string) $link['rel']) === 'alternate') {
                        $feed['uri'] = (string)$link['href'];
                        break;
                    }
                }
            }
            if (isset($xml->icon)) {
                $feed['icon'] = (string) $xml->icon;
            } elseif (isset($xml->logo)) {
                $feed['icon'] = (string) $xml->logo;
            }
            foreach ($xml->entry as $item) {
                $feed['items'][] = $this->parseAtomItem($item);
            }
        } else {
            throw new \Exception('Unable to detect feed format');
        }

        return $feed;
    }

    public function parseAtomItem(\SimpleXMLElement $feedItem): array
    {
        $item = $this->parseRss2Item($feedItem);
        if (isset($feedItem->id)) {
            $item['uri'] = (string)$feedItem->id;
        }
        if (isset($feedItem->title)) {
            $item['title'] = trim(html_entity_decode((string)$feedItem->title));
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
                if (strtolower((string) $link['rel']) === 'alternate') {
                    $item['uri'] = (string)$link['href'];
                }
                if (strtolower((string) $link['rel']) === 'enclosure') {
                    $item['enclosures'][] = (string)$link['href'];
                }
            }
        }
        return $item;
    }

    public function parseRss2Item(\SimpleXMLElement $feedItem): array
    {
        $item = [
            'uri'           => '',
            'title'         => '',
            'content'       => '',
            'timestamp'     => '',
            'author'        => '',
            //'uid'           => null,
            //'categories'    => [],
            //'enclosures'    => [],
        ];

        foreach ($feedItem as $k => $v) {
            $hasChildren = count($v) !== 0;
            if (!$hasChildren) {
                $item[$k] = (string) $v;
            }
        }

        if (isset($feedItem->link)) {
            // todo: trim uri
            $item['uri'] = (string)$feedItem->link;
        }
        if (isset($feedItem->title)) {
            $item['title'] = trim(html_entity_decode((string)$feedItem->title));
        }
        if (isset($feedItem->description)) {
            $item['content'] = (string)$feedItem->description;
        }

        $namespaces = $feedItem->getNamespaces(true);
        if (isset($namespaces['dc'])) {
            $dc = $feedItem->children($namespaces['dc']);
        }
        if (isset($namespaces['media'])) {
            $media = $feedItem->children($namespaces['media']);
        }

        if (isset($namespaces['content'])) {
            $content = $feedItem->children($namespaces['content']);
            $item['content'] = (string) $content;
        }

        foreach ($namespaces as $namespaceName => $namespaceUrl) {
            if (in_array($namespaceName, ['', 'content', 'media'])) {
                continue;
            }
            $item[$namespaceName] = $this->parseModule($feedItem, $namespaceName, $namespaceUrl);
        }
        if (isset($namespaces['itunes'])) {
            $enclosure = $feedItem->enclosure;
            $item['enclosure'] = [
                'url'       => (string) $enclosure['url'],
                'length'    => (string) $enclosure['length'],
                'type'      => (string) $enclosure['type'],
            ];
        }
        if (!$item['uri']) {
            // Let's use guid as uri if it's a permalink
            if (isset($feedItem->guid)) {
                foreach ($feedItem->guid->attributes() as $attribute => $value) {
                    if ($attribute === 'isPermaLink' && ($value === 'true' || (filter_var($feedItem->guid, FILTER_VALIDATE_URL)))) {
                        $item['uri'] = (string) $feedItem->guid;
                        break;
                    }
                }
            }
        }

        $item['timestamp'] = $feedItem->pubDate ?? $dc->date ?? '';
        $item['timestamp'] = strtotime((string) $item['timestamp']);

        $item['author'] = $feedItem->author ?? $feedItem->creator ?? $dc->creator ?? $media->credit ?? '';
        $item['author'] = (string) $item['author'];

        if (isset($feedItem->enclosure) && !empty($feedItem->enclosure['url'])) {
            $item['enclosures'] = [
                (string) $feedItem->enclosure['url'],
            ];
        }
        return $item;
    }

    public function parseRss1Item(\SimpleXMLElement $feedItem): array
    {
        $item = [
            'uri'           => '',
            'title'         => '',
            'content'       => '',
            'timestamp'     => '',
            'author'        => '',
            //'uid'           => null,
            //'categories'    => [],
            //'enclosures'    => [],
        ];
        if (isset($feedItem->link)) {
            // todo: trim uri
            $item['uri'] = (string)$feedItem->link;
        }
        if (isset($feedItem->title)) {
            $item['title'] = html_entity_decode((string)$feedItem->title);
        }
        if (isset($feedItem->description)) {
            $item['content'] = (string)$feedItem->description;
        }
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

    private function parseModule(\SimpleXMLElement $element, string $namespaceName, string $namespaceUrl): array
    {
        $result = [];
        $module = $element->children($namespaceUrl);
        foreach ($module as $name => $value) {
            // todo: add custom parsing if it's something other than a string
            $result[$name] = (string) $value;
        }
        return $result;
    }
}
