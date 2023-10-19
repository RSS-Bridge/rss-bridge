<?php

/**
 * AtomFormat - RFC 4287: The Atom Syndication Format
 * https://tools.ietf.org/html/rfc4287
 *
 * Validator:
 * https://validator.w3.org/feed/
 */
class AtomFormat extends FormatAbstract
{
    const MIME_TYPE = 'application/atom+xml';

    protected const ATOM_NS = 'http://www.w3.org/2005/Atom';
    protected const MRSS_NS = 'http://search.yahoo.com/mrss/';

    public function stringify()
    {
        $document = new \DomDocument('1.0', $this->getCharset());

        $feedUrl = get_current_url();

        $extraInfos = $this->getExtraInfos();
        if (empty($extraInfos['uri'])) {
            $uri = REPOSITORY;
        } else {
            $uri = $extraInfos['uri'];
        }

        $document->formatOutput = true;
        $feed = $document->createElementNS(self::ATOM_NS, 'feed');
        $document->appendChild($feed);
        $feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:media', self::MRSS_NS);

        $title = $document->createElement('title');
        $feed->appendChild($title);
        $title->setAttribute('type', 'text');
        $title->appendChild($document->createTextNode($extraInfos['name']));

        $id = $document->createElement('id');
        $feed->appendChild($id);
        $id->appendChild($document->createTextNode($feedUrl));

        $uriparts = parse_url($uri);
        if (empty($extraInfos['icon'])) {
            $iconUrl = $uriparts['scheme'] . '://' . $uriparts['host'] . '/favicon.ico';
        } else {
            $iconUrl = $extraInfos['icon'];
        }
        $icon = $document->createElement('icon');
        $feed->appendChild($icon);
        $icon->appendChild($document->createTextNode($iconUrl));

        $logo = $document->createElement('logo');
        $feed->appendChild($logo);
        $logo->appendChild($document->createTextNode($iconUrl));

        $feedTimestamp = gmdate(DATE_ATOM, $this->lastModified);
        $updated = $document->createElement('updated');
        $feed->appendChild($updated);
        $updated->appendChild($document->createTextNode($feedTimestamp));

        // since we can't guarantee that all items have an author,
        // a global feed author is mandatory
        $feedAuthor = 'RSS-Bridge';
        $author = $document->createElement('author');
        $feed->appendChild($author);
        $authorName = $document->createElement('name');
        $author->appendChild($authorName);
        $authorName->appendChild($document->createTextNode($feedAuthor));

        $linkAlternate = $document->createElement('link');
        $feed->appendChild($linkAlternate);
        $linkAlternate->setAttribute('rel', 'alternate');
        $linkAlternate->setAttribute('type', 'text/html');
        $linkAlternate->setAttribute('href', $uri);

        $linkSelf = $document->createElement('link');
        $feed->appendChild($linkSelf);
        $linkSelf->setAttribute('rel', 'self');
        $linkSelf->setAttribute('type', 'application/atom+xml');
        $linkSelf->setAttribute('href', $feedUrl);

        foreach ($this->getItems() as $item) {
            $itemArray = $item->toArray();
            $entryTimestamp = $item->getTimestamp();
            $entryTitle = $item->getTitle();
            $entryContent = $item->getContent();
            $entryUri = $item->getURI();
            $entryID = '';

            if (!empty($item->getUid())) {
                $entryID = 'urn:sha1:' . $item->getUid();
            }

            if (empty($entryID)) {
                // Fallback to provided URI
                $entryID = $entryUri;
            }

            if (empty($entryID)) {
                // Fallback to title and content
                $entryID = 'urn:sha1:' . hash('sha1', $entryTitle . $entryContent);
            }

            if (empty($entryTimestamp)) {
                $entryTimestamp = $this->lastModified;
            }

            if (empty($entryTitle)) {
                $entryTitle = str_replace("\n", ' ', strip_tags($entryContent));
                if (strlen($entryTitle) > 140) {
                    $wrapPos = strpos(wordwrap($entryTitle, 140), "\n");
                    $entryTitle = substr($entryTitle, 0, $wrapPos) . '...';
                }
            }

            if (empty($entryContent)) {
                $entryContent = ' ';
            }

            $entry = $document->createElement('entry');
            $feed->appendChild($entry);

            $title = $document->createElement('title');
            $entry->appendChild($title);
            $title->setAttribute('type', 'html');
            $title->appendChild($document->createTextNode($entryTitle));

            $entryTimestamp = gmdate(\DATE_ATOM, $entryTimestamp);
            $published = $document->createElement('published');
            $entry->appendChild($published);
            $published->appendChild($document->createTextNode($entryTimestamp));

            $updated = $document->createElement('updated');
            $entry->appendChild($updated);
            $updated->appendChild($document->createTextNode($entryTimestamp));

            $id = $document->createElement('id');
            $entry->appendChild($id);
            $id->appendChild($document->createTextNode($entryID));

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
            } elseif (!empty($entryUri)) {
                $entryLinkAlternate = $document->createElement('link');
                $entry->appendChild($entryLinkAlternate);
                $entryLinkAlternate->setAttribute('rel', 'alternate');
                $entryLinkAlternate->setAttribute('type', 'text/html');
                $entryLinkAlternate->setAttribute('href', $entryUri);
            }

            if (!empty($item->getAuthor())) {
                $author = $document->createElement('author');
                $entry->appendChild($author);
                $authorName = $document->createElement('name');
                $author->appendChild($authorName);
                $authorName->appendChild($document->createTextNode($item->getAuthor()));
            }

            $content = $document->createElement('content');
            $content->setAttribute('type', 'html');
            $content->appendChild($document->createTextNode(break_annoying_html_tags($entryContent)));
            $entry->appendChild($content);

            foreach ($item->getEnclosures() as $enclosure) {
                $entryEnclosure = $document->createElement('link');
                $entry->appendChild($entryEnclosure);
                $entryEnclosure->setAttribute('rel', 'enclosure');
                $entryEnclosure->setAttribute('type', parse_mime_type($enclosure));
                $entryEnclosure->setAttribute('href', $enclosure);
            }

            foreach ($item->getCategories() as $category) {
                $entryCategory = $document->createElement('category');
                $entry->appendChild($entryCategory);
                $entryCategory->setAttribute('term', $category);
            }

            if (!empty($item->thumbnail)) {
                $thumbnail = $document->createElementNS(self::MRSS_NS, 'thumbnail');
                $entry->appendChild($thumbnail);
                $thumbnail->setAttribute('url', $item->thumbnail);
            }
        }

        $xml = $document->saveXML();

        // Remove invalid characters
        ini_set('mbstring.substitute_character', 'none');
        $xml = mb_convert_encoding($xml, $this->getCharset(), 'UTF-8');
        return $xml;
    }
}
