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
				'required' => true
			),
			'clientId' => array(
				'name' => '(your) Client ID',
				'required' => true
			)),
		'Most Viral' => array(
			'clientId' => array(
				'name' => '(your) Client ID',
				'required' => true
			)
		)
	);

	public function collectData() {

		$url = "https://api.imgur.com/3/";
		
		switch($this->queriedContext) {

			case 'User':
				$url .= "account/" . $this->getInput('u') . "/submissions/0/newest"; 

				$response = $this->simpleGetFromJson($url);

				$this->itemizeData($response->data);

				break;

			case 'Tag':
				$url .= 'gallery/t/' . $this->getInput('t');

				$response = $this->simpleGetFromJson($url);

				$this->itemizeData($response->data->items);

				break;

			case 'Most Viral': //todo: allow
				$url .= "gallery/hot/viral";

				$response = $this->simpleGetFromJson($url);

				$this->itemizeData($response->data);

				break;

			default:
				returnClientError('Unknown context: "' . $this->queriedContext . '"!');

		}

	}

	private function itemizeData($data){

		foreach ($data as $album) {
			$item = array();

			$item['uri'] = $album->link;
			$item['title'] = $album->title;
			$item['timestamp'] = $album->datetime;
			$item['author'] = $album->account_url;
			$item['content'] = "";
			$item['categories'] = array();

			foreach ($album->tags as $tag) {
				$item['categories'][] = $tag->name;
			}

			if(property_exists($album, "images")){
				foreach ($album->images as $image) {
					$item['content'] .= $this->albumImage2Html($image);
				}
			}else if(property_exists($album, "link")){
				$item['content'] .= $this->albumImage2Html($album); //the album is only 1 image
			}

			$this->items[] = $item;
		}
	}

	private function albumImage2Html($img){
		$txt = "";
		if(substr($img->type, 0, 6) !== "image/"){
			$txt .= "<video src=\"" . $img->link . "\" controls></video><br />";
		}else{
			$txt .= "<img src=\"" . $img->link . "\" /><br />";
		}
		if(null !== $img->description && trim($img->description) !== ''){
			$desc = htmlentities($img->description);
			$desc = str_replace("\n", "<br />", $desc);
			$txt .= $desc . "<br />";
		}
		return $txt;
	}

	/*
	private function sanitizeUser($user) {
		if (filter_var($user, FILTER_VALIDATE_URL)) {

			$urlparts = parse_url($user);

			if($urlparts['host'] !== parse_url(self::URI)['host']) {
				returnClientError('The host you provided is invalid! Received "'
				. $urlparts['host']
				. '", expected "'
				. parse_url(self::URI)['host']
				. '"!');
			}

			if(!array_key_exists('path', $urlparts)
			|| $urlparts['path'] === '/') {
				returnClientError('The URL you provided doesn\'t contain the user name!');
			}

			return explode('/', $urlparts['path'])[2];

		} else {

			// First character cannot be a forward slash
			if(strpos($user, '/') === 0) {
				returnClientError('Remove leading slash "/" from the username!');
			}

			return $user;

		}
	}
	*/

	private function simpleGetFromJson($url){
		$context = stream_context_create([ 
			'http' => [ 
				'method' => 'GET', 
				'header' => ['Authorization: Client-ID ' . $this->getInput("clientId")] 
			]
		]);
		$result = file_get_contents($url, false, $context);
		return json_decode($result, false);
	}
}
