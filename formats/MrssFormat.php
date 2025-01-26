<?php

/**
 * MrssFormat - RSS 2.0 + Media RSS
 * http://www.rssboard.org/rss-specification
 * http://www.rssboard.org/media-rss
 *
 * Validators:
 * https://validator.w3.org/feed/
 * http://www.rssboard.org/rss-validator/
 *
 * Notes about the implementation:
 *
 * - The item author is not supported as it needs to be an e-mail address to be
 *   valid.
 * - The RSS specification does not explicitly allow to have more than one
 *   enclosure as every item is meant to provide one "story", thus having
 *   multiple enclosures per item may lead to unexpected behavior.
 *   On top of that, it requires to have a length specified, which RSS-Bridge
 *   can't provide.
 * - The Media RSS extension comes in handy, since it allows to have multiple
 *   enclosures, even though they recommend to have only one enclosure because
 *   of the one-story-per-item reason. It only requires to specify the URL,
 *   everything else is optional.
 * - Since the Media RSS extension has its own namespace, the output is a valid
 *   RSS 2.0 feed that works with feed readers that don't support the extension.
 */
class MrssFormat extends FormatAbstract
{
    const MIME_TYPE = 'application/rss+xml';

    protected const ATOM_NS = 'http://www.w3.org/2005/Atom';
    protected const MRSS_NS = 'http://search.yahoo.com/mrss/';

    public function render(): string
    {
        $document = new \DomDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $feed = $document->createElement('rss');
        $document->appendChild($feed);
        $feed->setAttribute('version', '2.0');
        $feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', self::ATOM_NS);
        $feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:media', self::MRSS_NS);

        $channel = $document->createElement('channel');
        $feed->appendChild($channel);

        $feedArray = $this->getFeed();
        $uri = $feedArray['uri'];
        $title = $feedArray['name'];

        foreach ($feedArray as $feedKey => $feedValue) {
            if (in_array($feedKey, ['atom', 'donationUri'])) {
                continue;
            }
            if ($feedKey === 'name') {
                $channelTitle = $document->createElement('title');
                $channel->appendChild($channelTitle);
                $channelTitle->appendChild($document->createTextNode($title));

                $description = $document->createElement('description');
                $channel->appendChild($description);
                $description->appendChild($document->createTextNode($title));
            } elseif ($feedKey === 'uri') {
                $link = $document->createElement('link');
                $channel->appendChild($link);
                $link->appendChild($document->createTextNode($uri));

                $linkAlternate = $document->createElementNS(self::ATOM_NS, 'link');
                $channel->appendChild($linkAlternate);
                $linkAlternate->setAttribute('rel', 'alternate');
                $linkAlternate->setAttribute('type', 'text/html');
                $linkAlternate->setAttribute('href', $uri);

                $linkSelf = $document->createElementNS(self::ATOM_NS, 'link');
                $channel->appendChild($linkSelf);
                $linkSelf->setAttribute('rel', 'self');
                $linkSelf->setAttribute('type', 'application/atom+xml');
                $feedUrl = get_current_url();
                $linkSelf->setAttribute('href', $feedUrl);
            } elseif ($feedKey === 'icon') {
                $icon = $feedValue;
                if ($icon) {
                    $feedImage = $document->createElement('image');
                    $channel->appendChild($feedImage);
                    $iconUrl = $document->createElement('url');
                    $iconUrl->appendChild($document->createTextNode($icon));
                    $feedImage->appendChild($iconUrl);
                    $iconTitle = $document->createElement('title');
                    $iconTitle->appendChild($document->createTextNode($title));
                    $feedImage->appendChild($iconTitle);
                    $iconLink = $document->createElement('link');
                    $iconLink->appendChild($document->createTextNode($uri));
                    $feedImage->appendChild($iconLink);
                }
            } elseif ($feedKey === 'itunes') {
                $feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:itunes', self::ITUNES_NS);
                foreach ($feedValue as $itunesKey => $itunesValue) {
                    $itunesProperty = $document->createElementNS(self::ITUNES_NS, $itunesKey);
                    $channel->appendChild($itunesProperty);
                    $itunesProperty->appendChild($document->createTextNode($itunesValue));
                }
            } else {
                $element = $document->createElement($feedKey);
                $channel->appendChild($element);
                $element->appendChild($document->createTextNode($feedValue));
            }
        }

        foreach ($this->getItems() as $item) {
            $itemArray = $item->toArray();
            $itemTimestamp = $item->getTimestamp();
            $itemTitle = $item->getTitle();
            $itemUri = $item->getURI();
            $itemContent = $item->getContent() ?? '';
            $itemUid = $item->getUid();
            $isPermaLink = 'false';

            if (empty($itemUid) && !empty($itemUri)) {
                // Fallback to provided URI
                $itemUid = $itemUri;
                $isPermaLink = 'true';
            }

            if (empty($itemUid)) {
                // Fallback to title and content
                $itemUid = hash('sha1', $itemTitle . $itemContent);
            }

            $entry = $document->createElement('item');
            $channel->appendChild($entry);

            if (!empty($itemTitle)) {
                $entryTitle = $document->createElement('title');
                $entry->appendChild($entryTitle);
                $entryTitle->appendChild($document->createTextNode($itemTitle));
            }

            if (isset($itemArray['itunes'])) {
                $feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:itunes', self::ITUNES_NS);
                foreach ($itemArray['itunes'] as $itunesKey => $itunesValue) {
                    $itunesProperty = $document->createElementNS(self::ITUNES_NS, $itunesKey);
                    $entry->appendChild($itunesProperty);
                    $itunesProperty->appendChild($document->createTextNode($itunesValue));
                }

                if (isset($itemArray['enclosure'])) {
                    $itunesEnclosure = $document->createElement('enclosure');
                    $entry->appendChild($itunesEnclosure);
                    $itunesEnclosure->setAttribute('url', $itemArray['enclosure']['url']);
                    $itunesEnclosure->setAttribute('length', $itemArray['enclosure']['length']);
                    $itunesEnclosure->setAttribute('type', $itemArray['enclosure']['type']);
                }
            }

            if (!empty($itemUri)) {
                $entryLink = $document->createElement('link');
                $entry->appendChild($entryLink);
                $entryLink->appendChild($document->createTextNode($itemUri));
            }

            $entryGuid = $document->createElement('guid');
            $entryGuid->setAttribute('isPermaLink', $isPermaLink);
            $entry->appendChild($entryGuid);
            $entryGuid->appendChild($document->createTextNode($itemUid));

            if (!empty($itemTimestamp)) {
                $entryPublished = $document->createElement('pubDate');
                $entry->appendChild($entryPublished);
                $entryPublished->appendChild($document->createTextNode(gmdate(\DATE_RFC2822, $itemTimestamp)));
            }

            if (!empty($itemContent)) {
                $entryDescription = $document->createElement('description');
                $entry->appendChild($entryDescription);
                $entryDescription->appendChild($document->createTextNode($itemContent));
            }

            foreach ($item->getEnclosures() as $enclosure) {
                $entryEnclosure = $document->createElementNS(self::MRSS_NS, 'content');
                $entry->appendChild($entryEnclosure);
                $entryEnclosure->setAttribute('url', $enclosure);
                $entryEnclosure->setAttribute('type', parse_mime_type($enclosure));
            }

            foreach ($item->getCategories() as $category) {
                $entryCategory = $document->createElement('category');
                $entry->appendChild($entryCategory);
                $entryCategory->appendChild($document->createTextNode($category));
            }
        }

        $xml = $document->saveXML();
        return $xml;
    }
}
