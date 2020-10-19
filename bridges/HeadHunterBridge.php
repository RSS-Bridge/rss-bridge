<?php
class HeadHunterBridge extends FeedExpander {

	const NAME = 'HeadHunter';
	const DESCRIPTION = 'Расширяет ленту путем добавления полного описания вакансии';
	const MAINTAINER = 'em92';

	const PARAMETERS = array(
		array('url' => array(
			'name' => 'Ссылка на ленту',
			'exampleValue' => 'https://ufa.hh.ru/search/vacancy/rss?area=99&clusters=true&enable_snippets=true&text=php',o
			'required' => true,
		)),
	);

	protected function parseItem($newItem) {
		$item = parent::parseItem($newItem);

		$html = getSimpleHTMLDOMCached($item['uri']);

		$vacancy_header = $html->find('.vacancy-salary', 0)->outertext;
		$vacancy_company = $html->find('.vacancy-company-wrapper', 0)->outertext;
		$vacancy_description = $html->find('.vacancy-description', 0);

		$unwanted_element_selectors = array('script', 'style', '.tmpl_hh_head', '.tmpl_hh_slider');
		foreach($unwanted_element_selectors as $unwanted_element_selector) {
			foreach($vacancy_description->find($unwanted_element_selector) as $el) {
				$el->outertext = '';
			}
		}
		$vacancy_description = $vacancy_description->outertext;

		$content = $vacancy_header . $vacancy_company . $vacancy_description;

		$item['content'] = $content;
		return $item;

	}

	public function collectData(){
		// TODO: check if host ends with .hh.ru
		// TODO: check .hh.ru/search/vacancy/rss
		$this->collectExpandableDatas($this->getInput('url'));
	}

}
