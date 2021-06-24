<?php
class PicukiBridge extends BridgeAbstract
{

	const MAINTAINER = 'marcus-at-localhost';
	const NAME = 'Picuki Bridge';
	const URI = 'https://www.picuki.com/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Returns Picuki posts by users and by hashtag';

	const PARAMETERS = [
		'Username' => [
			'u' => [
				'name' => 'username',
				'required' => true,
			],
		],
		'Hashtag' => [
			'h' => [
				'name' => 'hashtag',
				'required' => true,
			],
		]
	];

	public function getURI()
	{
		if (!is_null($this->getInput('u'))) {
			return trim(self::URI, '/') . '/profile/' . $this->getInput('u');
		}

		if (!is_null($this->getInput('h'))) {
			return trim(self::URI, '/') . '/tag/' . $this->getInput('h');
		}

		return parent::getURI();
	}

	public function collectData()
	{
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request Picuki.');

		foreach ($html->find('.box-photos .box-photo') as $element) {

				// check if item is an ad.
				if (in_array('adv', explode(' ', $element->class))) {
					continue;
				}

				$item = [];

				$date = date_create();
				$relative_date = str_replace(' ago', '', $element->find('.time', 0)->plaintext);
				date_sub($date, date_interval_create_from_date_string($relative_date));
				$item['timestamp'] = date_format($date, 'r');


				$item['uri'] = self::URI . $element->find('a', 0)->href;

				$item['title'] = $element->find('.photo-description', 0)->plaintext;

				$is_video = (bool) $element->find('.video-icon', 0);
				$item['content'] = ($is_video) ? '(video) ' : '';
				$item['content'] .= str_replace(
					'src="',
					'src="' . trim(self::URI, '/'),
					$element->find('.photo', 0)->outertext
				);

				$item['enclosures'] = [
					// just add `.jpg` extension to get the correct mime type. All Instagram posts are JPG
 					trim(self::URI, '/') . $element->find('.post-image', 0)->src . '.jpg'
				];

				$item['thumbnail'] = trim(self::URI, '/') . $element->find('.post-image', 0)->src;

				$this->items[] = $item;
		}
	}


	public function getName()
	{
		if (!is_null($this->getInput('u'))) {
			return $this->getInput('u') . ' - Picuki Bridge';
		}

		return parent::getName();
	}

}
