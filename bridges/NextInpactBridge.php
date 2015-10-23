<?php
/**
* RssBridgeNextinpact
* Returns the newest articles
* 2014-05-25
*
* @name NextInpact Bridge
* @homepage http://www.nextinpact.com/
* @description Returns the newest articles.
* @maintainer qwertygc
* @update 2015-10-23
*/
class NextInpactBridge extends BridgeAbstract {

	public function collectData(array $param) {

		function StripCDATA($string) {
			$string = str_replace('<![CDATA[', '', $string);
			$string = str_replace(']]>', '', $string);
			return $string;
		}

		function ExtractContent($url) {
			$html2 = file_get_html($url);
			$text = '<p><em>'.$html2->find('span.sub_title', 0)->innertext.'</em></p>'
				.'<p><img src="'.$html2->find('div.container_main_image_article', 0)->find('img.dedicated',0)->src.'" alt="-" /></p>'
				.'<div>'.$html2->find('div[itemprop=articleBody]', 0)->innertext.'</div>';
			$premium_article = $html2->find('h2.title_reserve_article', 0);
			if (is_object($premium_article))
				$text = $text.'<p><em>'.$premium_article->innertext.'</em></p>';
			return $text;
		}

		$html = file_get_html('http://www.nextinpact.com/rss/news.xml') or $this->returnError('Could not request NextInpact.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
		 if($limit < 3) {
				$item = new \Item();
				$item->title = StripCDATA($element->find('title', 0)->innertext);
				$item->uri = StripCDATA($element->find('guid', 0)->plaintext);
				$item->thumbnailUri = StripCDATA($element->find('enclosure', 0)->url);
				$item->author = StripCDATA($element->find('author', 0)->innertext);
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content = ExtractContent($item->uri);
				$this->items[] = $item;
				$limit++;
			}
		}

    }

	public function getName() {
		return 'Nextinpact Bridge';
	}

	public function getURI() {
		return 'http://www.nextinpact.com/';
	}

	public function getCacheDuration() {
		return 3600; // 1 hour
		// return 0;
	}
}
