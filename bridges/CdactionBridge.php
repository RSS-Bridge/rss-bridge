<?php

class CdactionBridge extends BridgeAbstract {
	const NAME = 'CD-ACTION bridge';
	const URI = 'https://cdaction.pl/newsy';
	const DESCRIPTION = 'Fetches the latest news.';
	const MAINTAINER = 'tomaszkane';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI());

		$newsJson = $html->find('script#__NEXT_DATA__', 0)->innertext;
		if (!$newsJson = json_decode($newsJson)) {
			return;
		}

		foreach ($newsJson->props->pageProps->dehydratedState->queries[1]->state->data->results as $news) {
			$item = array();
			$item['uri'] = $this->getURI() . '/' . $news->slug;
			$item['title'] = $news->title;
			$item['timestamp'] = $news->publishedAt;
			$item['author'] = $news->editor->fullName;
			$item['content'] = $news->lead;
			$item['enclosures'][] = $news->bannerUrl;
			$item['categories'] = array_column($news->tags, 'name');
			$item['uid'] = $news->id;

			$this->items[] = $item;
		}
	}
}
