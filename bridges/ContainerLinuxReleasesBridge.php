<?php
class ContainerLinuxReleasesBridge extends BridgeAbstract {

	const MAINTAINER = 'capt3m0';
	const NAME = 'Core OS Container Linux Releases Bridge';
	const URI = 'https://coreos.com/releases/';
	const DESCRIPTION = 'Returns the releases notes for Container Linux';

	const STABLE = 'stable';
	const BETA = 'beta';
	const ALPHA = 'alpha';

	const PARAMETERS = [
		[
			'channel' => [
				'name' => 'Release Channel',
				'type' => 'list',
				'required' => true,
				'defaultValue' => self::STABLE,
				'values' => [
					'Stable' => self::STABLE,
					'Beta' => self::BETA,
					'Alpha' => self::ALPHA,
				],
			]
		]
	];

	public function getReleaseFeed($jsonUrl) {
		$json = getContents($jsonUrl)
			or returnServerError('Could not request Core OS Website.');
		return json_decode($json, true);
	}

	public function collectData() {
		$data = $this->getReleaseFeed($this->getJsonUri());

		foreach ($data as $releaseVersion => $release) {
			$item = [];

			$item['uri'] = "https://coreos.com/releases/#$releaseVersion";
			$item['title'] = $releaseVersion;
			$item['content'] = nl2br($release['release_notes']);

			$item['content'] .= <<<EOT
<br/>
Major Software:
<br/>
- Kernel: {$release['major_software']['kernel'][0]}<br/>
- Docker: {$release['major_software']['docker'][0]}<br/>
- etcd: {$release['major_software']['etcd'][0]}<br/>
EOT;
			$item['timestamp'] = strtotime($release['release_date']);

			$this->items[] = $item;
		}
	}

	private function getJsonUri() {
		$channel = $this->getInput('channel');

		return "https://coreos.com/releases/releases-$channel.json";
	}

	public function getURI() {
		return self::URI;
	}

	public function getName(){
		if(!is_null($this->getInput('channel'))) {
			return 'Container Linux Releases: ' . $this->getInput('channel') . ' Channel';
		}

		return parent::getName();
	}
}
