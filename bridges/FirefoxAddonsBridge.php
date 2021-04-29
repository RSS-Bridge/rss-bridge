<?php
class FirefoxAddonsBridge extends BridgeAbstract {
	const NAME = 'Firefox Add-ons Bridge';
	const URI = 'https://addons.mozilla.org/';
	const DESCRIPTION = 'Returns version history for a Firefox Add-on.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'id' => array(
				'name' => 'Add-on ID',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'save-to-the-wayback-machine',
			)
		)
	);

	const CACHE_TIMEOUT = 3600;

	private $feedName = '';
	private $releaseDateRegex = '/Released ([\w, ]+) - ([\w. ]+)/';
	private $xpiFileRegex = '/([A-Za-z0-9_.-]+)\.xpi$/';
	private $outgoingRegex = '/https:\/\/outgoing.prod.mozaws.net\/v1\/(?:[A-z0-9]+)\//';

	private $urlRegex = '/addons\.mozilla\.org\/(?:[\w-]+\/)?firefox\/addon\/([\w-]+)/';

	public function detectParameters($url) {
		$params = array();

		if(preg_match($this->urlRegex, $url, $matches)) {
			$params['id'] = $matches[1];
			return $params;
		}

		return null;
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$this->feedName = $html->find('h1[class="AddonTitle"] > a', 0)->innertext;
		$author = $html->find('span.AddonTitle-author > a', 0)->plaintext;

		foreach ($html->find('div.AddonVersionCard-content') as $div) {
			$item = array();

			$item['title'] = $div->find('h2.AddonVersionCard-version', 0)->plaintext;
			$item['uid'] = $item['title'];
			$item['uri'] = $this->getURI();
			$item['author'] = $author;

			if (preg_match($this->releaseDateRegex, $div->find('div.AddonVersionCard-fileInfo', 0)->plaintext, $match)) {
				$item['timestamp'] = $match[1];
				$size = $match[2];
			}

			$compatibility = $div->find('div.AddonVersionCard-compatibility', 0)->plaintext;
			$license = $div->find('p.AddonVersionCard-license', 0)->innertext;
			$downloadlink = $div->find('a.InstallButtonWrapper-download-link', 0)->href;
			$releaseNotes = $this->removeOutgoinglink($div->find('div.AddonVersionCard-releaseNotes', 0));

			if (preg_match($this->xpiFileRegex, $downloadlink, $match)) {
				$xpiFilename = $match[0];
			}

			$item['content'] = <<<EOD
<strong>Release Notes</strong>
<p>{$releaseNotes}</p>
<strong>Compatibility</strong>
<p>{$compatibility}</p>
<strong>License</strong>
<p>{$license}</p>
<strong>Download</strong>
<p><a href="{$downloadlink}">{$xpiFilename}</a> ($size)</p>
EOD;

			$this->items[] = $item;
		}
	}

	public function getURI() {
		if (!is_null($this->getInput('id'))) {
			return self::URI . 'en-US/firefox/addon/' . $this->getInput('id') . '/versions/';
		}

		return parent::getURI();
	}

	public function getName() {
		if (!empty($this->feedName)) {
			return $this->feedName . ' - Firefox Add-on';
		}

		return parent::getName();
	}

	private function removeOutgoinglink($html) {
		foreach ($html->find('a') as $a) {
			$a->href = urldecode(preg_replace($this->outgoingRegex, '', $a->href));
		}

		return $html->innertext;
	}
}
