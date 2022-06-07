<?php
/**
 * AtomFormat - RFC 4287: The Atom Syndication Format
 * https://tools.ietf.org/html/rfc4287
 *
 * Validator:
 * https://validator.w3.org/feed/
 */
class AtomFormat extends FormatAbstract{
	const MIME_TYPE = 'application/atom+xml';

	protected const ATOM_NS = 'http://www.w3.org/2005/Atom';
	protected const MRSS_NS = 'http://search.yahoo.com/mrss/';

	const LIMIT_TITLE = 140;

	public function stringify(){
		$urlPrefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		$urlHost = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
		$urlPath = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : '';
		$urlRequest = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';

		$feedUrl = $urlPrefix . $urlHost . $urlRequest;

		$extraInfos = $this->getExtraInfos();
		$uri = !empty($extraInfos['uri']) ? $extraInfos['uri'] : REPOSITORY;

		$document = new DomDocument('1.0', $this->getCharset());
		$document->formatOutput = true;
		$feed = $document->createElementNS(self::ATOM_NS, 'feed');
		$feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:media', self::MRSS_NS);
		$document->appendChild($feed);

		$title = $document->createElement('title');
		$title->setAttribute('type', 'text');
		$title->appendChild($document->createTextNode($extraInfos['name']));
		$feed->appendChild($title);

		$id = $document->createElement('id');
		$id->appendChild($document->createTextNode($feedUrl));
		$feed->appendChild($id);

		$uriparts = parse_url($uri);
		if(!empty($extraInfos['icon'])) {
			$iconUrl = $extraInfos['icon'];
		} else {
			$iconUrl = $uriparts['scheme'] . '://' . $uriparts['host'] . '/favicon.ico';
		}
		$icon = $document->createElement('icon');
		$icon->appendChild($document->createTextNode($iconUrl));
		$feed->appendChild($icon);

		$logo = $document->createElement('logo');
		$logo->appendChild($document->createTextNode($iconUrl));
		$feed->appendChild($logo);

		$feedTimestamp = gmdate(DATE_ATOM, $this->lastModified);
		$updated = $document->createElement('updated');
		$updated->appendChild($document->createTextNode($feedTimestamp));
		$feed->appendChild($updated);

		// since we can't guarantee that all items have an author,
		// a global feed author is mandatory
		$feedAuthor = 'RSS-Bridge';
		$author = $document->createElement('author');
		$authorName = $document->createElement('name');
		$authorName->appendChild($document->createTextNode($feedAuthor));
		$author->appendChild($authorName);
		$feed->appendChild($author);

		$linkAlternate = $document->createElement('link');
		$linkAlternate->setAttribute('rel', 'alternate');
		$linkAlternate->setAttribute('type', 'text/html');
		$linkAlternate->setAttribute('href', $uri);
		$feed->appendChild($linkAlternate);

		$linkSelf = $document->createElement('link');
		$linkSelf->setAttribute('rel', 'self');
		$linkSelf->setAttribute('type', 'application/atom+xml');
		$linkSelf->setAttribute('href', $feedUrl);
		$feed->appendChild($linkSelf);

		foreach($this->getItems() as $item) {
			$entryTimestamp = $item->getTimestamp();
			$entryTitle = $item->getTitle();
			$entryContent = $item->getContent();
			$entryUri = $item->getURI();
			$entryID = '';

			if (!empty($item->getUid()))
				$entryID = 'urn:sha1:' . $item->getUid();

			if (empty($entryID)) // Fallback to provided URI
				$entryID = $entryUri;

			if (empty($entryID)) // Fallback to title and content
				$entryID = 'urn:sha1:' . hash('sha1', $entryTitle . $entryContent);

			if (empty($entryTimestamp))
				$entryTimestamp = $this->lastModified;

			if (empty($entryTitle)) {
				$entryTitle = str_replace("\n", ' ', strip_tags($entryContent));
				if (strlen($entryTitle) > self::LIMIT_TITLE) {
					$wrapPos = strpos(wordwrap($entryTitle, self::LIMIT_TITLE), "\n");
					$entryTitle = substr($entryTitle, 0, $wrapPos) . '...';
				}
			}

			if (empty($entryContent))
				$entryContent = ' ';

			$entry = $document->createElement('entry');

			$title = $document->createElement('title');
			$title->setAttribute('type', 'html');
			$title->appendChild($document->createTextNode($entryTitle));
			$entry->appendChild($title);

			$entryTimestamp = gmdate(DATE_ATOM, $entryTimestamp);
			$published = $document->createElement('published');
			$published->appendChild($document->createTextNode($entryTimestamp));
			$entry->appendChild($published);

			$updated = $document->createElement('updated');
			$updated->appendChild($document->createTextNode($entryTimestamp));
			$entry->appendChild($updated);

			$id = $document->createElement('id');
			$id->appendChild($document->createTextNode($entryID));
			$entry->appendChild($id);

			if (!empty($entryUri)) {
				$entryLinkAlternate = $document->createElement('link');
				$entryLinkAlternate->setAttribute('rel', 'alternate');
				$entryLinkAlternate->setAttribute('type', 'text/html');
				$entryLinkAlternate->setAttribute('href', $entryUri);
				$entry->appendChild($entryLinkAlternate);
			}

			if (!empty($item->getAuthor())) {
				$author = $document->createElement('author');
				$authorName = $document->createElement('name');
				$authorName->appendChild($document->createTextNode($item->getAuthor()));
				$author->appendChild($authorName);
				$entry->appendChild($author);
			}

			$content = $document->createElement('content');
			$content->setAttribute('type', 'html');
			$content->appendChild($document->createTextNode($this->sanitizeHtml($entryContent)));
			$entry->appendChild($content);

			foreach($item->getEnclosures() as $enclosure) {
				$entryEnclosure = $document->createElement('link');
				$entryEnclosure->setAttribute('rel', 'enclosure');
				$entryEnclosure->setAttribute('type', getMimeType($enclosure));
				$entryEnclosure->setAttribute('href', $enclosure);
				$entry->appendChild($entryEnclosure);
			}

			foreach($item->getCategories() as $category) {
				$entryCategory = $document->createElement('category');
				$entryCategory->setAttribute('term', $category);
				$entry->appendChild($entryCategory);
			}

			if (!empty($item->thumbnail)) {
				$thumbnail = $document->createElementNS(self::MRSS_NS, 'media:thumbnail');
				$thumbnail->setAttribute('url', $item->thumbnail);
				$entry->appendChild($thumbnail);
			}

			$feed->appendChild($entry);
		}

		$toReturn = $document->saveXML();

		// Remove invalid characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}
}
