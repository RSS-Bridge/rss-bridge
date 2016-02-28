<?php

function xml_encode($text) {
	return htmlspecialchars($text, ENT_XML1);
}

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

        $serverRequestUri = xml_encode($_SERVER['REQUEST_URI']);

        $extraInfos = $this->getExtraInfos();
        $title = xml_encode($extraInfos['name']);
        $uri = $extraInfos['uri'];
        $icon = xml_encode('http://icons.better-idea.org/icon?url='. $uri .'&size=64');
        $uri = xml_encode($uri);

        $entries = '';
        foreach($this->getDatas() as $data){
            $entryName = is_null($data->name) ? $title : xml_encode($data->name);
            $entryAuthor = is_null($data->author) ? $uri : xml_encode($data->author);
            $entryTitle = is_null($data->title) ? '' : xml_encode($data->title);
            $entryUri = is_null($data->uri) ? '' : xml_encode($data->uri);
            $entryTimestamp = is_null($data->timestamp) ? '' : xml_encode(date(DATE_ATOM, $data->timestamp));
            // We prevent content from closing the CDATA too early.
            $entryContent = is_null($data->content) ? '' : '<![CDATA[' . $this->sanitizeHtml(str_replace(']]>','',$data->content)) . ']]>';

			// We generate a list of the enclosure links
			$entryEnclosures = "";
            
			foreach($data->enclosures as $enclosure) {

				$entryEnclosures .= "<link rel=\"enclosure\" href=\"".$enclosure."\"></link>";

			}

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
		{$entryEnclosures}
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
    <icon>{$icon}</icon>
    <logo>{$icon}</logo>
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
            ->setContentType('application/atom+xml; charset=UTF-8')  // We force UTF-8 in ATOM output.
            ->callContentType();

        return parent::display();
    }
}
