<?php
class PicukiBridge extends BridgeAbstract
{
	const MAINTAINER = 'marcus-at-localhost';
	const NAME = 'Picuki Bridge';
	const URI = 'https://www.picuki.com/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns Picuki posts by user and by hashtag';

	const PARAMETERS = array(
		'Username' => array(
			'u' => array(
				'name' => 'username',
				'exampleValue' => 'aesoprockwins',
				'required' => true,
			),
		),
		'Hashtag' => array(
			'h' => array(
				'name' => 'hashtag',
				'exampleValue' => 'beautifulday',
				'required' => true,
			),
		)
	);

	public function getURI()
	{
		if (!is_null($this->getInput('u'))) {
			return urljoin(self::URI, '/profile/' . $this->getInput('u'));
		}

		if (!is_null($this->getInput('h'))) {
			return urljoin(self::URI, '/tag/' . trim($this->getInput('h'), '#'));
		}

		return parent::getURI();
	}

	public function collectData()
	{
		$html = getSimpleHTMLDOM($this->getURI());

		foreach ($html->find('.box-photos .box-photo') as $element) {

				// check if item is an ad.
			if (in_array('adv', explode(' ', $element->class))) {
				continue;
			}

			$item = array();

			$date = date_create();
			$relative_date = str_replace(' ago', '', $element->find('.time', 0)->plaintext);
			date_sub($date, date_interval_create_from_date_string($relative_date));
			$item['timestamp'] = date_format($date, 'r');

			$item['uri'] = urljoin(self::URI, $element->find('a', 0)->href);

			$description = trim($element->find('.photo-description', 0)->plaintext);
			$item['title'] = mb_substr($description, 0, 60);

			$is_video = (bool) $element->find('.video-icon', 0);
			$item['content'] = ($is_video) ? '(video) ' : '';
			$item['content'] .= $description;

			$postImage = $element->find('.post-image', 0)->src;
			$item['enclosures'] = [
				urljoin(self::URI, $postImage)
			];
			$item['thumbnail'] = urljoin(self::URI, $postImage);
			$this->items[] = $item;
		}
	}

	public function getName()
	{
		if (!is_null($this->getInput('u'))) {
			return $this->getInput('u') . ' - Picuki Bridge';
		}

		if (!is_null($this->getInput('h'))) {
			return $this->getInput('h') . ' - Picuki Bridge';
		}

		return parent::getName();
	}
}
