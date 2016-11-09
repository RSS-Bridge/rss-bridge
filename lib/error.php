<?php
function returnError($message, $code){
	throw new \HttpException($message, $code);
}

function returnClientError($message){
	returnError($message, 400);
}

function returnServerError($message){
	returnError($message, 500);
}

function debugMessage($text){
	if(!file_exists('DEBUG')) {
		return;
	}

	$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
	$calling = $backtrace[2];
	$message = $calling['file'] . ':'
		. $calling['line'] . ' class '
		. $calling['class'] . '->'
		. $calling['function'] . ' - '
		. $text;

	error_log($message);
}
