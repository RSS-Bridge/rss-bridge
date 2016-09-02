<?php
class NiceMatinBridge extends BridgeAbstract{

	const MAINTAINER = "pit-fgfjiudghdf";
	const NAME = "NiceMatin";
	const URI = "http://www.nicematin.com/";
	const DESCRIPTION = "Returns the 10 newest posts from NiceMatin (full text)";

	private function NiceMatinExtractContent($url) {
		$html = $this->getSimpleHTMLDOM($url);
		if(!$html)
			return 'Could not acquire content from url: ' . $url . '!';

		$content = $html->find('article', 0);
		if(!$content)
			return 'Could not find \'section\'!';

		$text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content->innertext);
		$text = strip_tags($text, '<p><a><img>');
		return $text;
	}

	public function collectData(){
        $html = $this->getSimpleHTMLDOM(self::URI.'derniere-minute/rss')
            or $this->returnServerError('Could not request NiceMatin.');
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit >= 10) {
                break;
            }
			// We need to fix the 'link' tag as simplehtmldom cannot parse it (just rename it and load back as dom)
			$element_text = $element->outertext;
			$element_text = str_replace('<link>', '<url>', $element_text);
			$element_text = str_replace('</link>', '</url>', $element_text);
			$element = str_get_html($element_text);

			$item = array();
			$item['title'] = $element->find('title', 0)->innertext;
			$item['uri'] = $element->find('url', 0)->innertext;
			$item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
			$item['content'] = $this->NiceMatinExtractContent($item['uri']);
			$this->items[] = $item;
			$limit++;
		}
	}
}
