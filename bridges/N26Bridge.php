<?php

class N26Bridge extends BridgeAbstract
{
	const MAINTAINER = 'quentinus95';
	const NAME = 'N26 Blog';
	const URI = 'https://n26.com';
	const CACHE_TIMEOUT = 1800;
	const DESCRIPTION = 'Returns recent blog posts from N26.';

	public function getIcon()
    {
		return 'https://n26.com/favicon.ico';
	}

	public function collectData()
    {
		$html = getSimpleHTMLDOM(self::URI . '/en-fr/blog-archive')
			or returnServerError('Error while downloading the website content');

		foreach($html->find('div.ga') as $article) {
			$item = [];

            $item['uri'] = self::URI . $article->find('h2 a', 0)->href;
            $item['title'] = $article->find('h2 a', 0)->plaintext;

            $fullArticle = getSimpleHTMLDOM($item['uri']);

            $item['timestamp'] = strtotime($fullArticle->find('.fk.fl.de.ch.fm.by'));
			$item['content'] = $fullArticle->find('main article', 0)->innertext;

			$this->items[] = $item;
		}
	}
}
