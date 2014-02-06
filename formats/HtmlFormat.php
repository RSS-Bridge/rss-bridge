<?php
/**
* Html
* Documentation Source http://en.wikipedia.org/wiki/Atom_%28standard%29 and http://tools.ietf.org/html/rfc4287
*
* @name Html
*/
class HtmlFormat extends FormatAbstract
{
    public function stringify()
    {
        /* Datas preparation */
        $extraInfos = $this->getExtraInfos();
        $title = htmlspecialchars($extraInfos['name']);
        $uri = htmlspecialchars($extraInfos['uri']);

        $entries = '';
        foreach ($this->getDatas() as $data) {
            $entryUri = is_null($data->uri) ? $uri : $data->uri;
            $entryTitle = is_null($data->title) ? '' : $this->sanitizeHtml(strip_tags($data->title));
            $entryTimestamp = is_null($data->timestamp) ? '' : '<small>' . date(DATE_ATOM, $data->timestamp) . '</small>';
            $entryContent = is_null($data->content) ? '' : '<p>' . $this->sanitizeHtml($data->content). '</p>';
            $entries .= <<<EOD

        <div class="rssitem">
            <h2><a href="{$entryUri}">{$entryTitle}</a></h2>
            {$entryTimestamp}
            {$entryContent}
        </div>

EOD;
        }

        $styleCss = <<<'EOD'
body{font-family:"Trebuchet MS",Verdana,Arial,Helvetica,sans-serif;font-size:10pt;background-color:#aaa;}div.rssitem{border:1px solid black;padding:5px;margin:10px;background-color:#fff;}
EOD;

        /* Data are prepared, now let's begin the "MAGIE !!!" */
        $toReturn = <<<EOD
<html>
    <head>
        <title>{$title}</title>
        <style type="text/css">{$styleCss}</style>
    </head>
    <body>
        <h1>{$title}</h1>
{$entries}
    </body>
</html>
EOD;

        return $toReturn;
    }

    public function display()
    {
        $this
            ->setContentType('text/html; charset=' . $this->getCharset())
            ->callContentType();

        return parent::display();
    }
}
