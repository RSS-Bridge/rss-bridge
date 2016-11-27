<?php
class HtmlFormat extends FormatAbstract {

	public function stringify(){
		$extraInfos = $this->getExtraInfos();
		$title = htmlspecialchars($extraInfos['name']);
		$uri = htmlspecialchars($extraInfos['uri']);
		$atomquery = str_replace('format=Html', 'format=Atom', htmlentities($_SERVER['QUERY_STRING']));
		$mrssquery = str_replace('format=Html', 'format=Mrss', htmlentities($_SERVER['QUERY_STRING']));

		$entries = '';
		foreach($this->getItems() as $item){
			$entryAuthor = isset($item['author']) ? '<br /><p class="author">by: ' . $item['author'] . '</p>' : '';
			$entryTitle = isset($item['title']) ? $this->sanitizeHtml(strip_tags($item['title'])) : '';
			$entryUri = isset($item['uri']) ? $item['uri'] : $uri;

			$entryTimestamp = '';
			if(isset($item['timestamp'])){
				$entryTimestamp = '<time datetime="'
				. date(DATE_ATOM, $item['timestamp'])
				. '">'
				. date(DATE_ATOM, $item['timestamp'])
				. '</time>';
			}

			$entryContent = '';
			if(isset($item['content'])){
				$entryContent = '<div class="content">'
				. $this->sanitizeHtml($item['content'])
				. '</div>';
			}

			$entryEnclosures = '';
			if(isset($item['enclosures'])){
				$entryEnclosures = '<div class="attachments"><p>Attachments:</p>';

				foreach($item['enclosures'] as $enclosure){
					$url = $this->sanitizeHtml($enclosure);

					$entryEnclosures .= '<li class="enclosure"><a href="'
					. $url
					. '">'
					. substr($url, strrpos($url, '/') + 1)
					. '</a></li>';
				}

				$entryEnclosures .= '</div>';
			}

			$entries .= <<<EOD

<section class="feeditem">
	<h2><a class="itemtitle" href="{$entryUri}">{$entryTitle}</a></h2>
	{$entryTimestamp}
	{$entryAuthor}
	{$entryContent}
	{$entryEnclosures}
</section>

EOD;
		}

		$charset = $this->getCharset();

		/* Data are prepared, now let's begin the "MAGIE !!!" */
		$toReturn = <<<EOD
<!DOCTYPE html>
<html>
<head>
	<meta charset="{$charset}">
	<title>{$title}</title>
	<link href="css/HtmlFormat.css" rel="stylesheet">
	<meta name="robots" content="noindex, follow">
</head>
<body>
	<h1 class="pagetitle"><a href="{$uri}" target="_blank">{$title}</a></h1>
	<div class="buttons">
		<a href="./#bridge-{$_GET['bridge']}"><button class="backbutton">← back to rss-bridge</button></a>
		<a href="./?{$atomquery}"><button class="rss-feed">RSS feed (ATOM)</button></a>
		<a href="./?{$mrssquery}"><button class="rss-feed">RSS feed (MRSS)</button></a>
	</div>
{$entries}
</body>
</html>
EOD;

		// Remove invalid characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}

	public function display() {
		$this
			->setContentType('text/html; charset=' . $this->getCharset())
			->callContentType();

		return parent::display();
	}
}
