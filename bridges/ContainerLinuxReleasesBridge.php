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

			$content = $release['release_notes'];
			$content .= <<<EOT

Major Software:
* Kernel: <tt>{$release['major_software']['kernel'][0]}</tt>
* Docker: <tt>{$release['major_software']['docker'][0]}</tt>
* etcd: <tt>{$release['major_software']['etcd'][0]}</tt>
EOT;
			$item['timestamp'] = strtotime($release['release_date']);

			// Based on https://gist.github.com/jbroadway/2836900
			$regex = '/\[([^\[]+)\]\(([^\)]+)\)/';
			$replacement = '<a href=\'\2\'>\1</a>';

			$item['content'] = preg_replace($regex, $replacement, nl2br($content));

			$regex = '/\n[\*|\-](.*)/';
			$item['content'] = preg_replace_callback ($regex, function($regs) {
				$item = $regs[1];
				return sprintf ("\n<ul>\n\t<li>%s</li>\n</ul>", trim ($item));
			}, $item['content']);

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
