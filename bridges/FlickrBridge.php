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
		),
	);

	public function collectData(){
		switch($this->queriedContext) {
		case 'Explore':
			$key = 'photos';
			$html = getSimpleHTMLDOM(self::URI . 'explore')
				or returnServerError('Could not request Flickr.');
			break;
		case 'By keyword':
			$key = 'photos';
			$html = getSimpleHTMLDOM(self::URI . 'search/?q=' . urlencode($this->getInput('q')) . '&s=rec')
				or returnServerError('No results for this query.');
			break;
		case 'By username':
			$key = 'photoPageList';
			$html = getSimpleHTMLDOM(self::URI . 'photos/' . urlencode($this->getInput('u')))
				or returnServerError('Requested username can\'t be found.');
			break;
		default:
			returnClientError('Invalid context: ' . $this->queriedContext);
		}

		// Find SCRIPT containing JSON data
		$model = $html->find('.modelExport', 0);
		$model_text = $model->innertext;

		// Find start and end of JSON data
		$start = strpos($model_text, 'modelExport:') + strlen('modelExport:');
		$end = strpos($model_text, 'auth:') - strlen('auth:');

		// Dissect JSON data and remove trailing comma
		$model_text = trim(substr($model_text, $start, $end - $start));
		$model_text = substr($model_text, 0, strlen($model_text) - 1);

		$model_json = json_decode($model_text, true);

		foreach($html->find('.photo-list-photo-view') as $element) {
			// Get the styles
			$style = explode(';', $element->style);

			// Get the background-image style
			$backgroundImage = explode(':', end($style));

			// URI type : url(//cX.staticflickr.com/X/XXXXX/XXXXXXXXX.jpg)
			$imageURI = trim(str_replace(['url(', ')'], '', end($backgroundImage)));

			// Get the image ID
			$imageURIs = explode('_', basename($imageURI));
			$imageID = reset($imageURIs);

			// Use JSON data to build items
			foreach(reset($model_json)[0][$key]['_data'] as $element) {
				if($element['id'] === $imageID) {
					$item = array();

					/* Author name depends on scope. On a keyword search the
					 * author is part of the picture data. On a username search
					 * the author is part of the owner data.
					 */
					if(array_key_exists('username', $element)) {
						$item['author'] = $element['username'];
					} elseif (array_key_exists('owner', reset($model_json)[0])) {
						$item['author'] = reset($model_json)[0]['owner']['username'];
					}

					$item['title'] = (array_key_exists('title', $element) ? $element['title'] : 'Untitled');
					$item['uri'] = self::URI . 'photo.gne?id=' . $imageID;

					$description = (array_key_exists('description', $element) ? $element['description'] : '');

					$item['content'] = '<a href="'
					. $item['uri']
					. '"><img src="'
					. $imageURI
					. '" /></a><br><p>'
					. $description
					. '</p>';

					$this->items[] = $item;

					break;
				}
			}
		}
	}
}
