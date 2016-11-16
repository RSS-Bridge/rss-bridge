<?php
/**
* Mrss
* Documentation Source http://www.rssboard.org/media-rss
*/
class MrssFormat extends FormatAbstract {

	public function stringify(){
		$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '';
		$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$httpInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';

		$serverRequestUri = $this->xml_encode($_SERVER['REQUEST_URI']);

		$extraInfos = $this->getExtraInfos();
		$title = $this->xml_encode($extraInfos['name']);

		if(!empty($extraInfos['uri'])){
			$uri = $this->xml_encode($extraInfos['uri']);
		} else {
			$uri = 'https://github.com/sebsauvage/rss-bridge';
		}

		$icon = $this->xml_encode('http://icons.better-idea.org/icon?url='. $uri .'&size=64');

		$items = '';
		foreach($this->getItems() as $item){
			$itemAuthor = isset($item['author']) ? $this->xml_encode($item['author']) : '';
			$itemTitle = strip_tags(isset($item['title']) ? $this->xml_encode($item['title']) : '');
			$itemUri = isset($item['uri']) ? $this->xml_encode($item['uri']) : '';
			$itemTimestamp = isset($item['timestamp']) ? $this->xml_encode(date(DATE_RFC2822, $item['timestamp'])) : '';
			$itemContent = isset($item['content']) ? $this->xml_encode($this->sanitizeHtml($item['content'])) : '';

			$entryEnclosuresWarning = '';
			$entryEnclosures = '';
			if(isset($item['enclosures'])){
				$entryEnclosures .= '<enclosure url="'
				. $this->xml_encode($item['enclosures'][0])
				. '"/>';

				if(count($item['enclosures']) > 1){
					$entryEnclosures .= PHP_EOL;
					$entryEnclosuresWarning = '&lt;br&gt;Warning:
Some media files might not be shown to you. Consider using the ATOM format instead!';
					foreach($item['enclosures'] as $enclosure){
						$entryEnclosures .= '<atom:link rel="enclosure" href="'
						. $enclosure . '" />'
						. PHP_EOL;
					}
				}
			}

			$items .= <<<EOD

	<item>
		<title>{$itemTitle}</title>
		<link>{$itemUri}</link>
		<guid isPermaLink="true">{$itemUri}</guid>
		<pubDate>{$itemTimestamp}</pubDate>
		<description>{$itemContent}{$entryEnclosuresWarning}</description>
		<author>{$itemAuthor}</author>
		{$entryEnclosures}
	</item>

EOD;
		}

		$charset = $this->getCharset();

		/* Data are prepared, now let's begin the "MAGIE !!!" */
		$toReturn = <<<EOD
<?xml version="1.0" encoding="{$charset}"?>
<rss version="2.0"
xmlns:dc="http://purl.org/dc/elements/1.1/"
xmlns:media="http://search.yahoo.com/mrss/"
xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>{$title}</title>
		<link>http{$https}://{$httpHost}{$httpInfo}/</link>
		<description>{$title}</description>
		<image url="{$icon}" title="{$title}" link="{$uri}"/>
		<atom:link rel="alternate" type="text/html" href="{$uri}" />
		<atom:link rel="self" href="http{$https}://{$httpHost}{$serverRequestUri}" />
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
