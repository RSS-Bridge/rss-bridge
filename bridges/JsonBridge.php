<?php

/**
 * This is a bridge that requires the user to insert a url to some json.
 *
 * Also the user must specify the json keys for important elements such
 * as url, title and so on.
 */
final class JsonBridge extends BridgeAbstract
{
	public const NAME = 'JSON bridge';
	public const URI = 'https://github.com/RSS-Bridge/rss-bridge';
	public const DESCRIPTION = 'This bridge converts JSON to a feed';
	public const MAINTAINER = 'dvikan';

	public const PARAMETERS = [
		'main' => [
			'json_url' => [
				'name' => 'JSON url',
				'type' => 'text',
				'defaultValue' => 'https://www.executeprogram.com/api/pages/blog',
			],
			'items' => [
				'name' => 'Items key',
				'type' => 'text',
				'defaultValue' => 'posts',
			],
			'title' => [
				'name' => 'Title key',
				'type' => 'text',
				'defaultValue' => 'title',
			],
			'url' => [
				'name' => 'Url key',
				'type' => 'text',
				'defaultValue' => 'url',
			],
			'content' => [
				'name' => 'Content key',
				'type' => 'text',
				'defaultValue' => 'body',
			],
		],
	];

	public function collectData()
	{
		$jsonUrl = $this->getInput('json_url');
		$data = json_decode(getContents($jsonUrl), true);
		if (! $data) {
			throw new \Exception('Unable to decode json');
		}
		if (! isset($data[$this->getInput('items')])) {
			throw new \Exception('Unable to find any items');
		}

		foreach ($data[$this->getInput('items')] as $item) {
			$feedItem = new FeedItem();

			$feedItem->setTitle($item[$this->getInput('title')] ?? '');
			$feedItem->setURI($item[$this->getInput('url')] ?? '');
			$feedItem->setContent($item[$this->getInput('content')] ?? '');

			$this->items[] = $feedItem;
		}
	}
}
