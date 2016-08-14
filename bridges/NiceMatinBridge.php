<?php
class NiceMatinBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "pit-fgfjiudghdf";
		$this->name = "NiceMatin";
		$this->uri = "http://www.nicematin.com/";
		$this->description = "Returns the 10 newest posts from NiceMatin (full text)";
		$this->update = "2016-08-09";
	}

	private function NiceMatinExtractContent($url) {
		$html = $this->file_get_html($url);
		if(!$html)
			$this->returnError('Could not acquire content from url: ' . $url . '!', 404);
		
		$content = $html->find('article', 0);
		if(!$content)
			$this->returnError('Could not find \'section\'!', 404);
		
		$text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content->innertext);
		$text = strip_tags($text, '<p><a><img>');
		return $text;
	}

	public function collectData(array $param){
		$html = $this->file_get_html('http://www.nicematin.com/derniere-minute/rss') or $this->returnError('Could not request NiceMatin.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 10) {
				// We need to fix the 'link' tag as simplehtmldom cannot parse it (just rename it and load back as dom)
				$element_text = $element->outertext;
				$element_text = str_replace('<link>', '<url>', $element_text);
				$element_text = str_replace('</link>', '</url>', $element_text);
				$element = str_get_html($element_text);

				$item = new \Item();
				$item->title = $element->find('title', 0)->innertext;
				$item->uri = $element->find('url', 0)->innertext;
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content = $this->NiceMatinExtractContent($item->uri);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
