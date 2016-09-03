<?php
require_once('GelbooruBridge.php');

class MspabooruBridge extends GelbooruBridge{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Mspabooru";
	const URI = "http://mspabooru.com/";
	const DESCRIPTION = "Returns images from given page";

    const PIDBYPAGE=50;
}
