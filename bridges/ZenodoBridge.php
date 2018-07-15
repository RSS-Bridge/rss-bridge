<?php
class ZenodoBridge extends BridgeAbstract {
	const MAINTAINER = 'theradialactive';
	const NAME = 'Zenodo';
	const URI = 'https://zenodo.org';
	const CACHE_TIMEOUT = 10;
	const DESCRIPTION = 'Returns the newest content of Zenodo';

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('zenodo.org not reachable.');

		foreach($html->find('div.record-elem') as $element) {
			$item = array();
			$item['uri'] = self::URI . $element->find('h4', 0)->find('a', 0)->href;
			$item['title'] = trim(
				htmlspecialchars_decode($element->find('h4', 0)->find('a', 0)->innertext,
				ENT_QUOTES
				)
			);
			foreach($element->find('p', 0)->find('span') as $authors) {
				$item['author'] = $item['author'] . $authors . '; ';
			}
			$content = $element->find('p.hidden-xs', 0)->find('a', 0)->innertext . '<br>';
			$type = '<br>Type: ' . $element->find('span.label-default', 0)->innertext;

			$raw_date = $element->find('small.text-muted', 0)->innertext;
			$clean_date = date_parse(str_replace('Uploaded on ', '', $raw_date));

			$content = $content . date_parse($clean_date);

			$item['timestamp'] = mktime(
					$clean_date['hour'],
					$clean_date['minute'],
					$clean_date['second'],
					$clean_date['month'],
					$clean_date['day'],
					$clean_date['year']
			);

			$access = '';
			if ($element->find('span.label-success', 0)->innertext) {
				$access = 'Open Access';
			} elseif ($element->find('span.label-warning', 0)->innertext) {
				$access = 'Embargoed Access';
			} else {
				$access = $element->find('span.label-error', 0)->innertext;
			}
			$access = '<br>Access: ' . $access;
			$publication = '<br>Publication Date: ' . $element->find('span.label-info', 0)->innertext;
			$item['content'] = $content . $type . $access . $publication;
			$this->items[] = $item;
		}
	}
}
