<?php
/**
 * AtomFormat - RFC 4287: The Atom Syndication Format
 * https://tools.ietf.org/html/rfc4287
 */

use PHPUnit\Framework\TestCase;

class AtomFormatTest extends TestCase {
	const PATH_SAMPLES	= __DIR__ . '/samples/';
	const PATH_EXPECTED	= __DIR__ . '/samples/expectedAtomFormat/';

	private $sample;
	private $format;
	private $data;

	/**
	 * @dataProvider sampleProvider
	 * @runInSeparateProcess
	 * @requires function xdebug_get_headers
	 */
	public function testHeaders($path) {
		$this->setSample($path);
		$this->initFormat();

		$this->assertContains(
			'Content-Type: application/atom+xml; charset=' . $this->format->getCharset(),
			xdebug_get_headers()
		);
	}

	/**
	 * @dataProvider sampleProvider
	 * @runInSeparateProcess
	 */
	public function testOutput($path) {
		$this->setSample($path);
		$this->initFormat();

		$this->assertXmlStringEqualsXmlFile($this->sample->expected, $this->data);
	}

	public function sampleProvider() {
		$samples = array();
		foreach (glob(self::PATH_SAMPLES . '*.json') as $path) {
			$samples[basename($path, '.json')] = array($path);
		}
		return $samples;
	}

	private function setSample($path) {
		$data = json_decode(file_get_contents($path), true);
		if (isset($data['meta']) && isset($data['items'])) {
			if (!empty($data['server']))
				$this->setServerVars($data['server']);

			$items = array();
			foreach($data['items'] as $item) {
				$items[] = new \FeedItem($item);
			}

			$this->sample = (object)array(
				'meta'		=> $data['meta'],
				'items'		=> $items,
				'expected'	=> self::PATH_EXPECTED . basename($path, '.json') . '.xml'
			);
		} else {
			$this->fail('invalid test sample: ' . basename($path, '.json'));
		}
	}

	private function setServerVars($list) {
		$_SERVER = array_merge($_SERVER, $list);
	}

	private function initFormat() {
		$formatFac = new FormatFactory();
		$formatFac->setWorkingDir(PATH_LIB_FORMATS);
		$this->format = $formatFac->create('Atom');
		$this->format->setItems($this->sample->items);
		$this->format->setExtraInfos($this->sample->meta);
		$this->format->setLastModified(strtotime('2000-01-01 12:00:00 UTC'));

		$_ = $this->format->display();
		$this->data = $this->getActualOutput();
		$this->assertNotFalse(simplexml_load_string($this->data));
		ob_clean();
	}
}
