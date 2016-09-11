<?php
class HttpException extends \Exception{}

/**
* Not real http implementation but only utils stuff
*/
class Http{

	/**
	* Return message corresponding to Http code
	*/
	static public function getMessageForCode($code){
		$codes = self::getCodes();

		if(isset($codes[$code]))
			return $codes[$code];

		return '';
	}

	/**
	* List of common Http code
	*/
	static public function getCodes(){
		return array(
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Moved Temporarily',
			307 => 'Temporary Redirect',
			310 => 'Too many Redirects',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested range unsatisfiable',
			417 => 'Expectation failed',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Time-out',
			508 => 'Loop detected',
		);
	}
}
