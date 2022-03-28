<?php
class UbuntuUsersBridge extends BridgeAbstract{

	const MAINTAINER = 'DNO';
	const NAME = 'Ubuntu Users';
	const URI = "https://wiki.ubuntuusers.de";
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = "get the latest article from ubuntuusers.de";
	const PARAMETERS = array();


	public function collectData() {
        $html = getSimpleHTMLDOM('https://wiki.ubuntuusers.de/Wiki/Neue_Artikel/');
        
        $main = $html->find('#page',0);
        foreach($main->find('li') as $li){
            $litext = $li->plaintext;
            $litext = trim($litext);
            if(substr($litext, 10, 1)==":") {
            
                $item = array(); 
                $title = explode(", von ", substr($litext,12));
                $item['title'] = $title[0];

                $engDate = substr($litext,6,4)."-".substr($litext,3,2)."-".substr($litext,0,2);


                if( !is_null($li->find('.internal', 0))){
                    $link = $li->find('.internal',0)->href;
                }else{
                    $link = $author = "#";
                }

                $item['uri'] = $link;

                if( !is_null($li->find('.user', 0))){
                    $item['author'] = $li->find('.user',0)->plaintext;
                }else{
                    $item['author'] = $author = "unknown";
                }

                $item['timestamp']= $engDate;
                $item['content'] = $litext;

                $this->items[] = $item; 
            }
        }
        
    }
}
