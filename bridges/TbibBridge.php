<?php
require_once('GelbooruBridge.php');

class TbibBridge extends GelbooruBridge {

	const MAINTAINER = 'mitsukarenai';
	const NAME = 'Tbib';
	const URI = 'https://tbib.org/';
	const DESCRIPTION = 'Returns images from given page';

	const PIDBYPAGE = 50;
}
