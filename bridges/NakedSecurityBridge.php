<?php
class NakedSecurityBridge extends FeedExpander {

	const MAINTAINER = 'ORelio';
	const NAME = 'Naked Security';
	const URI = 'https://nakedsecurity.sophos.com/';
	const DESCRIPTION = 'Returns the newest articles.';

	private function StripRecursiveHTMLSection($string, $tag_name, $tag_start) {
		$open_tag = '<'.$tag_name;
		$close_tag = '</'.$tag_name.'>';
		$close_tag_length = strlen($close_tag);
		if (strpos($tag_start, $open_tag) === 0) {
			while (strpos($string, $tag_start) !== false) {
				$max_recursion = 100;
				$section_to_remove = null;
				$section_start = strpos($string, $tag_start);
				$search_offset = $section_start;
				do {
					$max_recursion--;
					$section_end = strpos($string, $close_tag, $search_offset);
					$search_offset = $section_end + $close_tag_length;
					$section_to_remove = substr($string, $section_start, $section_end - $section_start + $close_tag_length);
					$open_tag_count = substr_count($section_to_remove, $open_tag);
					$close_tag_count = substr_count($section_to_remove, $close_tag);
				} while ($open_tag_count > $close_tag_count && $max_recursion > 0);
				$string = str_replace($section_to_remove, '', $string);
			}
		}
		return $string;
	}


	protected function parseItem($item){
		$item = parent::parseItem($item);

		$article_html = $this->getSimpleHTMLDOMCached($item['uri']);
		if(!$article_html){
			$item['content'] = 'Could not request '.$this->getName().': '.$item['uri'];
			return $item;
		}

		$article_image = $article_html->find('img.wp-post-image', 0)->src;
		$article_content = $article_html->find('div.entry-content', 0)->innertext;
		$article_content = $this->StripRecursiveHTMLSection($article_content , 'div', '<div class="entry-prefix"');
		$article_content = $this->StripRecursiveHTMLSection($article_content , 'script', '<script');
		$article_content = $this->StripRecursiveHTMLSection($article_content , 'aside', '<aside');
		$article_content = '<p><img src="'.$article_image.'" /></p><p><b>'.$item['content'].'</b></p>'.$article_content;

		$item['content'] = $article_content;

		return $item;

	}

	public function collectData(){

		$feedUrl = 'https://feeds.feedburner.com/nakedsecurity?format=xml';
		$this->collectExpandableDatas($feedUrl);
	}
}
