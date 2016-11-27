<?php
class ReadComicsBridge extends BridgeAbstract{

	const MAINTAINER = "niawag";
	const NAME = "Read Comics";
	const URI = "http://www.readcomics.tv/";
	const DESCRIPTION = "Enter the comics as they appear in the website uri, separated by semicolons, ex: good-comic-1;good-comic-2; ...";

    const PARAMETERS = array( array(
        'q'=>array(
            'name'=>'keywords, separated by semicolons',
            'exampleValue'=>'first list;second list;...',
            'required'=>true
        ),
    ));

	public function collectData(){

        function parseDateTimestamp($element){
            $guessedDate = $element->find('span',0)->plaintext;
            $guessedDate = strptime($guessedDate, '%m/%d/%Y');
            $timestamp   = mktime(0, 0, 0, $guessedDate['tm_mon'] + 1, $guessedDate['tm_mday'], date('Y'));
            
            return $timestamp;
        }

        $keywordsList = explode(";",$this->getInput('q'));
        foreach($keywordsList as $keywords){
			$html = $this->getSimpleHTMLDOM(self::URI.'comic/'.rawurlencode($keywords))
						or $this->returnServerError('Could not request readcomics.tv.');

            foreach($html->find('li') as $element) {
                $item = array();
                $item['uri'] = $element->find('a.ch-name',0)->href;                
                $item['id'] = $item['uri'];                
                $item['timestamp'] = parseDateTimestamp($element);
                $item['title'] = $element->find('a.ch-name',0)->plaintext;
                if(isset($item['title']))
                    $this->items[] = $item;
            }
        }
	}
}
