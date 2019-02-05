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
	const ALLOWED_IMAGE_EXT = array(
		'.gif', '.jpg', '.png'
	);

	public function stringify(){
		$urlPrefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		$urlHost = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
		$urlPath = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : '';
		$urlRequest = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';

		$feedUrl = $this->xml_encode($urlPrefix . $urlHost . $urlRequest);

		$extraInfos = $this->getExtraInfos();
		$title = $this->xml_encode($extraInfos['name']);
		$icon = $extraInfos['icon'];

		if(!empty($extraInfos['uri'])) {
			$uri = $this->xml_encode($extraInfos['uri']);
		} else {
			$uri = REPOSITORY;
		}

		$items = '';
		foreach($this->getItems() as $item) {
			$itemTimestamp = $item->getTimestamp();
			$itemTitle = $this->xml_encode($item->getTitle());
			$itemUri = $this->xml_encode($item->getURI());
			$itemContent = $this->xml_encode($this->sanitizeHtml($item->getContent()));
			$entryID = $item->getUid();
			$isPermaLink = 'false';

			if (empty($entryID) && !empty($itemUri)) { // Fallback to provided URI
				$entryID = $itemUri;
				$isPermaLink = 'true';
			}

			if (empty($entryID)) // Fallback to title and content
				$entryID = hash('sha1', $itemTitle . $itemContent);

			$entryTitle = '';
			if (!empty($itemTitle))
				$entryTitle = '<title>' . $itemTitle . '</title>';

			$entryLink = '';
			if (!empty($itemUri))
				$entryLink = '<link>' . $itemUri . '</link>';

			$entryPublished = '';
			if (!empty($itemTimestamp)) {
				$entryPublished = '<pubDate>'
				. $this->xml_encode(gmdate(DATE_RFC2822, $itemTimestamp))
				. '</pubDate>';
			}

			$entryDescription = '';
			if (!empty($itemContent))
				$entryDescription = '<description>' . $itemContent . '</description>';

			$entryEnclosures = '';
			foreach($item->getEnclosures() as $enclosure) {
				$entryEnclosures .= '<media:content url="'
				. $this->xml_encode($enclosure)
				. '" type="' . getMimeType($enclosure) . '"/>'
				. PHP_EOL;
			}

			$entryCategories = '';
			foreach($item->getCategories() as $category) {
				$entryCategories .= '<category>'
				. $category . '</category>'
				. PHP_EOL;
			}

			$items .= <<<EOD

	<item>
		{$entryTitle}
		{$entryLink}
		<guid isPermaLink="{$isPermaLink}">{$entryID}</guid>
		{$entryPublished}
		{$entryDescription}
		{$entryEnclosures}
		{$entryCategories}
	</item>

EOD;
		}

		$charset = $this->getCharset();

		$feedImage = '';
		if (!empty($icon) && in_array(substr($icon, -4), self::ALLOWED_IMAGE_EXT)) {
			$feedImage .= <<<EOD
		<image>
			<url>{$icon}</url>
			<title>{$title}</title>
			<link>{$uri}</link>
		</image>
EOD;
		}

		/* Data are prepared, now let's begin the "MAGIE !!!" */
		$toReturn = <<<EOD
<?xml version="1.0" encoding="{$charset}"?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>{$title}</title>
		<link>{$uri}</link>
		<description>{$title}</description>
		{$feedImage}
		<atom:link rel="alternate" type="text/html" href="{$uri}"/>
		<atom:link rel="self" href="{$feedUrl}" type="application/atom+xml"/>
		{$items}
	</channel>
</rss>
EOD;

		// Remove invalid non-UTF8 characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}

	public function display(){
		$this
			->setContentType('application/rss+xml; charset=' . $this->getCharset())
			->callContentType();

		return parent::display();
	}

	private function xml_encode($text){
		return htmlspecialchars($text, ENT_XML1);
	}
}
