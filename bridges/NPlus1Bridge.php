<?php
class NPlus1Bridge extends BridgeAbstract {

	const NAME = 'N+1 Bridge';
	const URI = 'https://nplus1.ru';
	const DESCRIPTION = 'Returns newest articles by rubric or theme';
	const MAINTAINER = 'em92';

	const PARAMETERS = array(
		'By rubric' => array(
			'rubric' => array(
				'name' => 'rubric',
				'exampleValue' => 'it',
				'required' => true
			)
		),
		'By theme' => array(
			'theme' => array(
				'name' => 'theme',
				'exampleValue' => "up-we-go",
				'required' => true
			)
		)
	);

	protected $pageName = 'N+1';

	public function getURI() {
		if ($this->getInput("rubric")) {
			return self::URI . "/rubric/" . urlencode($this->getInput("rubric"));
		} else if ($this->getInput("theme")) {
			return self::URI . "/theme/" . urlencode($this->getInput("theme"));
		}
		return parent::getURI();
	}

	public function getName() {
		return html_entity_decode( $this->pageName );
	}

	private function parseNewsPost($post) {
		$a = $post->find("a", 0);
		$date = $post->find(".date > span", 0)->getAttribute('title');

		$item = array();
		$item['post_id'] = intval( $a->getAttribute('data-id') );
		$item['uri'] = self::URI . $a->getAttribute('href');
		$item['timestamp'] = strtotime($date);
		$item['title'] = $post->find("h3", 0)->plaintext;
		return $item;
	}

	private function parseArticlePost($post) {
		$a = $post->find("a", 0);
		$href = $a->getAttribute('href');

		$item = array();
		preg_match('/\/.+?\/(\d+)\/(\d+)\/(\d+)\//', $href, $result);
		if (count($result) > 0) {
			$date = $result[1] . "-" . $result[2] . "-" . $result[3];
			$item['timestamp'] = strtotime($date);
		}

		$item['post_id'] = intval( $a->getAttribute('data-id') );
		$item['uri'] = self::URI . $a->getAttribute('href');
		$item['title'] = $post->find("h3", 0)->plaintext;
		$item['content'] = $post->find("p", 0)->plaintext;
		return $item;
	}

	public function collectData() {

		if ($this->getInput("rubric")) {
			$link = self::URI . "/rubric/" . urlencode($this->getInput("rubric"));
		} else if ($this->getInput("theme")) {
			$link = self::URI . "/theme/" . urlencode($this->getInput("theme"));
		} else {
			returnServerError("No input given");
		}

		$text_html = getContents($link);
		$html = str_get_html($text_html);

		$err_element = $html->find("#err", 0);
		if (is_object($err_element)) {
			returnServerError("Error on site: " . $err_element->plaintext);
		}

		$this->pageName .= ": " . $html->find("title", 0)->plaintext;

		foreach($html->find("article.item") as $post) {

			if (strpos($post->getAttribute('class'), 'item-news') !== false) {
				$this->items[] = $this->parseNewsPost($post);
			} else if (strpos($post->getAttribute('class'), 'item-article') !== false) {
				$this->items[] = $this->parseArticlePost($post);
			} else {
				continue;
			}

		}

		usort($this->items, function ($item1, $item2) {
			return $item2['post_id'] - $item1['post_id'];
		});
	}
}
