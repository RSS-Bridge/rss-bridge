<?php
/**
* Atom
* Documentation Source http://en.wikipedia.org/wiki/Atom_%28standard%29 and http://tools.ietf.org/html/rfc4287
*
* @name Atom
*/
class AtomFormat extends FormatAbstract{

    public function stringify(){
        /* Datas preparation */
        $https = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '' );
        $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $httpInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';

        $serverRequestUri = htmlspecialchars($_SERVER['REQUEST_URI']);

        $extraInfos = $this->getExtraInfos();
        $title = htmlspecialchars($extraInfos['name']);
        $uri = htmlspecialchars($extraInfos['uri']);

        $entries = '';
        foreach($this->getDatas() as $data){
            $entryName = is_null($data->name) ? $title : $data->name;
            $entryAuthor = is_null($data->author) ? $uri : $data->author;
            $entryTitle = is_null($data->title) ? '' : $data->title;
            $entryUri = is_null($data->uri) ? '' : $data->uri;
            $entryTimestamp = is_null($data->timestamp) ? '' : date(DATE_ATOM, $data->timestamp);
            $entryContent = is_null($data->content) ? '' : '<![CDATA[' . htmlentities($data->content) . ']]>';

            $entries .= <<<EOD

    <entry>
        <author>
            <name>{$entryName}</name>
            <uri>{$entryAuthor}</uri>
        </author>
        <title type="html"><![CDATA[{$entryTitle}]]></title>
        <link rel="alternate" type="text/html" href="{$entryUri}" />
        <id>{$entryUri}</id>
        <updated>{$entryTimestamp}</updated>
        <content type="html">{$entryContent}</content>
    </entry>

EOD;
        }

        /*
        TODO :
        - Security: Disable Javascript ?
        - <updated> : Define new extra info ?
        - <content type="html"> : RFC look with xhtml, keep this in spite of ?
        */

        /* Data are prepared, now let's begin the "MAGIE !!!" */
        $toReturn  = '<?xml version="1.0" encoding="UTF-8"?>';
        $toReturn .= <<<EOD
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xml:lang="en-US">

    <title type="text">{$title}</title>
    <id>http{$https}://{$httpHost}{$httpInfo}/</id>
    <updated></updated>
    <link rel="alternate" type="text/html" href="{$uri}" />
    <link rel="self" href="http{$https}://{$httpHost}{$serverRequestUri}" />
{$entries}
</feed>
EOD;

        return $toReturn;
    }

    public function display(){
        // $this
            // ->setContentType('application/atom+xml; charset=' . $this->getCharset())
            // ->callContentType();

        return parent::display();
    }
}