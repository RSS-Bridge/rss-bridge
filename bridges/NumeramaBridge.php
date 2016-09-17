<?php
require_once('WordPressBridge.php');

class NumeramaBridge extends WordPressBridge {

    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Numerama';
    const URI = 'http://www.numerama.com/';
    const DESCRIPTION = 'Returns the newest posts from Numerama (full text)';
	const PARAMETERS = array();
    public function getCacheDuration() {

        return 1800; // 30min
    }
}
