<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

/**
 * Throws an exception when called.
 *
 * @throws \Exception when called
 * @param string $message The error message
 * @param int $code The HTTP error code
 * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes List of HTTP
 * status codes
 */
function returnError($message, $code){
	throw new \Exception($message, $code);
}

/**
 * Returns HTTP Error 400 (Bad Request) when called.
 *
 * @param string $message The error message
 */
function returnClientError($message){
	returnError($message, 400);
}

/**
 * Returns HTTP Error 500 (Internal Server Error) when called.
 *
 * @param string $message The error message
 */
function returnServerError($message){
	returnError($message, 500);
}
