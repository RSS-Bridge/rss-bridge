<?php
class ScmbBridge extends BridgeAbstract{

	public $maintainer = "Astalaseven";
	public $name = "Se Coucher Moins Bête Bridge";
	public $uri = "http://secouchermoinsbete.fr/";
	public $description = "Returns the newest anecdotes.";

    public function collectData(){
        $html = '';
        $html = $this->getSimpleHTMLDOM('http://secouchermoinsbete.fr/') or $this->returnServerError('Could not request Se Coucher Moins Bete.');

        foreach($html->find('article') as $article) {
        	$item = array();
			$item['uri'] = 'http://secouchermoinsbete.fr'.$article->find('p.summary a',0)->href;
			$item['title'] = $article->find('header h1 a',0)->innertext;

			$article->find('span.read-more',0)->outertext=''; // remove text "En savoir plus" from anecdote content
			$content = $article->find('p.summary a',0)->innertext;
			$content =substr($content,0,strlen($content)-17); // remove superfluous spaces at the end

			// get publication date
			$str_date = $article->find('time',0)->datetime;
			list($date, $time) = explode(' ', $str_date);
			list($y, $m, $d) = explode('-', $date);
			list($h, $i) = explode(':', $time);
			$timestamp = mktime($h,$i,0,$m,$d,$y);
			$item['timestamp'] = $timestamp;


			$item['content'] = $content;
			$this->items[] = $item;
		}
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
