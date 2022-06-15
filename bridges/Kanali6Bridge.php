<?php

class Kanali6Bridge extends XPathAbstract {
	const NAME = 'Kanali6 Latest Podcasts';
	const DESCRIPTION = 'Returns the latest podcasts';
	const URI = 'https://kanali6.com.cy/mp3/TOC.html';
	const MAINTAINER = 'Yaman Qalieh';

	const FEED_SOURCE_URL = 'https://kanali6.com.cy/mp3/TOC.xml';
	const USER_EXPRESSION_ITEM = '//recording[position() <= 50]';
	const USER_EXPRESSION_ITEM_TITLE = './title';
	const USER_EXPRESSION_ITEM_CONTENT = './durationvisual';
	const USER_EXPRESSION_ITEM_URI = './filename';
	const USER_EXPRESSION_ITEM_AUTHOR = './/producersname';
	const USER_EXPRESSION_ITEM_TIMESTAMP = './recfinisheddatetime';
	const USER_EXPRESSION_ITEM_ENCLOSURES = './filename';

	public function getURI() {
		return self::URI;
	}
}
