<?php
/**
* RssBridgeSeCoucherMoinsBete 
* Returns the newest anecdotes
*
* @name Se Coucher Moins Bête Bridge
* @description Returns the newest anecdotes with their embedded content if any (additional details, picture, video)
*/
class ScmbBridge extends BridgeAbstract{
    
    public function collectData(array $param){
        $html = '';
        $html = file_get_html('http://secouchermoinsbete.fr/') or $this->returnError('Could not request Se Coucher Moins Bete.', 404);
    
        foreach($html->find('article') as $article) {
        	$item = new \Item();
			$item->uri = 'http://secouchermoinsbete.fr'.$article->find('p.summary a',0)->href;
			$item->title = $article->find('header h1 a',0)->innertext;
			
			$article->find('span.read-more',0)->outertext=''; // remove text "En savoir plus" from anecdote content
			$content = $article->find('p.summary a',0)->innertext;
			$content =substr($content,0,strlen($content)-17); // remove superfluous spaces at the end
			
			// get publication date
			$str_date = $article->find('time',0)->datetime;
			list($date, $time) = explode(' ', $str_date);
			list($y, $m, $d) = explode('-', $date);
			list($h, $i) = explode(':', $time);
			$timestamp = mktime($h,$i,0,$m,$d,$y);
			$item->timestamp = $timestamp;
			
			// TODO: this should be optional since it is highly time and broadband consuming
			// check if the anecdote has more content to offer (text details, picture, video) and follow link to retrieve it if that is the case
			$optcontent = $article->find('div.metadata-list a');
			$hasPic = (preg_match("#pas#", $optcontent[0]->innertext)) ? false : true;
			$hasVid = (preg_match("#pas#", $optcontent[1]->innertext)) ? false : true;
			$hasDetails = (preg_match("#pas#", $optcontent[2]->innertext)) ? false : true;
	
			if($hasDetails || $hasPic || $hasVid) $opt_html = file_get_html($item->uri);
			if($hasDetails){
				$details = $opt_html->find('p.details',0)->innertext;
				$content = $content . '<br />' . $details;
			}
			if($hasPic){
				$picUri = $opt_html->find('div#sources-image-wrapper a',0)->href;
				$item->pictureUri = $picUri;
				$content = $content . '<br /><img src="' . $item->pictureUri . '" />';
			}
			if($hasVid){
				$vidUri = $opt_html->find('div#sources-video-wrapper iframe',0)->src;
				$vidUri = explode('?', $vidUri)[0]; // remove "?autoplay=0"
				$item->vidUri = $vidUri;
				$content = $content . ' <a href="' . $vidUri . '">Vidéo</a>';
			}
			
			$item->content = $content;
			$this->items[] = $item;
		}
    }

    public function getName(){
        return 'Se Coucher Moins Bête Bridge';
    }

    public function getURI(){
        return 'http://secouchermoinsbete.fr/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
