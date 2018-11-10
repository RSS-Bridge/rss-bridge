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
