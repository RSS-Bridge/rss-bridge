<?php

/* This is a mashup of FlickrExploreBridge by sebsauvage and FlickrTagBridge
 * by erwang, providing the functionality of both in one.
 */
class FlickrBridge extends BridgeAbstract {

	const MAINTAINER = 'logmanoriginal';
	const NAME = 'Flickr Bridge';
	const URI = 'https://www.flickr.com/';
	const CACHE_TIMEOUT = 21600; // 6 hours
	const DESCRIPTION = 'Returns images from Flickr';

	const PARAMETERS = array(
		'Explore' => array(),
		'By keyword' => array(
			'q' => array(
				'name' => 'Keyword',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert keyword',
				'exampleValue' => 'bird'
			)
		),
		'By username' => array(
			'u' => array(
				'name' => 'Username',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert username (as shown in the address bar)',
				'exampleValue' => 'flickr'
			)
		)
	);

	public function collectData(){

		switch($this->queriedContext) {

		case 'Explore':
			$filter = 'photo-lite-models';
			$html = getSimpleHTMLDOM(self::URI . 'explore')
				or returnServerError('Could not request Flickr.');
			break;

		case 'By keyword':
			$filter = 'photo-lite-models';
			$html = getSimpleHTMLDOM(self::URI . 'search/?q=' . urlencode($this->getInput('q')) . '&s=rec')
				or returnServerError('No results for this query.');
			break;

		case 'By username':
			$filter = 'photo-models';
			$html = getSimpleHTMLDOM(self::URI . 'photos/' . urlencode($this->getInput('u')))
				or returnServerError('Requested username can\'t be found.');
			break;

		default:
			returnClientError('Invalid context: ' . $this->queriedContext);

		}

		$model_json = $this->extractJsonModel($html);
		$photo_models = $this->getPhotoModels($model_json, $filter);

		foreach($photo_models as $model) {

			$item = array();

			/* Author name depends on scope. On a keyword search the
			* author is part of the picture data. On a username search
			* the author is part of the owner data.
			*/
			if(array_key_exists('username', $model)) {
				$item['author'] = $model['username'];
			} elseif (array_key_exists('owner', reset($model_json)[0])) {
				$item['author'] = reset($model_json)[0]['owner']['username'];
			}

			$item['title'] = (array_key_exists('title', $model) ? $model['title'] : 'Untitled');
			$item['uri'] = self::URI . 'photo.gne?id=' . $model['id'];

			$description = (array_key_exists('description', $model) ? $model['description'] : '');

			$item['content'] = '<a href="'
			. $item['uri']
			. '"><img src="'
			. $this->extractContentImage($model)
			. '" style="max-width: 640px; max-height: 480px;"/></a><br><p>'
			. $description
			. '</p>';

			$item['enclosures'] = $this->extractEnclosures($model);

			$this->items[] = $item;

		}

	}

	private function extractJsonModel($html) {

		// Find SCRIPT containing JSON data
		$model = $html->find('.modelExport', 0);
		$model_text = $model->innertext;

		// Find start and end of JSON data
		$start = strpos($model_text, 'modelExport:') + strlen('modelExport:');
		$end = strpos($model_text, 'auth:') - strlen('auth:');

		// Extract JSON data, remove trailing comma
		$model_text = trim(substr($model_text, $start, $end - $start));
		$model_text = substr($model_text, 0, strlen($model_text) - 1);

		return json_decode($model_text, true);

	}

	private function getPhotoModels($json, $filter) {

		// The JSON model contains a "legend" array, where each element contains
		// the path to an element in the "main" object
		$photo_models = array();

		foreach($json['legend'] as $legend) {

			$photo_model = $json['main'];

			foreach($legend as $element) { // Traverse tree
				$photo_model = $photo_model[$element];
			}

			// We are only interested in content
			if($photo_model['_flickrModelRegistry'] === $filter) {
				$photo_models[] = $photo_model;
			}

		}

		return $photo_models;

	}

	private function extractEnclosures($model) {

		$areas = array();

		foreach($model['sizes'] as $size) {
			$areas[$size['width'] * $size['height']] = $size['url'];
		}

		return array($this->fixURL(max($areas)));

	}

	private function extractContentImage($model) {

		$areas = array();
		$limit = 320 * 240;

		foreach($model['sizes'] as $size) {

			$image_area = $size['width'] * $size['height'];

			if($image_area >= $limit) {
				$areas[$image_area] = $size['url'];
			}

		}

		return $this->fixURL(min($areas));

	}

	private function fixURL($url) {

		// For some reason the image URLs don't include the protocol (https)
		if(strpos($url, '//') === 0) {
			$url = 'https:' . $url;
		}

		return $url;

	}

}
