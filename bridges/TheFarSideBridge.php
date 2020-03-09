<?php
class TheFarSideBridge extends BridgeAbstract {
	const NAME = 'The Far Side Bridge';
	const URI = 'https://www.thefarside.com';
	const DESCRIPTION = 'Returns the daily dose';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 3600; // 1 hour

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request: ' . self::URI);

		$item = array();
		$item['uri'] = self::URI . date('/Y/m/d', strtotime($html->find('h3', 0)->innertext));
		$item['title'] = $html->find('h3', 0)->innertext;
		$item['timestamp'] = $html->find('h3', 0)->innertext;

		$item['content'] = '';

		$div = $html->find('div.tfs-content.js-daily-dose', 0);

		foreach($div->find('div.card-body') as $index => $card) {
			$image = $card->find('img', 0);
			$imageUrl = $image->attr['data-src'];

			// To get around the hotlink protection, images are downloaded, encoded as base64 and then added to the html.
			$image = getContents($imageUrl, array('Referer: ' . self::URI))
				or returnServerError('Could not request: ' . $imageUrl);

			// Encode image as base64
			$imageBase64 = base64_encode($image);

			$caption = $card->find('figcaption', 0)->innertext;

			$item['content'] .= <<<EOD
<figure><img title="{$caption}" src="data:image/jpeg;base64,{$imageBase64}"/><figcaption>{$caption}</figcaption></figure><br/>
EOD;
		}

		$this->items[] = $item;
	}
}
