<?php

declare(strict_types=1);

final class FeedParser
{
    public function parseAtomItem(\SimpleXMLElement $feedItem): array
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

    public function parseRss1Item(\SimpleXMLElement $feedItem): array
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

    public function parseRss091Item(\SimpleXMLElement $feedItem): array
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
}
