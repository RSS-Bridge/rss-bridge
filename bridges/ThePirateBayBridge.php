<?php
class ThePirateBayBridge extends BridgeAbstract{

	const MAINTAINER = "mitsukarenai";
	const NAME = "The Pirate Bay";
	const URI = "https://thepiratebay.org/";
	const DESCRIPTION = "Returns results for the keywords. You can put several list of keywords by separating them with a semicolon (e.g. \"one show;another show\"). Category based search needs the category number as input. User based search takes the Uploader name. Search can be done in a specified category";

    const PARAMETERS = array( array(
        'q'=>array(
            'name'=>'keywords, separated by semicolons',
            'exampleValue'=>'first list;second list;…',
            'required'=>true
        ),
        'crit'=>array(
            'type'=>'list',
            'name'=>'Search type',
            'values'=>array(
                'search'=>'search',
                'category'=>'cat',
                'user'=>'usr'
            )
        ),
        'cat_check'=>array(
            'type'=>'checkbox',
            'name'=>'Specify category for normal search ?',
        ),
		'cat'=>array(
            'name'=>'Category number',
            'exampleValue'=>'100, 200… See TPB for category number'
        ),
        'trusted'=>array(
            'type'=>'checkbox',
            'name'=>'Only get results from Trusted or VIP users ?',
        ),
    ));

	public function collectData(){

        function parseDateTimestamp($element){
                $guessedDate = $element->find('font',0)->plaintext;
                $guessedDate = explode("Uploaded ",$guessedDate)[1];
                $guessedDate = explode(",",$guessedDate)[0];
                if (count(explode(":",$guessedDate)) == 1)
                {
                    $guessedDate = strptime($guessedDate, '%m-%d&nbsp;%Y');
                    $timestamp   = mktime(0, 0, 0,
                                          $guessedDate['tm_mon'] + 1, $guessedDate['tm_mday'], 1900+$guessedDate['tm_year']);
                }
                else if (explode("&nbsp;",$guessedDate)[0] == 'Today')
                {
                    $guessedDate = strptime(explode("&nbsp;",$guessedDate)[1], '%H:%M');
                    $timestamp   = mktime($guessedDate['tm_hour'],    $guessedDate['tm_min'],  0,
                                          date('m'), date('d'), date('Y'));

                }
                else if (explode("&nbsp;",$guessedDate)[0] == 'Y-day')
                {
                    $guessedDate = strptime(explode("&nbsp;",$guessedDate)[1], '%H:%M');
                    $timestamp   = mktime($guessedDate['tm_hour'],    $guessedDate['tm_min'],  0,
                                          date('m',time()-24*60*60), date('d',time()-24*60*60), date('Y',time()-24*60*60));

                }
                else
                {
                    $guessedDate = strptime($guessedDate, '%m-%d&nbsp;%H:%M');
                    $timestamp   = mktime($guessedDate['tm_hour'],    $guessedDate['tm_min'],  0,
                                          $guessedDate['tm_mon'] + 1, $guessedDate['tm_mday'], date('Y'));
                }
                return $timestamp;
        }

		$catBool = $this->getInput('cat_check');
		if ($catBool)
		{
			$catNum = $this->getInput('cat');
		}
		$critList = $this->getInput('crit');
	
	$trustedBool = $this->getInput('trusted');
        $keywordsList = explode(";",$this->getInput('q'));
        foreach($keywordsList as $keywords){
          switch ($critList) {
		    case "search":
				if ($catBool == FALSE)
				{
					$html = getSimpleHTMLDOM(self::URI.'search/'.rawurlencode($keywords).'/0/3/0')
						or returnServerError('Could not request TPB.');
				}
				else
				{
					$html = getSimpleHTMLDOM(self::URI.'search/'.rawurlencode($keywords).'/0/3/'.rawurlencode($catNum))
						or returnServerError('Could not request TPB.');
				}
		        break;
		    case "cat":
		          $html = getSimpleHTMLDOM(self::URI.'browse/'.rawurlencode($keywords).'/0/3/0')
            		or returnServerError('Could not request TPB.');
		        break;
		    case "usr":
		        $html = getSimpleHTMLDOM(self::URI.'user/'.rawurlencode($keywords).'/0/3/0')
            		or returnServerError('Could not request TPB.');
		        break;
		  }

            if ($html->find('table#searchResult', 0) == FALSE)
                returnServerError('No result for query '.$keywords);


            foreach($html->find('tr') as $element) {
		    
		if ( !$trustedBool or !is_null($element->find('img[alt=VIP]', 0)) or !is_null($element->find('img[alt=Trusted]', 0)) ) 
		{
			$item = array();
			$item['uri'] = $element->find('a',3)->href;
			$item['id'] = self::URI.$element->find('a.detLink',0)->href;
			$item['timestamp'] = parseDateTimestamp($element);
			$item['author'] = $element->find('a.detDesc',0)->plaintext;
			$item['title'] = $element->find('a.detLink',0)->plaintext;
			$item['seeders'] = (int)$element->find('td',2)->plaintext;
			$item['leechers'] = (int)$element->find('td',3)->plaintext;
			$item['content'] = $element->find('font',0)->plaintext.'<br>seeders: '.$item['seeders'].' | leechers: '.$item['leechers'].'<br><a href="'.$item['id'].'">info page</a>';
			if(isset($item['title']))
			    $this->items[] = $item;
		}
            }
        }
	}
}
