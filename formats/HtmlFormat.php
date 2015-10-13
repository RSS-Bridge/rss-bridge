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
            $entryTimestamp = is_null($data->timestamp) ? '' : '<small><time datetime="' . date(DATE_ATOM, $data->timestamp) . '">' . date(DATE_ATOM, $data->timestamp) . '</time></small>';
            $entryAuthor = is_null($data->author) ? '' : '<br><small>by: ' . $data->author . '</small>';
            $entryContent = is_null($data->content) ? '' : '<p>' . $this->sanitizeHtml($data->content). '</p>';
            $entries .= <<<EOD

<div class="feeditem">
	<h2><a href="{$entryUri}">{$entryTitle}</a></h2>
	{$entryTimestamp}
   {$entryAuthor}
   {$entryContent}
</div>

EOD;
        }

        $styleCss = <<<'EOD'
body{
	font-family:"Trebuchet MS",Verdana,Arial,Helvetica,sans-serif;
	font-size:10pt;
	background-color:#aaa;
	background-image:linear-gradient(#eee, #aaa);
	background-attachment:fixed;
}
div.feeditem{border:1px solid black;padding:1em;margin:1em;background-color:#fff;}
div.feeditem:hover { background-color:#ebf7ff; }
h1 {border-bottom:dotted #bbb;margin:0 1em 1em 1em;}
h2 {margin:0;}
h2 a {color:black;text-decoration:none;}
h2 a:hover {text-decoration:underline;}
span.menu {margin-left:1em;}
span.menu img {vertical-align:middle;}
span.menu a { color:black; text-decoration:none;  padding:0.4em; }
span.menu a:hover { background-color:white; }

EOD;

        /* Data are prepared, now let's begin the "MAGIE !!!" */
        $toReturn = <<<EOD
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>{$title}</title>
	<style type="text/css">{$styleCss}</style>
	<meta name="robots" content="noindex, follow">
</head>
<body>
	<h1>{$title}</h1>
<span class="menu"><a href="./" onclick="window.history.back()">← back to rss-bridge</a> <a title="Get the ATOM feed" href="./?{$atomquery}"><img alt="feed" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABkAAAAZAgMAAAC5h23wAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAJUExURf+cCv/8+P/OhD2VurcAAAABdFJOU/4a4wd9AAAAbklEQVQI13XOMQ6AIAwFUGzCgLtHYPEUHMHBTwgTRyFOXoDdUT2ltCKbXV6b/iZV6qcMYmY1EJtw3MwFQRJU/Bfl6VaEzENQxbHIUxKTrY4Xgk4ct0EvcrYa9iTPGrxqbE3XHWSfRfIk1trp6O8+R38aBYbaAE4AAAAASUVORK5CYII="></a></span>
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
