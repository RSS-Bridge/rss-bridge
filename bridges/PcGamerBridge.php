<?php
class PcGamerBridge extends BridgeAbstract
{
	const NAME = 'PC Gamer';
	const URI = 'https://www.pcgamer.com/archive/';
	const DESCRIPTION = 'PC Gamer Most Read Stories';
	const CACHE_TIMEOUT = 3600;
	const MAINTAINER = 'IceWreck, mdemoss';

	public function collectData()
	{
		$html = getSimpleHTMLDOMCached($this->getURI(), 300);
		$stories = $html->find('ul.basic-list li.day-article');
		$i = 0;
		// Find induvidual stories in the archive page
		foreach ($stories as $element) {
			if($i == 15) break;
			$item['uri'] = $element->find('a', 0)->href;
			// error_log(print_r($item['uri'], TRUE));
			$articleHtml = getSimpleHTMLDOMCached($item['uri']);
			$item['title'] = $element->find('a', 0)->plaintext;
			$item['timestamp'] = strtotime($articleHtml->find('meta[name=pub_date]', 0)->content);
			$item['author'] = $articleHtml->find('span.by-author a', 0)->plaintext;

			// Get the article content
			$articleContents = $articleHtml->find('#article-body', 0);

			/*
				By default the img src has a link to an error image and then the actual image
				is added in by JS. So we replace the error image with the actual full size image
				whoose link is in one of the attributes of the img tag
			*/
			foreach($articleContents->find('img') as $img) {
				$imgsrc = $img->getAttribute('data-original-mos');
				// error_log($imgsrc);
				$img->src = $imgsrc;
			}

			$item['content'] = $articleContents;
			$this->items[] = $item;
			$i++;
		}
	}
}
