<?php
class SeznamZpravyBridge extends BridgeAbstract {
	const NAME = 'Seznam Zprávy Bridge';
	const URI = 'https://seznamzpravy.cz';
	const DESCRIPTION = 'Returns newest stories from Seznam Zprávy';
	const MAINTAINER = 'thezeroalpha';
	const PARAMETERS = array(
		'By Author' => array(
			'author' => array(
				'name' => 'Author String',
				'type' => 'text',
				'required' => true,
				'title' => 'The dash-separated author string, as shown in the URL bar.',
				'pattern' => '[a-z]+-[a-z]+-[0-9]+',
				'exampleValue' => 'janek-rubes-506'
			),
		)
	);

	private $feedName;

	public function getName() {
		if (isset($this->feedName)) {
			return $this->feedName;
		}
		return parent::getName();
	}

	public function collectData() {
		$ONE_DAY = 86500;
		switch($this->queriedContext) {
		case 'By Author':
			$url = 'https://www.seznamzpravy.cz/autor/';
			$selectors = array(
				'breadcrumbs' => 'div[data-dot=ogm-breadcrumb-navigation]',
				'article_list' => 'ul.ogm-document-timeline-page.atm-list-ul li article[data-dot=mol-timeline-item]',
				'article_title' => 'a[data-dot=mol-article-card-title]',
				'article_dm' => 'span.mol-formatted-date__date',
				'article_time' => 'span.mol-formatted-date__time',
				'article_content' => 'div[data-dot=ogm-article-content]'
			);

			$html = getSimpleHTMLDOMCached($url . $this->getInput('author'), $ONE_DAY);
			$main_breadcrumbs = $html->find($selectors['breadcrumbs'], 0);
			$author = $main_breadcrumbs->last_child()->plaintext
				or returnServerError('Could not get author on: ' . $this->getURI());
			$this->feedName = $author . ' - Seznam Zprávy';

			$articles = $html->find($selectors['article_list'])
				or returnServerError('Could not find articles on: ' . $this->getURI());

			foreach ($articles as $article) {
				$title_link = $article->find($selectors['article_title'], 0)
					or returnServerError('Could not find title on: ' . $this->getURI());

				$article_url = $title_link->href;
				$article_content_html = getSimpleHTMLDOMCached($article_url, $ONE_DAY);
				$content_e = $article_content_html->find($selectors['article_content'], 0);
				$content_text = $content_e->innertext
					or returnServerError('Could not get article content for: ' . $article_url);

				$breadcrumbs_e = $article_content_html->find($selectors['breadcrumbs'], 0);
				$breadcrumbs = $breadcrumbs_e->children();
				$num_breadcrumbs = count($breadcrumbs);
				$categories = array();
				foreach ($breadcrumbs as $cat) {
					if (--$num_breadcrumbs <= 0) {
						break;
					}
					$categories[] = trim($cat->plaintext);
				}

				$article_dm_e = $article->find($selectors['article_dm'], 0);
				$article_dm_text = $article_dm_e->plaintext;
				$article_dmy = preg_replace('/[^0-9\.]/', '', $article_dm_text) . date('Y');
				$article_time = $article->find($selectors['article_time'], 0)->plaintext;
				$item = array(
					'title' => $title_link->plaintext,
					'uri' => $title_link->href,
					'timestamp' => strtotime($article_dmy . ' ' . $article_time),
					'author' => $author,
					'content' => $content_text,
					'categories' => $categories
				);
				$this->items[] = $item;
			}
			break;
		}
		$this->items[] = $item;
	}
}
