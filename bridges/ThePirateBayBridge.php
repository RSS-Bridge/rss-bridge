<?php
class ThePirateBayBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "The Pirate Bay";
		$this->uri = "https://thepiratebay.org/";
		$this->description = "Returns results for the keywords. You can put several list of keywords by separating them with a semicolon (e.g. \"one show;another show\")";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "keywords, separated by semicolons",
				"identifier" : "q",
				"exampleValue" : "first list;second list;..."
			}
		]';
	}

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
            $html = $this->file_get_html('https://thepiratebay.org/search/'.rawurlencode($keywords).'/0/3/0') or $this->returnError('Could not request TPB.', 404);

            if ($html->find('table#searchResult', 0) == FALSE)
                $this->returnError('No result for query '.$keywords, 404);


            foreach($html->find('tr') as $element) {
                $item = new \Item();
                $item->uri = 'https://thepiratebay.org/'.$element->find('a.detLink',0)->href;
                $item->id = $item->uri;
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
}
