<?php
/**
* Atom
* Documentation Source http://en.wikipedia.org/wiki/Atom_%28standard%29 and http://tools.ietf.org/html/rfc4287
*/
class AtomFormat extends FormatAbstract{

    public function stringify(){
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '';
        $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $httpInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';

        $serverRequestUri = $this->xml_encode($_SERVER['REQUEST_URI']);

        $extraInfos = $this->getExtraInfos();
        $title = $this->xml_encode($extraInfos['name']);
        $uri = !empty($extraInfos['uri']) ? $extraInfos['uri'] : 'https://github.com/sebsauvage/rss-bridge';
        $icon = $this->xml_encode('http://icons.better-idea.org/icon?url='. $uri .'&size=64');
        $uri = $this->xml_encode($uri);

        $entries = '';
        foreach($this->getItems() as $item){
            $entryAuthor = isset($item['author']) ? $this->xml_encode($item['author']) : '';
            $entryTitle = isset($item['title']) ? $this->xml_encode($item['title']) : '';
            $entryUri = isset($item['uri']) ? $this->xml_encode($item['uri']) : '';
            $entryTimestamp = isset($item['timestamp']) ? $this->xml_encode(date(DATE_ATOM, $item['timestamp'])) : '';
            $entryContent = isset($item['content']) ? $this->xml_encode($this->sanitizeHtml($item['content'])) : '';
            $entries .= <<<EOD

    <entry>
        <author>
            <name>{$entryAuthor}</name>
        </author>
        <title type="html"><![CDATA[{$entryTitle}]]></title>
        <link rel="alternate" type="text/html" href="{$entryUri}" />
        <id>{$entryUri}</id>
        <updated>{$entryTimestamp}</updated>
        <content type="html">{$entryContent}</content>
    </entry>

EOD;
        }

    $feedTimestamp = date(DATE_ATOM, time());

        /* Data are prepared, now let's begin the "MAGIE !!!" */
        $toReturn  = '<?xml version="1.0" encoding="UTF-8"?>';
        $toReturn .= <<<EOD
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xml:lang="en-US">

    <title type="text">{$title}</title>
    <id>http{$https}://{$httpHost}{$httpInfo}/</id>
    <icon>{$icon}</icon>
    <logo>{$icon}</logo>
    <updated>{$feedTimestamp}</updated>
    <link rel="alternate" type="text/html" href="{$uri}" />
    <link rel="self" href="http{$https}://{$httpHost}{$serverRequestUri}" />
{$entries}
</feed>
EOD;
        
        // Remove invalid non-UTF8 characters
        ini_set('mbstring.substitute_character', 'none');
        $toReturn= mb_convert_encoding($toReturn, 'UTF-8', 'UTF-8'); 
        return $toReturn;
    }

    public function display(){
        $this
            ->setContentType('application/atom+xml; charset=UTF-8')
            ->callContentType();

        return parent::display();
    }

    private function xml_encode($text) {
        return htmlspecialchars($text, ENT_XML1);
    }
}
