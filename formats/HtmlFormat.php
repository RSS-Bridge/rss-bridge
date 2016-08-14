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
        $mrssquery = str_replace('format=HtmlFormat', 'format=MrssFormat', htmlentities($_SERVER['QUERY_STRING']));

        $entries = '';
        foreach($this->getDatas() as $data){
            $entryAuthor = is_null($data->author) ? '' : '<br /><p class="author">by: ' . $data->author . '</p>';
            $entryTitle = is_null($data->title) ? '' : $this->sanitizeHtml(strip_tags($data->title));
            $entryUri = is_null($data->uri) ? $uri : $data->uri;
            $entryTimestamp = is_null($data->timestamp) ? '' : '<time datetime="' . date(DATE_ATOM, $data->timestamp) . '">' . date(DATE_ATOM, $data->timestamp) . '</time>';
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

        return $toReturn;
    }

    public function display() {
        $this
            ->setContentType('text/html; charset=' . $this->getCharset())
            ->callContentType();

        return parent::display();
    }
}
