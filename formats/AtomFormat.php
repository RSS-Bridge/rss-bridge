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
            // We prevent content from closing the CDATA too early.
            $entryContent = is_null($data->content) ? '' : '<![CDATA[' . $this->sanitizeHtml(str_replace(']]>','',$data->content)) . ']]>';

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

// ####  TEMPORARY FIX ###
$feedTimestamp = date(DATE_ATOM, time());
//  ################ 

        /* Data are prepared, now let's begin the "MAGIE !!!" */
        $toReturn  = '<?xml version="1.0" encoding="UTF-8"?>';
        $toReturn .= <<<EOD
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:thr="http://purl.org/syndication/thread/1.0" xml:lang="en-US">

    <title type="text">{$title}</title>
    <id>http{$https}://{$httpHost}{$httpInfo}/</id>
    <updated>{$feedTimestamp}</updated>
    <link rel="alternate" type="text/html" href="{$uri}" />
    <link rel="self" href="http{$https}://{$httpHost}{$serverRequestUri}" />
{$entries}
</feed>
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
            ->setContentType('application/atom+xml; charset=utf8')  // We force UTF-8 in ATOM output.
            ->callContentType();

        return parent::display();
    }
}