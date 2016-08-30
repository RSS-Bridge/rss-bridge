<?php
class NextInpactBridge extends BridgeAbstract {

	public $maintainer = "qwertygc";
	public $name = "NextInpact Bridge";
	public $uri = "http://www.nextinpact.com/";
	public $description = "Returns the newest articles.";

	private function StripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	private function ExtractContent($url) {
		$html2 = $this->getSimpleHTMLDOM($url);
		$text = '<p><em>'.$html2->find('span.sub_title', 0)->innertext.'</em></p>'
			.'<p><img src="'.$html2->find('div.container_main_image_article', 0)->find('img.dedicated',0)->src.'" alt="-" /></p>'
			.'<div>'.$html2->find('div[itemprop=articleBody]', 0)->innertext.'</div>';
		$premium_article = $html2->find('h2.title_reserve_article', 0);
		if (is_object($premium_article))
			$text = $text.'<p><em>'.$premium_article->innertext.'</em></p>';
		return $text;
	}

	public function collectData(){
		$html = $this->getSimpleHTMLDOM($this->uri.'rss/news.xml') or $this->returnServerError('Could not request NextInpact.');
		$limit = 0;

		foreach($html->find('item') as $element) {
		 if($limit < 3) {
				$item = array();
				$item['title'] = $this->StripCDATA($element->find('title', 0)->innertext);
				$item['uri'] = $this->StripCDATA($element->find('guid', 0)->plaintext);
				$item['author'] = $this->StripCDATA($element->find('creator', 0)->innertext);
				$item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
				$item['content'] = $this->ExtractContent($item['uri']);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
