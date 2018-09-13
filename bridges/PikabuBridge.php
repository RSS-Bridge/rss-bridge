<?php
class PikabuBridge extends BridgeAbstract {

	const NAME = 'Пикабу';
	const URI = 'https://pikabu.ru';
	const DESCRIPTION = 'Выводит посты по тегу';
	const MAINTAINER = 'em92';

	const PARAMETERS = array(
		'По тегу' => array(
			'tag' => array(
				'name' => 'Тег',
				'exampleValue' => 'it',
				'required' => true
			),
			'filter' => array(
				'name' => 'Фильтр',
				'type' => 'list',
				'values' => array(
					'Горячее' => 'hot',
					'Свежее' => 'new',
				),
				'defaultValue' => 'hot'
			)
		)
	);

	public function getURI() {
		if ($this->getInput('tag')) {
			return self::URI . '/tag/' . rawurlencode($this->getInput('tag')) . '/' . rawurlencode($this->getInput('filter'));
		} else {
			return parent::getURI();
		}
	}

	public function getIcon() {
		return 'https://cs.pikabu.ru/assets/favicon.ico';
	}

	public function getName() {
		if (is_string($this->getInput('tag'))) {
			return $this->getInput('tag') . ' - ' . parent::getName();
		} else {
			return parent::getName();
		}
	}

	public function collectData(){
		$link = $this->getURI();

		$text_html = getContents($link) or returnServerError('Could not fetch ' . $link);
		$text_html = iconv('windows-1251', 'utf-8', $text_html);
		$html = str_get_html($text_html);

		foreach($html->find('article.story') as $post) {
			$time = $post->find('time.story__datetime', 0);
			if (is_null($time)) continue;

			$el_to_remove_selectors = array(
				'.story__read-more',
				'svg.story-image__stretch',
			);

			foreach($el_to_remove_selectors as $el_to_remove_selector) {
				foreach($post->find($el_to_remove_selector) as $el) {
					$el->outertext = '';
				}
			}

			foreach($post->find('img') as $img) {
				$src = $img->getAttribute('src');
				if (!$src) {
					$src = $img->getAttribute('data-src');
					if (!$src) {
						continue;
					}
				}
				$img->outertext = '<img src="'.$src.'">';
			}

			$categories = array();
			foreach($post->find('.tags__tag') as $tag) {
				if ($tag->getAttribute('data-tag')) {
					$categories[] = $tag->innertext;
				}
			}

			$title = $post->find('.story__title-link', 0);

			$item = array();
			$item['categories'] = $categories;
			$item['author'] = $post->find('.user__nick', 0)->innertext;
			$item['title'] = $title->plaintext;
			$item['content'] = strip_tags(backgroundToImg($post->find('.story__content-inner', 0)->innertext), '<br><p><img>');
			$item['uri'] = $title->href;
			$item['timestamp'] = strtotime($time->getAttribute('datetime'));
			$this->items[] = $item;
		}
	}
}
