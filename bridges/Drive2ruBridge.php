<?php
class Drive2ruBridge extends BridgeAbstract {

	const MAINTAINER = 'dotter-ak ';
	const NAME = 'Бортжурналы на Drive2.ru';
	const URI = 'https://drive2.ru/';
	const DESCRIPTION = 'Лента бортжурналов по выбранной марке или машине. Также работает с фильтром по категориям';
	const PARAMETERS = array(
		array(
			'url' => array(
				'name' => 'Ссылка на страницу с бортжурналом',
				'type' => 'text',
				'required' => true,
				'title' => 'Например: https://www.drive2.ru/experience/suzuki/g4895/',
				'exampleValue' => 'https://www.drive2.ru/experience/suzuki/g4895/'
			),
		)
	);

	private $title;

	public function collectData(){
		$url = $this->getInput('url');
		$validUrl = '/^https:\/\/www.drive2.ru\/experience/';
		if (!preg_match($validUrl, $url)) {
			returnServerError('Invalid url');
		}
		$html = getSimpleHTMLDOM($this->getInput('url'));
		$this->title = $html->find('title', 0)->innertext;
		$articles = $html->find('div.js-entity');
		foreach ($articles as $article) {
			$item = array();
			$item['title'] = $article->find('a.c-link--text', 0)->plaintext;
			$item['uri'] = self::URI . $article->find('a.c-link--text', 0)->href;
			$item['content'] = 
				str_replace(
					'<button class="c-post-preview__more r-button-unstyled c-link c-link--text" '.
					'data-action="post.show" data-ym-target="post_read">Читать дальше</button>',
					'', $article->find('div.c-post-preview__lead', 0)) .
					'<br><a href="' . $item['uri'] . '">Читать далее</a>';
			$item['author'] = $article->find('a.c-username--wrap', 0)->plaintext;
			$item['enclosures'][] = $article->find('img', 1)->src;
			$this->items[] = $item;
		}
	}

	public function getName() {
		return $this->title ?: parent::getName();
	}

	public function getIcon() {
		return 'https://www.drive2.ru/favicon.ico';
	}
}
