<?php
require_once('GelbooruBridge.php');

class Rule34Bridge extends GelbooruBridge{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Rule34";
	const URI = "http://rule34.xxx/";
	const DESCRIPTION = "Returns images from given page";

    const PIDBYPAGE=50;
}
