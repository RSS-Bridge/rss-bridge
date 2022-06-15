<?php

class ImgflipBridge extends JsonAbstract {

	const NAME = 'Imgflip Bridge';
	const DESCRIPTION = 'Returns the latest memes';
	const URI = 'https://imgflip.com/';
	const MAINTAINER = 'Yaman Qalieh';

	const FEED_SOURCE_URL = 'https://api.imgflip.com/get_memes';
	const USER_EXPRESSION_ITEM = 'data.memes';
	const USER_EXPRESSION_ITEM_TITLE = 'name';
	const USER_EXPRESSION_ITEM_CONTENT = 'url';
	const USER_EXPRESSION_ITEM_URI = 'url';
	const USER_EXPRESSION_ITEM_ENCLOSURES = 'url';
	const USER_EXPRESSION_UID = 'id';
}
