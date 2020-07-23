<?php
class DribbbleBridge extends BridgeAbstract {

	const MAINTAINER = 'quentinus95';
	const NAME = 'Dribbble popular shots';
	const URI = 'https://dribbble.com';
	const CACHE_TIMEOUT = 1800;
	const DESCRIPTION = 'Returns the newest popular shots from Dribbble.';

	public function getIcon() {
		return 'https://cdn.dribbble.com/assets/
favicon-63b2904a073c89b52b19aa08cebc16a154bcf83fee8ecc6439968b1e6db569c7.ico';
	}

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI . '/shots')
			or returnServerError('Error while downloading the website content');

		$json = $this->loadEmbeddedJsonData($html);

		foreach($html->find('li[id^="screenshot-"]') as $shot) {
			$item = array();

			$additional_data = $this->findJsonForShot($shot, $json);
			if ($additional_data === null) {
				$item['uri'] = self::URI . $shot->find('a', 0)->href;
				$item['title'] = $shot->find('.shot-title', 0)->plaintext;
			} else {
				$item['timestamp'] = strtotime($additional_data['published_at']);
				$item['uri'] = self::URI . $additional_data['path'];
				$item['title'] = $additional_data['title'];
			}

			$item['author'] = trim($shot->find('.user-information .display-name', 0)->plaintext);

			$description = $shot->find('.comment', 0);
			$item['content'] = $description === null ? '' : $description->plaintext;

			$preview_path = $shot->find('picture source', 0)->attr['srcset'];
			$item['content'] .= $this->getImageTag($preview_path, $item['title']);
			$item['enclosures'] = array($this->getFullSizeImagePath($preview_path));

			$this->items[] = $item;
		}
	}

	private function loadEmbeddedJsonData($html){
		$json = array();
		$scripts = $html->find('script');

		foreach($scripts as $script) {
			if(strpos($script->innertext, 'newestShots') !== false) {
				// fix single quotes
				$script->innertext = preg_replace('/\'(.*)\'(,?)$/im', '"\1"\2', $script->innertext);

				// fix JavaScript JSON (why do they not adhere to the standard?)
				$script->innertext = preg_replace('/^(\s*)(\w+):/im', '\1"\2":', $script->innertext);

				// fix relative dates, so they are recognized by strtotime
				$script->innertext = preg_replace('/"about ([0-9]+ hours? ago)"(,?)$/im', '"\1"\2', $script->innertext);

				// find beginning of JSON array
				$start = strpos($script->innertext, '[');

				// find end of JSON array, compensate for missing character!
				$end = strpos($script->innertext, '];') + 1;

				// convert JSON to PHP array
				$json = json_decode(substr($script->innertext, $start, $end - $start), true);
				break;
			}
		}

		return $json;
	}

	private function findJsonForShot($shot, $json){
		foreach($json as $element) {
			if(strpos($shot->getAttribute('id'), (string)$element['id']) !== false) {
				return $element;
			}
		}

		return null;
	}

	private function getImageTag($preview_path, $title){
		return sprintf(
			'<br /> <a href="%s"><img src="%s" alt="%s" /></a>',
			$this->getFullSizeImagePath($preview_path),
			$preview_path,
			$title
		);
	}

	private function getFullSizeImagePath($preview_path){
		return str_replace('_1x', '', $preview_path);
	}
}
