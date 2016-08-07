<?php
/**
* Html
* Documentation Source http://en.wikipedia.org/wiki/Atom_%28standard%29 and http://tools.ietf.org/html/rfc4287
*
* @name Html
*/
class HtmlFormat extends FormatAbstract{

    public function stringify(){
        /* Datas preparation */
        $extraInfos = $this->getExtraInfos();
        $title = htmlspecialchars($extraInfos['name']);
        $uri = htmlspecialchars($extraInfos['uri']);
	$atomquery = str_replace('format=HtmlFormat', 'format=AtomFormat', htmlentities($_SERVER['QUERY_STRING']));

        $entries = '';
        foreach($this->getDatas() as $data){
            $entryUri = is_null($data->uri) ? $uri : $data->uri;
            $entryTitle = is_null($data->title) ? '' : $this->sanitizeHtml(strip_tags($data->title));
            $entryTimestamp = is_null($data->timestamp) ? '' : '<time datetime="' . date(DATE_ATOM, $data->timestamp) . '">' . date(DATE_ATOM, $data->timestamp) . '</time>';
            $entryAuthor = is_null($data->author) ? '' : '<br /><p class="author">by: ' . $data->author . '</p>';
            $entryContent = is_null($data->content) ? '' : '<div class="content">' . $this->sanitizeHtml($data->content). '</div>';
            $entries .= <<<EOD

<section class="feeditem">
	<h2><a class="itemtitle" href="{$entryUri}">{$entryTitle}</a></h2>
	{$entryTimestamp}
   {$entryAuthor}
   {$entryContent}
</section>

EOD;
        }


        /* Data are prepared, now let's begin the "MAGIE !!!" */
        $toReturn = <<<EOD
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>{$title}</title>
	<link href="css/HtmlFormat.css" rel="stylesheet">
	<meta name="robots" content="noindex, follow">
</head>
<body>
	<h1 class="pagetitle">{$title}</h1>
	<div class="buttons"><a href="./#bridge-{$_GET['bridge']}"><button class="backbutton">← back to rss-bridge</button></a><a href="./?{$atomquery}"><button class="rss-feed">RSS feed</button></a></div>
{$entries}
</body>
</html>
EOD;

        return $toReturn;
    }

    public function display() {
        $this
            ->setContentType('text/html; charset=' . $this->getCharset())
            ->callContentType();

        return parent::display();
    }
}
