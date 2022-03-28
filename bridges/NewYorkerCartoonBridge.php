<?php
class NewYorkerCartoonBridge extends BridgeAbstract{

	const MAINTAINER = 'DNO';
	const NAME = 'New Yorker Cartoon Bridge';
	const URI = 'https://www.newyorker.com';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Get New Yorker Cartoons';
	const PARAMETERS = array();


	public function collectData() {
        $html = getSimpleHTMLDOM('https://www.newyorker.com/cartoons/daily-cartoon');
        
        $main = $html->find('main',1);
        $list = $main->find('ul',0);
        foreach($list->find('li') as $li){
            $item = array(); 
            $item['title'] = $li->find('h4',0)->plaintext;

            $imgTag = $li->find('img',0)->save();
            $imgUrlArray = array();
            preg_match('/src="([^"]*)"/i', $imgTag, $imgUrlArray);
            $imgUrl = $imgUrlArray[1];
            $author = $li->find('p',0)->plaintext;
            $item['author'] = str_replace("By ", "", $author);
            $imgUrl = str_replace("w_116","w_1280",$imgUrl);

            $varme = $li->find('img',0)->save();
            //$item['uri'] = 'https://www.youtube.com/watch?v=a6MApuOG1z4';

            $imgCaption = $li->find('h5',0)->plaintext;
            $item['timestamp']= $li->find('h6',0)->plaintext;
            $articleUrl = $li->find('a',0)->href;
            $articleUrl = "https://www.newyorker.com/{$articleUrl}";
            $item['content'] = "<table> <tr><td><img src='{$imgUrl}'></td></tr> <tr><td>{$imgCaption}</td></tr><tr><td>{$author}</td></tr> </table>";
            $item['uri'] = $articleUrl;


            $this->items[] = $item; // Add item to the list
        }
        
    }
}
