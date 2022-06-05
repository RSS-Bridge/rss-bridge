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
class MrssFormat extends FormatAbstract {
	const MIME_TYPE = 'application/rss+xml';

	protected const ATOM_NS = 'http://www.w3.org/2005/Atom';
	protected const MRSS_NS = 'http://search.yahoo.com/mrss/';

	const ALLOWED_IMAGE_EXT = array(
		'.gif', '.jpg', '.png'
	);

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
		$feed = $document->createElement('rss');
		$feed->setAttribute('version', '2.0');
		$feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', self::ATOM_NS);
		$feed->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:media', self::MRSS_NS);
		$document->appendChild($feed);

		$channel = $document->createElement('channel');
		$feed->appendChild($channel);

		$title = $extraInfos['name'];
		$channelTitle = $document->createElement('title');
		$channelTitle->appendChild($document->createTextNode($title));
		$channel->appendChild($channelTitle);

		$link = $document->createElement('link');
		$link->appendChild($document->createTextNode($uri));
		$channel->appendChild($link);

		$description = $document->createElement('description');
		$description->appendChild($document->createTextNode($extraInfos['name']));
		$channel->appendChild($description);

		$icon = $extraInfos['icon'];
		if (!empty($icon) && in_array(substr($icon, -4), self::ALLOWED_IMAGE_EXT)) {
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

		$linkAlternate = $document->createElementNS(self::ATOM_NS, 'atom:link');
		$linkAlternate->setAttribute('rel', 'alternate');
		$linkAlternate->setAttribute('type', 'text/html');
		$linkAlternate->setAttribute('href', $uri);
		$channel->appendChild($linkAlternate);

		$linkSelf = $document->createElementNS(self::ATOM_NS, 'atom:link');
		$linkSelf->setAttribute('rel', 'self');
		$linkSelf->setAttribute('type', 'application/atom+xml');
		$linkSelf->setAttribute('href', $feedUrl);
		$channel->appendChild($linkSelf);

		foreach($this->getItems() as $item) {
			$itemTimestamp = $item->getTimestamp();
			$itemTitle = $item->getTitle();
			$itemUri = $item->getURI();
			$itemContent = $this->sanitizeHtml($item->getContent());
			$entryID = $item->getUid();
			$isPermaLink = 'false';

			if (empty($entryID) && !empty($itemUri)) { // Fallback to provided URI
				$entryID = $itemUri;
				$isPermaLink = 'true';
			}

			if (empty($entryID)) // Fallback to title and content
				$entryID = hash('sha1', $itemTitle . $itemContent);

			$entry = $document->createElement('item');

			if (!empty($itemTitle)) {
				$entryTitle = $document->createElement('title');
				$entryTitle->appendChild($document->createTextNode($itemTitle));
				$entry->appendChild($entryTitle);
			}

			if (!empty($itemUri)) {
				$entryLink = $document->createElement('link');
				$entryLink->appendChild($document->createTextNode($itemUri));
				$entry->appendChild($entryLink);
			}

			$entryGuid = $document->createElement('guid');
			$entryGuid->setAttribute('isPermaLink', $isPermaLink);
			$entryGuid->appendChild($document->createTextNode($entryID));
			$entry->appendChild($entryGuid);

			if (!empty($itemTimestamp)) {
				$entryPublished = $document->createElement('pubDate');
				$entryPublished->appendChild($document->createTextNode(gmdate(DATE_RFC2822, $itemTimestamp)));
				$entry->appendChild($entryPublished);
			}

			if (!empty($itemContent)) {
				$entryDescription = $document->createElement('description');
				$entryDescription->appendChild($document->createTextNode($itemContent));
				$entry->appendChild($entryDescription);
			}

			foreach($item->getEnclosures() as $enclosure) {
				$entryEnclosure = $document->createElementNS(self::MRSS_NS, 'media:content');
				$entryEnclosure->setAttribute('url', $enclosure);
				$entryEnclosure->setAttribute('type', getMimeType($enclosure));
				$entry->appendChild($entryEnclosure);
			}

			$entryCategories = '';
			foreach($item->getCategories() as $category) {
				$entryCategory = $document->createElement('category');
				$entryCategory->appendChild($document->createTextNode($category));
				$entry->appendChild($entryCategory);
			}

			$channel->appendChild($entry);
		}

		$toReturn = $document->saveXML();

		// Remove invalid non-UTF8 characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}
}
