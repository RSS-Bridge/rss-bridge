<?php

/* var_dump($jsonDecode); */ 

class InformationIsBeautifulBridge extends BridgeAbstract{

	const MAINTAINER = 'DNO';
	const NAME = 'Information is beautiful';
	const URI = 'https://informationisbeautiful.net';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'get new articles';
	const PARAMETERS = array();


	public function collectData() {
        // 
        // $backupImgUrl ="https://infobeautiful4.s3.amazonaws.com/www/img/information-is-beautiful-logo-text-dark.svg";
        $backupImgUrl ="https://novta.dev/_varPubRes/a0e254394-imgnotfound.png";
        $baseurl ="https://infobeautiful4.s3.amazonaws.com";
        $articleBaseurl ="https://informationisbeautiful.net";
        $ch = curl_init();
        $url =$baseurl."/www/data/posts.json";
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url
          , CURLOPT_HEADER => 0
          , CURLOPT_RETURNTRANSFER => 1
          , CURLOPT_ENCODING => 'gzip'));
        $jsonRaw = curl_exec($ch);
        $jsonDecode =json_decode($jsonRaw);
        foreach ($jsonDecode as $entry){
            /* echo 'title: '.$entry[0]; */
            /* echo 'subtitle'.$entry[1]; */
            /* echo 'url'.$entry[2]; */
            /* echo 'image'.$entry[3]; */
            /* echo 'timestamp'.$entry[8]; */
            $item = array(); 
            $item['title'] = $entry[0];
            $articleUrl =$articleBaseurl.$entry[2];
            $item['uri'] = $articleUrl;
            $item['timestamp'] = $entry[8];
            $imgUrl=$baseurl.$entry[3];
            $imgCaption = $entry[1];
            if(empty($entry[3])){ $imgUrl = $backupImgUrl; }
            $content ="<style> tr { border:none; } </style>
                <table>
                <tr><td><h1>{$imgCaption}</h1></td></tr>
                <tr><td><img style='max-width:600px;' src='{$imgUrl}'></td></tr> 
                </table>";
            $item['content'] = $content;
            $this->items[] = $item; // Add item to the list
        }
    }
}
