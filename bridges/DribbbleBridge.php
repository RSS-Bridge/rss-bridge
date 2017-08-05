<?php
class DribbbleBridge extends BridgeAbstract {

	const MAINTAINER = 'quentinus95';
	const NAME = 'Dribbble popular shots';
	const URI = 'https://dribbble.com';
	const CACHE_TIMEOUT = 1800;
	const DESCRIPTION = 'Returns the newest popular shots from Dribbble.';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI . '/shots')
			or returnServerError('Error while downloading the website content');

		foreach($html->find('li[id^="screenshot-"]') as $shot) {
			$item = [];
			$item['uri'] = self::URI . $shot->find('a', 0)->href;
			$item['title'] = $shot->find('.dribbble-over strong', 0)->plaintext;
			$item['author'] = trim($shot->find('.attribution-user a', 0)->plaintext);
			$item['content'] = $shot->find('.comment', 0)->plaintext;
			$item['content'] .= $this->getFullSizeImage($shot);

			$this->items[] = $item;
		}
	}

	private function getFullSizeImage($shot){
		$image_preview = $shot->find('picture source', 0)->attr['srcset'];
		$image_path = str_replace('_1x', '', $image_preview);

		return '<br /> <img src="'.$image_path.'" alt="" />';
	}
}
