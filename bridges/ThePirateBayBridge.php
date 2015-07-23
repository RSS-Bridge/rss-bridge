<?php
/**
* RssBridgeThePirateBay
* Returns results for the keywords. You can put several list of keywords by separating them with a semicolon (e.g. "one show;another show")
* 2014-05-25
*
* @name The Pirate Bay
* @homepage https://thepiratebay.vg/
* @description Returns results for the keywords. You can put several list of keywords by separating them with a semicolon (e.g. "one show;another show")
* @maintainer mitsukarenai
* @update 2014-05-26
* @use1(q="first list;second list;...")
 */

class ThePirateBayBridge extends BridgeAbstract{

	public function collectData(array $param){

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


		if (!isset($param['q']))
			$this->returnError('You must specify keywords (?q=...)', 400);

        $keywordsList = explode(";",$param['q']); 
        foreach($keywordsList as $keywords){
            $html = file_get_html('https://thepiratebay.vg/search/'.rawurlencode($keywords).'/0/3/0') or $this->returnError('Could not request TPB.', 404);

            if ($html->find('table#searchResult', 0) == FALSE)
                $this->returnError('No result for query '.$keywords, 404);


            foreach($html->find('tr') as $element) {
                $item = new \Item();
                $item->id = 'https://thepiratebay.vg'.$element->find('a.detLink',0)->href;
                $item->uri = $element->find('a',3)->href;
                $item->timestamp = parseDateTimestamp($element);
                $item->title = $element->find('a.detLink',0)->plaintext;
                $item->seeders = (int)$element->find('td',2)->plaintext;
                $item->leechers = (int)$element->find('td',3)->plaintext;
                $item->content = $element->find('font',0)->plaintext.'<br>seeders: '.$item->seeders.' | leechers: '.$item->leechers.'<br><a href="'.$element->find('a',3)->href.'">download</a>';
                if(!empty($item->title))
                    $this->items[] = $item;
            }
        }
	}
    
    public function getName(){
        return 'The Pirate Bay';
    }

    public function getURI(){
        return 'https://thepiratebay.vg/';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
