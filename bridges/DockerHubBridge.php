<?php
class DockerHubBridge extends BridgeAbstract {
	const NAME = 'Docker Hub Bridge';
	const URI = 'https://hub.docker.com';
	const DESCRIPTION = 'Returns new images for a container';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'user' => array(
				'name' => 'User',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'rssbridge',
			),
			'repo' => array(
				'name' => 'Repository',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'rss-bridge',
			)
		)
	);

	const CACHE_TIMEOUT = 3600; // 1 hour

	private $apiURL = 'https://hub.docker.com/v2/repositories/';

	public function collectData() {
		$json = getContents($this->getApiUrl())
			or returnServerError('Could not request: ' . $this->getURI());

		$data = json_decode($json, false);

		foreach ($data->results as $result) {
			$item = array();

			$lastPushed = date('Y-m-d H:i:s', strtotime($result->tag_last_pushed));

			$item['title'] = $result->name;
			$item['uid'] = $result->id;
			$item['uri'] = $this->getTagUrl($result->name);
			$item['author'] = $result->last_updater_username;
			$item['timestamp'] = $result->tag_last_pushed;
			$item['content'] = <<<EOD
<Strong>Tag</strong><br>
<p>{$result->name}</p>
<Strong>Last pushed</strong><br>
<p>{$lastPushed}</p>
<Strong>Images</strong><br>
{$this->getImages($result)}
EOD;

			$this->items[] = $item;
		}

	}

	public function getURI() {
		if ($this->getInput('user')) {
			return self::URI . '/r/' . $this->getRepo();
		}

		return parent::getURI();
	}

	public function getName() {
		if ($this->getInput('user')) {
			return $this->getRepo() . ' - Docker Hub';
		}

		return parent::getName();
	}

	private function getRepo() {
		return $this->getInput('user') . '/' . $this->getInput('repo');
	}

	private function getApiUrl() {
		return $this->apiURL . $this->getRepo() . '/tags/?page_size=25&page=1';
	}

	private function getLayerUrl($name, $digest) {
		return self::URI . '/layers/' . $this->getRepo() . '/' . $name . '/images/' . $digest;
	}

	private function getTagUrl($name) {
		return self::URI . '/r/' . $this->getRepo() . '/tags?name=' . $name;
	}

	private function getImages($result) {
		$html = <<<EOD
<table style="width:300px;"><thead><tr><th>Digest</th><th>OS/architecture</th></tr></thead></tbody>
EOD;

		foreach ($result->images as $image) {
			$layersUrl = $this->getLayerUrl($result->name, $image->digest);
			$id = $this->getShortDigestId($image->digest);

			$html .= <<<EOD
			<tr>
				<td><a href="{$layersUrl}">{$id}</a></td>
				<td>{$image->os}/{$image->architecture}</td>
			</tr>
EOD;
		}

		return $html . '</tbody></table>';
	}

	private function getShortDigestId($digest) {
		$parts = explode(':', $digest);
		return substr($parts[1], 0, 12);
	}
}
