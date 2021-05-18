<?php
class Drive2ruBridge extends BridgeAbstract {

	const MAINTAINER = 'dotter-ak';
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
			'full_articles' => array(
				'name' => 'Загружать в ленту полный текст',
				'type' => 'checkbox',
			)
		)
	);

	private $title;

	public function collectData(){
		$url = $this->getInput('url');
		$validUrl = '/^https:\/\/www.drive2.ru\/experience/';
		if (!preg_match($validUrl, $url)) returnServerError('Invalid url');
		$html = getSimpleHTMLDOM($this->getInput('url'));
		$this->title = $html->find('title', 0)->innertext;
		$articles = $html->find('div.js-entity');
		foreach ($articles as $article) {
			$item = array();
			$item['title'] = $article->find('a.c-link--text', 0)->plaintext;
			$item['uri'] = urljoin(self::URI, $article->find('a.c-link--text', 0)->href);
			if($this->getInput('full_articles')) {
				$content = getSimpleHTMLDOM($item['uri'])->find('div.c-post__body', 0);
			foreach($content->find('div, span') as $ds)
					foreach ($ds->getAllAttributes() as $attr => $val)
						$ds->removeAttribute($attr);
				foreach ($content->find('script') as $node)
					$node->outertext = '';
				foreach ($content->find('iframe') as $node)
					$node->outertext = '<a href="' . $node->src . '">' . $node->src . '</a>';
				$item['content'] = $content->innertext;
			} else {
				$content = $article->find('div.c-post-preview__lead', 0);
				if (!is_null($content))
					$item['content'] = preg_replace('!\s+!', ' ', str_replace('Читать дальше', '', $content->plaintext)) .
						'<br><a href="' . $item['uri'] . '">Читать далее</a>';
				else $item['content'] = '';
			}
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
