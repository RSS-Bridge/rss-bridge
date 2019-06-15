<?php
class HtmlFormat extends FormatAbstract {
	public function stringify(){
		$extraInfos = $this->getExtraInfos();
		$title = htmlspecialchars($extraInfos['name']);
		$uri = htmlspecialchars($extraInfos['uri']);
		$atomquery = str_replace('format=Html', 'format=Atom', htmlentities($_SERVER['QUERY_STRING']));
		$mrssquery = str_replace('format=Html', 'format=Mrss', htmlentities($_SERVER['QUERY_STRING']));

		$entries = '';
		foreach($this->getItems() as $item) {
			$entryAuthor = $item->getAuthor() ? '<br /><p class="author">by: ' . $item->getAuthor() . '</p>' : '';
			$entryTitle = $this->sanitizeHtml(strip_tags($item->getTitle()));
			$entryUri = $item->getURI() ?: $uri;

			$entryTimestamp = '';
			if($item->getTimestamp()) {
				$entryTimestamp = '<time datetime="'
				. date(DATE_ATOM, $item->getTimestamp())
				. '">'
				. date(DATE_ATOM, $item->getTimestamp())
				. '</time>';
			}

			$entryContent = '';
			if($item->getContent()) {
				$entryContent = '<div class="content">'
				. $this->sanitizeHtml($item->getContent())
				. '</div>';
			}

			$entryEnclosures = '';
			if(!empty($item->getEnclosures())) {
				$entryEnclosures = '<div class="attachments"><p>Attachments:</p>';

				foreach($item->getEnclosures() as $enclosure) {
					$url = $this->sanitizeHtml($enclosure);

					$entryEnclosures .= '<li class="enclosure"><a href="'
					. $url
					. '">'
					. substr($url, strrpos($url, '/') + 1)
					. '</a></li>';
				}

				$entryEnclosures .= '</div>';
			}

			$entryCategories = '';
			if(!empty($item->getCategories())) {
				$entryCategories = '<div class="categories"><p>Categories:</p>';

				foreach($item->getCategories() as $category) {

					$entryCategories .= '<li class="category">'
					. $this->sanitizeHtml($category)
					. '</li>';
				}

				$entryCategories .= '</div>';
			}

			$entries .= <<<EOD

<section class="feeditem">
	<h2><a class="itemtitle" href="{$entryUri}">{$entryTitle}</a></h2>
	{$entryTimestamp}
	{$entryAuthor}
	{$entryContent}
	{$entryEnclosures}
	{$entryCategories}
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
	<link href="static/HtmlFormat.css" rel="stylesheet">
	<link rel="alternate" type="application/atom+xml" title="Atom" href="./?{$atomquery}" />
	<link rel="alternate" type="application/rss+xml" title="RSS" href="/?{$mrssquery}" />
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
