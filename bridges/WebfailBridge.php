<?php
class WebfailBridge extends BridgeAbstract {
	const MAINTAINER = 'logmanoriginal';
	const URI = 'https://webfail.com';
	const NAME = 'Webfail';
	const DESCRIPTION = 'Returns the latest fails';
	const PARAMETERS = array(
		'By content type' => array(
			'language' => array(
				'name' => 'Language',
				'type' => 'list',
				'title' => 'Select your language',
				'values' => array(
					'English' => 'en',
					'German' => 'de'
				),
				'defaultValue' => 'English'
			),
			'type' => array(
				'name' => 'Type',
				'type' => 'list',
				'title' => 'Select your content type',
				'values' => array(
					'None' => '/',
					'Facebook' => '/ffdts',
					'Images' => '/images',
					'Videos' => '/videos',
					'Gifs' => '/gifs'
				),
				'defaultValue' => 'None'
			)
		)
	);

	public function getURI(){
		if(is_null($this->getInput('language')))
			return self::URI;

		// e.g.: https://en.webfail.com
		return 'https://' . $this->getInput('language') . '.webfail.com';
	}

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI() . $this->getInput('type'));

		$type = array_search($this->getInput('type')
		, self::PARAMETERS[$this->queriedContext]['type']['values']);

		switch(strtolower($type)){
		case 'facebook':
		case 'videos':
			$this->ExtractNews($html, $type);
			break;
		case 'none':
		case 'images':
		case 'gifs':
			$this->ExtractArticle($html);
			break;
		default: returnClientError('Unknown type: ' . $type);
		}
	}

	private function ExtractNews($html, $type){
		$news = $html->find('#main', 0)->find('a.wf-list-news');
		foreach($news as $element){
			$item = array();
			$item['title'] = $this->fixTitle($element->find('div.wf-news-title', 0)->innertext);
			$item['uri'] = $this->getURI() . $element->href;

			$img = $element->find('img.wf-image', 0)->src;
			// Load high resolution image for 'facebook'
			switch(strtolower($type)){
			case 'facebook':
				$img = $this->getImageHiResUri($item['uri']);
				break;
			default:
			}

			$description = '';
			if(!is_null($element->find('div.wf-news-description', 0))){
				$description = $element->find('div.wf-news-description', 0)->innertext;
			}

			$item['content'] = '<p>'
			. $description
			. '</p><br><a href="'
			. $item['uri']
			. '"><img src="'
			. $img
			. '"></a>';

			$this->items[] = $item;
		}
	}

	private function ExtractArticle($html){
		$articles = $html->find('article');
		foreach($articles as $article){
			$item = array();
			$item['title'] = $this->fixTitle($article->find('a', 1)->innertext);

			// Webfail shares videos or images
			if(!is_null($article->find('img.wf-image', 0))){ // Image type
				$item['uri'] = $this->getURI() . $article->find('a', 2)->href;
				$item['content'] = '<a href="'
				. $item['uri']
				. '"><img src="'
				. $article->find('img.wf-image', 0)->src
				. '">';
			} elseif(!is_null($article->find('div.wf-video', 0))){ // Video type
				$videoId = $this->getVideoId($article->find('div.wf-play', 0)->onclick);
				$item['uri'] = 'https://youtube.com/watch?v=' . $videoId;
				$item['content'] = '<a href="'
				. $item['uri']
				. '"><img src="http://img.youtube.com/vi/'
				. $videoId
				. '/0.jpg"></a>';
			}

			$this->items[] = $item;
		}
	}

	private function fixTitle($title){
		// This fixes titles that include umlauts (in German language)
		return html_entity_decode($title, ENT_QUOTES | ENT_HTML401, 'UTF-8');
	}

	private function getVideoId($onclick){
		return substr($onclick, 21, 11);
	}

	private function getImageHiResUri($url){
		// https://de.webfail.com/ef524fae509?tag=ffdt
		// http://cdn.webfail.com/upl/img/ef524fae509/post2.jpg
		$id = substr($url, strrpos($url, '/') + 1, strlen($url) - strrpos($url, '?') + 2);
		return 'http://cdn.webfail.com/upl/img/' . $id . '/post2.jpg';
	}
}
