<?php

class FM4Bridge extends BridgeAbstract
{
	const MAINTAINER = 'joni1993';
	const NAME = 'FM4 Bridge';
	const URI = 'https://fm4.orf.at/tags/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Feed for FM4 articles by tags (authors)';
	const PARAMETERS = array(
		array(
			'tag' => array(
				'name' => 'Tag (author)',
				'title' => 'Tag to retrieve (usually the author)',
				'required' => true,
			),
			'loadcontent' => array(
				'name' => 'Load Full Article Content',
				'title' => 'Retrieve full content of articles (may take longer)',
				'type' => 'checkbox'
			)
		)
	);
	const LIMIT = 10;

	public function collectData()
	{
		$uri = self::URI . $this->getInput('tag');

		$html = getSimpleHTMLDOM($uri)
		or returnServerError('Error while downloading the website content');


		foreach ($html->find('div.listItem') as $article) {
			$item = array();

			$item['uri'] = $article->find('a', 0)->href;
			$item['title'] = $article->find('h2', 0)->plaintext;
			$item['author'] = $article->find('p[class*=keyword]', 0)->plaintext;
			$item["timestamp"] = strtotime($article->find('p[class*=time]', 0)->plaintext);

			if ($this->getInput('loadcontent')) {
				$item['content'] = getSimpleHTMLDOM($item['uri'])->find('div[class=storyText]', 0)->innertext
				or returnServerError('Error while downloading the full article');
			}

			$this->items[] = $item;
		}
	}
}
