<?php

class HtmlFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/html';

    public function stringify()
    {
        $extraInfos = $this->getExtraInfos();
        $title = e($extraInfos['name']);
        $uri = e($extraInfos['uri']);
        $donationUri = e($extraInfos['donationUri']);
        $donationsAllowed = Configuration::getConfig('admin', 'donations');

        // Dynamically build buttons for all formats (except HTML)
        $formatFactory = new FormatFactory();

        $buttons = '';
        $links = '';

        foreach ($formatFactory->getFormatNames() as $format) {
            if ($format === 'Html') {
                continue;
            }

            $queryString = $_SERVER['QUERY_STRING'];
            $query = str_ireplace('format=Html', 'format=' . $format, htmlentities($queryString));
            $buttons .= sprintf('<a href="./?%s"><button class="rss-feed">%s</button></a>', $query, $format) . "\n";

            $mime = $formatFactory->create($format)->getMimeType();
            $links .= sprintf('<link href="./?%s" title="%s" rel="alternate" type="%s">', $query, $format, $mime) . "\n";
        }

        if ($donationUri !== '' && $donationsAllowed) {
            $str = sprintf(
                '<a href="%s" target="_blank"><button class="highlight">Donate to maintainer</button></a>',
                $donationUri
            );
            $buttons .= $str;
            $str1 = sprintf(
                '<link href="%s target="_blank"" title="Donate to Maintainer" rel="alternate">',
                $donationUri
            );
            $links .= $str1;
        }

        $entries = '';
        foreach ($this->getItems() as $item) {
            if ($item->getAuthor()) {
                $entryAuthor = sprintf('<br /><p class="author">by: %s</p>', $item->getAuthor());
            } else {
                $entryAuthor = '';
            }
            $entryTitle = sanitize_html(strip_tags($item->getTitle()));
            $entryUri = $item->getURI() ?: $uri;

            $entryDate = '';
            if ($item->getTimestamp()) {
                $entryDate = sprintf(
                    '<time datetime="%s">%s</time>',
                    date('Y-m-d H:i:s', $item->getTimestamp()),
                    date('Y-m-d H:i:s', $item->getTimestamp())
                );
            }

            $entryContent = '';
            if ($item->getContent()) {
                $str2 = sprintf('<div class="content">%s</div>', sanitize_html($item->getContent()));
                $entryContent = $str2;
            }

            $entryEnclosures = '';
            if (!empty($item->getEnclosures())) {
                $entryEnclosures = '<div class="attachments"><p>Attachments:</p>';

                foreach ($item->getEnclosures() as $enclosure) {
                    $template = '<li class="enclosure"><a href="%s" rel="noopener noreferrer nofollow">%s</a></li>';
                    $url = sanitize_html($enclosure);
                    $anchorText = substr($url, strrpos($url, '/') + 1);

                    $entryEnclosures .= sprintf($template, $url, $anchorText);
                }

                $entryEnclosures .= '</div>';
            }

            $entryCategories = '';
            if (!empty($item->getCategories())) {
                $entryCategories = '<div class="categories"><p>Categories:</p>';

                foreach ($item->getCategories() as $category) {
                    $entryCategories .= '<li class="category">'
                    . sanitize_html($category)
                    . '</li>';
                }

                $entryCategories .= '</div>';
            }

            $entries .= <<<EOD

<section class="feeditem">
	<h2><a class="itemtitle" href="{$entryUri}">{$entryTitle}</a></h2>
	{$entryDate}
	{$entryAuthor}
	{$entryContent}
	{$entryEnclosures}
	{$entryCategories}
</section>

EOD;
        }

        $charset = $this->getCharset();
        $toReturn = <<<EOD
<!DOCTYPE html>
<html>
<head>
	<meta charset="{$charset}">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>{$title}</title>
	<link href="static/HtmlFormat.css" rel="stylesheet">
	<link rel="icon" type="image/png" href="static/favicon.png">
	{$links}
	<meta name="robots" content="noindex, follow">
</head>
<body>
	<h1 class="pagetitle"><a href="{$uri}" target="_blank">{$title}</a></h1>
	<div class="buttons">
		<a href="./#bridge-{$_GET['bridge']}"><button class="backbutton">← back to rss-bridge</button></a>
		{$buttons}
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
}
