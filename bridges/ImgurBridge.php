<?php
class ImgurBridge extends BridgeAbstract {
	const NAME = 'Imgur Bridge via API';
	const URI = 'https://imgur.com/';
	const DESCRIPTION = 'get imgur stuff with your own API key';
	const MAINTAINER = '@AdamRGrey';
	const PARAMETERS = array(
		'User' => array(
			'u' => array(
				'name' => 'Username',
				'exampleValue' => 'Sarah',
				'required' => true
			),
			'clientId' => array(
				'name' => '(your) Client ID',
				'required' => true
			)
		),
		'Tag' => array(
			't' => array(
				'name' => 'tag name',
				'exampleValue' => 'movies_and_tv',
				'required' => true
			),
			'clientId' => array(
				'name' => '(your) Client ID',
				'required' => true
			)),
		'Gallery' => array(
			'section' => array(
				'name' => 'type',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Hot' => 'hot',
					'Top' => 'top',
					'User' => 'user'
				),
				'defaultValue' => 'hot'
			),
			'sort' => array(
				'name' => 'type',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Viral' => 'viral',
					'Top' => 'top',
					'Time' => 'time',
					'Rising' => 'rising'
				),
				'defaultValue' => 'viral'
			),
			'window' => array(
				'name' => 'type',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Day' => 'day',
					'Week' => 'week',
					'Month' => 'month',
					'Year' => 'year',
					'All' => 'all'
				),
				'defaultValue' => 'day'
			),
			'clientId' => array(
				'name' => '(your) Client ID',
				'required' => true
			)
		)
	);

	public function collectData() {

		$url = 'https://api.imgur.com/3/';
		
		switch($this->queriedContext) {

			case 'User':
				$url .= 'account/'
					. $this->getInput('u')
					. '/submissions/0/newest';

				$response = $this->simpleGetFromJson($url);

				$this->itemizeData($response->data);

				break;

			case 'Tag':
				$url .= 'gallery/t/' . $this->getInput('t');

				$response = $this->simpleGetFromJson($url);

				$this->itemizeData($response->data->items);

				break;

			case 'Gallery':

				$url .= 'gallery/'
					. $this->getInput('section')
					. '/'
					. $this->getInput('sort')
					. '/'
					. $this->getInput('window');

				$response = $this->simpleGetFromJson($url);

				$this->itemizeData($response->data);

				break;

			default:
				returnClientError(
					'Unknown context: "'
					. $this->queriedContext 
					. '"!'
				);

		}

	}

	private function itemizeData($data){

		foreach ($data as $album) {
			$item = array();

			$item['uri'] = $album->link;
			$item['title'] = $album->title;
			$item['timestamp'] = $album->datetime;
			$item['author'] = $album->account_url;
			$item['content'] = '';
			$item['categories'] = array();

			foreach ($album->tags as $tag) {
				$item['categories'][] = $tag->name;
			}

			if(property_exists($album, 'images')) {
				foreach ($album->images as $image) {
					$item['content'] .= $this->albumImage2Html($image);
				}
			}elseif(property_exists($album, 'link')) {
				//the album is only 1 image
				$item['content'] .= $this->albumImage2Html($album);
			}

			$this->items[] = $item;
		}
	}

	private function albumImage2Html($img){
		$txt = '';
		if(substr($img->type, 0, 6) !== 'image/') {
			$txt .= '<video src="'
				. $img->link 
				. '" controls></video><br />';
		}else{
			$txt .= '<img src="' . $img->link . '" /><br />';
		}
		if(null !== $img->description && trim($img->description) !== '') {
			$desc = htmlentities($img->description);
			$desc = str_replace('\n', '<br />', $desc);
			$txt .= $desc . '<br />';
		}
		return $txt;
	}

	private function simpleGetFromJson($url){
		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'header' => ['Authorization: Client-ID '
					. $this->getInput('clientId')
				]
			]
		]);
		$result = file_get_contents($url, false, $context);
		return json_decode($result, false);
	}
}
