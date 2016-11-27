<?php
require_once('GelbooruBridge.php');

class TbibBridge extends GelbooruBridge{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Tbib";
	const URI = "http://tbib.org/";
	const DESCRIPTION = "Returns images from given page";

    const PIDBYPAGE=50;
}
