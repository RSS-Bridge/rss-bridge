<?php
/**
* Mrss
* Documentation Source http://www.rssboard.org/media-rss
*
* @name Media RSS
*/
class MrssFormat extends FormatAbstract{

    public function stringify(){
        /* Datas preparation */
        $https = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '' );
        $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $httpInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';

        $serverRequestUri = $this->xml_encode($_SERVER['REQUEST_URI']);

        $extraInfos = $this->getExtraInfos();
        $title = $this->xml_encode($extraInfos['name']);
        $uri = $this->xml_encode(!empty($extraInfos['uri']) ? $extraInfos['uri'] : 'https://github.com/sebsauvage/rss-bridge');

        $items = '';
        foreach($this->getDatas() as $data){
            $itemAuthor = is_null($data->author) ? '' : $this->xml_encode($data->author);
            $itemTitle = strip_tags(is_null($data->title) ? '' : $this->xml_encode($data->title));
            $itemUri = is_null($data->uri) ? '' : $this->xml_encode($data->uri);
            $itemTimestamp = is_null($data->timestamp) ? '' : $this->xml_encode(date(DATE_RFC2822, $data->timestamp));
            $itemContent = is_null($data->content) ? '' : $this->xml_encode($this->sanitizeHtml($data->content));
            $items .= <<<EOD

    <item>
        <title>{$itemTitle}</title>
        <link>{$itemUri}</link>
        <guid isPermaLink="true">{$itemUri}</guid>
        <pubDate>{$itemTimestamp}</pubDate>
        <description>{$itemContent}</description>
        <author>{$itemAuthor}</author>
    </item>

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
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{$title}</title>
        <link>http{$https}://{$httpHost}{$httpInfo}/</link>
        <description>{$title}</description>
        <atom:link rel="alternate" type="text/html" href="{$uri}" />
        <atom:link rel="self" href="http{$https}://{$httpHost}{$serverRequestUri}" />
        {$items}
    </channel>
</rss>
EOD;

        // Remove invalid non-UTF8 characters

        // We cannot use iconv because of a bug in some versions of iconv.
        // See http://www.php.net/manual/fr/function.iconv.php#108643
        //$toReturn = iconv("UTF-8", "UTF-8//IGNORE", $toReturn);
        // So we use mb_convert_encoding instead:
        ini_set('mbstring.substitute_character', 'none');
        $toReturn= mb_convert_encoding($toReturn, 'UTF-8', 'UTF-8');
        return $toReturn;
    }

    public function display(){
        $this
            ->setContentType('application/rss+xml; charset=UTF-8')  // We force UTF-8 in RSS output.
            ->callContentType();

        return parent::display();
    }

    private function xml_encode($text) {
        return htmlspecialchars($text, ENT_XML1);
    }
}
