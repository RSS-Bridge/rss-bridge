<?php
/**
 * AtomFormat - RFC 4287: The Atom Syndication Format
 * https://tools.ietf.org/html/rfc4287
 *
 * Validator:
 * https://validator.w3.org/feed/
 */
class AtomFormat extends FormatAbstract{
	const LIMIT_TITLE = 140;

	public function stringify(){
		$urlPrefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
		$urlHost = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
		$urlPath = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : '';
		$urlRequest = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '';

		$feedUrl = $this->xml_encode($urlPrefix . $urlHost . $urlRequest);

		$extraInfos = $this->getExtraInfos();
		$title = $this->xml_encode($extraInfos['name']);
		$uri = !empty($extraInfos['uri']) ? $extraInfos['uri'] : REPOSITORY;

		// since we can't guarantee that all items have an author,
		// a global feed author is mandatory
		$feedAuthor = 'RSS-Bridge';

		$uriparts = parse_url($uri);
		if(!empty($extraInfos['icon'])) {
			$icon = $extraInfos['icon'];
		} else {
			$icon = $this->xml_encode($uriparts['scheme'] . '://' . $uriparts['host'] . '/favicon.ico');
		}

		$uri = $this->xml_encode($uri);

		$entries = '';
		foreach($this->getItems() as $item) {
			$entryTimestamp = $item->getTimestamp();
			$entryTitle = $this->xml_encode($item->getTitle());
			$entryContent = $item->getContent();
			$entryUri = $item->getURI();
			$entryID = '';

			if (!empty($item->getUid()))
				$entryID = 'urn:sha1:' . $item->getUid();

			if (empty($entryID)) // Fallback to provided URI
				$entryID = $this->xml_encode($entryUri);

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
				$entryContent = $entryTitle;

			$entryAuthor = $this->xml_encode($item->getAuthor());
			$entryTitle = $this->xml_encode($entryTitle);
			$entryUri = $this->xml_encode($entryUri);
			$entryTimestamp = $this->xml_encode(gmdate(DATE_ATOM, $entryTimestamp));
			$entryContent = $this->xml_encode($this->sanitizeHtml($entryContent));

			$entryEnclosures = '';
			foreach($item->getEnclosures() as $enclosure) {
				$entryEnclosures .= '<link rel="enclosure" href="'
				. $this->xml_encode($enclosure)
				. '" type="' . getMimeType($enclosure) . '" />'
				. PHP_EOL;
			}

			$entryCategories = '';
			foreach($item->getCategories() as $category) {
				$entryCategories .= '<category term="'
				. $this->xml_encode($category)
				. '"/>'
				. PHP_EOL;
			}

			$entryLinkAlternate = '';
			if (!empty($entryUri)) {
				$entryLinkAlternate = '<link rel="alternate" type="text/html" href="'
				. $entryUri
				. '"/>';
			}

			if (!empty($entryAuthor)) {
				$entryAuthor = '<author><name>'
				. $entryAuthor
				. '</name></author>';
			}

			$entries .= <<<EOD

	<entry>
		<title type="html">{$entryTitle}</title>
		<published>{$entryTimestamp}</published>
		<updated>{$entryTimestamp}</updated>
		<id>{$entryID}</id>
		{$entryLinkAlternate}
		{$entryAuthor}
		<content type="html">{$entryContent}</content>
		{$entryEnclosures}
		{$entryCategories}
	</entry>

EOD;
		}

		$feedTimestamp = gmdate(DATE_ATOM, $this->lastModified);
		$charset = $this->getCharset();

		/* Data are prepared, now let's begin the "MAGIE !!!" */
		$toReturn = <<<EOD
<?xml version="1.0" encoding="{$charset}"?>
<feed xmlns="http://www.w3.org/2005/Atom">

	<title type="text">{$title}</title>
	<id>{$feedUrl}</id>
	<icon>{$icon}</icon>
	<logo>{$icon}</logo>
	<updated>{$feedTimestamp}</updated>
	<author>
		<name>{$feedAuthor}</name>
	</author>
	<link rel="alternate" type="text/html" href="{$uri}" />
	<link rel="self" type="application/atom+xml" href="{$feedUrl}" />
{$entries}
</feed>
EOD;

		// Remove invalid characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}

	public function display(){
		$this
			->setContentType('application/atom+xml; charset=' . $this->getCharset())
			->callContentType();

		return parent::display();
	}

	private function xml_encode($text){
		return htmlspecialchars($text, ENT_XML1);
	}
}
