<?php
require_once('GelbooruBridge.php');

class SafebooruBridge extends GelbooruBridge{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Safebooru";
	const URI = "http://safebooru.org/";
	const DESCRIPTION = "Returns images from given page";

    const PIDBYPAGE=40;
}
