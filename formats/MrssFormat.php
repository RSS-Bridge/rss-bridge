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

    public function stringify()
    {
        $feedUrl = get_current_url();
        $extraInfos = $this->getExtraInfos();
        if (empty($extraInfos['uri'])) {
            $uri = REPOSITORY;
        } else {
            $uri = $extraInfos['uri'];
        }

        $document = new \DomDocument('1.0', $this->getCharset());
        $document->formatOutput = true;
        $feed = $document->createElement('rss');
        $document->appendChild($feed);
        $feed->setAttribute('version', '2.0');
        $feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', self::ATOM_NS);
        $feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:media', self::MRSS_NS);

        $channel = $document->createElement('channel');
        $feed->appendChild($channel);

        $title = $extraInfos['name'];
        $channelTitle = $document->createElement('title');
        $channel->appendChild($channelTitle);
        $channelTitle->appendChild($document->createTextNode($title));

        $link = $document->createElement('link');
        $channel->appendChild($link);
        $link->appendChild($document->createTextNode($uri));

        $description = $document->createElement('description');
        $channel->appendChild($description);
        $description->appendChild($document->createTextNode($extraInfos['name']));

        $allowedIconExtensions = [
            '.gif',
            '.jpg',
            '.png',
        ];
        $icon = $extraInfos['icon'];
        if (!empty($icon) && in_array(substr($icon, -4), $allowedIconExtensions)) {
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

        $linkAlternate = $document->createElementNS(self::ATOM_NS, 'link');
        $channel->appendChild($linkAlternate);
        $linkAlternate->setAttribute('rel', 'alternate');
        $linkAlternate->setAttribute('type', 'text/html');
        $linkAlternate->setAttribute('href', $uri);

        $linkSelf = $document->createElementNS(self::ATOM_NS, 'link');
        $channel->appendChild($linkSelf);
        $linkSelf->setAttribute('rel', 'self');
        $linkSelf->setAttribute('type', 'application/atom+xml');
        $linkSelf->setAttribute('href', $feedUrl);

        foreach ($this->getItems() as $item) {
            $itemTimestamp = $item->getTimestamp();
            $itemTitle = $item->getTitle();
            $itemUri = $item->getURI();
            $itemContent = $item->getContent() ? break_annoying_html_tags($item->getContent()) : '';
            $entryID = $item->getUid();
            $isPermaLink = 'false';

            if (empty($entryID) && !empty($itemUri)) {
                // Fallback to provided URI
                $entryID = $itemUri;
                $isPermaLink = 'true';
            }

            if (empty($entryID)) {
                // Fallback to title and content
                $entryID = hash('sha1', $itemTitle . $itemContent);
            }

            $entry = $document->createElement('item');
            $channel->appendChild($entry);

            if (!empty($itemTitle)) {
                $entryTitle = $document->createElement('title');
                $entry->appendChild($entryTitle);
                $entryTitle->appendChild($document->createTextNode($itemTitle));
            }

            if (!empty($itemUri)) {
                $entryLink = $document->createElement('link');
                $entry->appendChild($entryLink);
                $entryLink->appendChild($document->createTextNode($itemUri));
            }

            $entryGuid = $document->createElement('guid');
            $entryGuid->setAttribute('isPermaLink', $isPermaLink);
            $entry->appendChild($entryGuid);
            $entryGuid->appendChild($document->createTextNode($entryID));

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
        // Remove invalid non-UTF8 characters
        ini_set('mbstring.substitute_character', 'none');
        $xml = mb_convert_encoding($xml, $this->getCharset(), 'UTF-8');
        return $xml;
    }
}
