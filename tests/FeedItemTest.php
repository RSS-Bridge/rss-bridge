<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../lib/FeedItem.php';

final class FeedItemTest extends TestCase {

	#region setURI()

	public function test_setURI_SchemeInvalid() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setURI('ftp://ftp.download.stuff/files/public');
	}

	public function test_setURI_SchemeMissing() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setURI('www.github.com/rss-bridge/rss-bridge');
	}

	public function test_setURI_HostMissing() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setURI('https:///rss-bridge/rss-bridge');
	}

	public function test_setURI_PathMissing() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setURI('https://www.github.com');
	}

	public function test_setURI_TypeInvalid() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setURI(array());
	}

	public function test_setURI() {

		// All of these URIs must pass
		$uris = array(
			'https://www.github.com/rss-bridge/rss-bridge/blob/master/index.php',
			'https://www.github.com/rss-bridge/rss-bridge/blob/master/',
			'https://www.github.com/',
			'http://www.github.com/',
			'http://github.com/',
			'https://github.com/search?q=rss-bridge&type=Repositories',
		);

		foreach($uris as $uri) {
			$item = new \FeedItem();
			$result = $item->setURI($uri);
			$this->assertInstanceOf(\FeedItem::class, $result);
		}

	}

	#endregion setURI()

	#region setTitle()

	public function test_setTitle_InvalidType() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setTitle(array());
	}

	public function test_setTitle() {

		// All of these titles must pass
		$titles = array(
			'<a href="https://github.com">Awesome title!</a>',
			'     Awesome title!     ',
		);

		foreach($titles as $title) {
			$item = new \FeedItem();
			$result = $item->setTitle($title);
			$this->assertInstanceOf(\FeedItem::class, $result);
		}

	}

	#endregion setTitle()

	#region setTimestamp()

	public function test_setTimestamp() {

		// All of these timestamps must pass
		$timestamps = array(
			strtotime('now'),
			strtotime('10 September 2000'),
			strtotime('+1 day'),
		);

		foreach($timestamps as $timestamp) {
			$item = new \FeedItem();
			$result = $item->setTimestamp($timestamp);
			$this->assertInstanceOf(\FeedItem::class, $result);
		}

	}

	#endregion setTimestamp()

	#region setAuthor()

	public function test_setAuthor() {

		// All of these values must pass
		$values = array(
			'User 1',
		);

		foreach($values as $value) {
			$item = new \FeedItem();
			$result = $item->setAuthor($value);
			$this->assertInstanceOf(\FeedItem::class, $result);
		}

	}

	#endregion setAuthor()

	#region setContent()

	public function test_setContent_InvalidType() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setContent(array());
	}

	public function test_setContent() {

		// All of these values must pass
		$values = array(
			'Hello World!',
		);

		foreach($values as $value) {
			$item = new \FeedItem();
			$result = $item->setContent($value);
			$this->assertInstanceOf(\FeedItem::class, $result);
		}

	}

	#endregion setContent()

	#region setEnclosures()

	public function test_setEnclosures_InvalidType() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setEnclosures('https://github.com/rss-bridge/rss-bridge/blob/master/README.md');
	}

	public function test_setEnclosures_InvalidElementType() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setEnclosures('Hello World!');
	}

	public function test_setEnclosures() {
		$item = new \FeedItem();
		$result = $item->setEnclosures(
			array(
				'https://github.com/rss-bridge/rss-bridge/blob/master/README.md',
				'ftp://domain.ftp.com/files/public/file.txt',
			)
		);
		$this->assertInstanceOf(\FeedItem::class, $result);
	}

	#endregion setEnclosures()

	#region setCategories()

	public function test_setCategories_InvalidType() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->setCategories('php');
	}

	public function test_setCategories() {
		$item = new \FeedItem();
		$result = $item->setCategories(
			array(
				'php', 'rss-bridge', 'unlicense', 'atom-feed', 'rss-feed',
			)
		);
		$this->assertInstanceOf(\FeedItem::class, $result);
	}

	#endregion setCategories()

	#region setMisc()

	public function test_setMisc_InvalidKeyType() {
		$item = new \FeedItem();
		$this->expectException(\InvalidArgumentException::class);
		$item->addMisc(1, 'First Element');
	}

	public function test_setMisc() {

		// All of these values must pass
		$values = array(
			'id' => 42,
			'guid' => '9842abd3-884d-4b62-a098-338f64cb12de',
			'lang' => 'en-US',
		);

		foreach($values as $name => $value) {
			$item = new \FeedItem();
			$result = $item->addMisc($name, $value);
			$this->assertInstanceOf(\FeedItem::class, $result);
		}

	}

	#endregion setMisc()

	#region toArray()

	public function test_toArray() {
		$item = array();

		$item['uri'] = 'https://www.github.com/rss-bridge/rss-bridge/';
		$item['title'] = 'Title';
		$item['timestamp'] = strtotime('now');
		$item['autor'] = 'Unknown author';
		$item['content'] = 'Hello World!';
		$item['enclosures'] = array('https://github.com/favicon.ico');
		$item['categories'] = array('php', 'rss-bridge', 'awesome');

		$feedItem = new \FeedItem($item);

		foreach($item as $key => $value) {
			$this->assertArrayHasKey($key, $feedItem->toArray());
		}
	}

	#endregion toArray()

}
