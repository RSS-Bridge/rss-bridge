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

/**
 * Returns an URL that automatically populates a new issue on GitHub based
 * on the information provided
 *
 * @param $title string Sets the title of the issue
 * @param $body string Sets the body of the issue (GitHub markdown applies)
 * @param $labels mixed (optional) Specifies labels to add to the issue
 * @param $maintainer string (optional) Specifies the maintainer for the issue.
 * The maintainer only applies if part of the development team!
 * @return string Returns a qualified URL to a new issue with populated conent.
 * Returns null if title or body is null or empty
 */
function buildGitHubIssueQuery($title, $body, $labels = null, $maintainer = null){
	if(!isset($title) || !isset($body) || empty($title) || empty($body)){
		return null;
	}

	// Add title and body
	$uri = 'https://github.com/rss-bridge/rss-bridge/issues/new?title='
		. urlencode($title)
		. '&body='
		. urlencode($body);

	// Add labels
	if(!is_null($labels) && is_array($labels) && count($labels) > 0){
		if(count($lables) === 1){
			$uri .= '&labels=' . urlencode($labels[0]);
		} else {
			foreach($labels as $label){
				$uri .= '&labels[]=' . urlencode($label);
			}
		}
	} elseif(!is_null($labels) && is_string($labels)){
		$uri .= '&labels=' . urlencode($labels);
	}

	// Add maintainer
	if(!empty($maintainer)){
		$uri .= '&assignee=' . urlencode($maintainer);
	}

	return $uri;
}

/**
 * Returns the exception message as HTML string
 *
 * @param $e Exception The exception to show
 * @param $bridge object The bridge object
 * @return string Returns the exception as HTML string. Returns null if the
 * provided parameter are invalid
 */
function buildBridgeException($e, $bridge){
	if(!($e instanceof \Exception) || !($bridge instanceof \BridgeInterface)){
		return null;
	}

	$title = $bridge->getName() . ' failed with error ' . $e->getCode();

	// Build a GitHub compatible message
	$body = 'Error message: `'
	. $e->getmessage()
	. "`\nQuery string: `"
	. $_SERVER['QUERY_STRING'] . '`';

	$link = buildGitHubIssueQuery($title, $body, 'bug report', $bridge->getMaintainer());

	$message = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
	<title>{$e->getCode()} - {$e->getMessage()}</title>
</head>
<body>
	<h1>Error {$e->getCode()} - {$e->getMessage()}</h1>
	<p><strong>{$bridge->getName()}</strong> was unable to receive or process the remote website's content!
	<br>Check your input parameters or press F5 to retry.
	<br>If the error persists use <a href="{$link}">this</a> link to notify the bridge maintainer.
	<br>Notice: After clicking on the link you can review the issue before sending it.</p>
	<h2>Additional info</h2>
	<p>Error code: "{$e->getCode()}"</p>
	<p>Message: "{$e->getMessage()}"</p>
</body>
</html>
EOD;

	return $message;
}

/**
 * Returns the exception message as HTML string
 *
 * @param $e Exception The exception to show
 * @param $bridge object The bridge object
 * @return string Returns the exception as HTML string. Returns null if the
 * provided parameter are invalid
 */
function buildTransformException($e, $bridge){
	if(!($e instanceof \Exception) || !($bridge instanceof \BridgeInterface)){
		return null;
	}

	$title = $bridge->getName() . ' failed with error ' . $e->getCode();

	// Build a GitHub compatible message
	$body = 'Error message: `'
	. $e->getmessage()
	. "`\nQuery string: `"
	. $_SERVER['QUERY_STRING'] . '`';

	$link = buildGitHubIssueQuery($title, $body, 'bug report', $bridge->getMaintainer());

	$message = <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
	<title>{$e->getCode()} - {$e->getMessage()}</title>
</head>
<body>
	<h1>Error {$e->getCode()} - {$e->getMessage()}</h1>
	<p>RSS-Bridge was unable to transform the contents returned by <strong>{$bridge->getName()}</strong>!
	<br>Check your input parameters or press F5 to retry.
	<br>If the error persists use <a href="{$link}">this</a> link to notify the bridge maintainer.
	<br>Notice: After clicking on the link you can review the issue before sending it.</p>
	<h2>Additional info</h2>
	<p>Error code: "{$e->getCode()}"</p>
	<p>Message: "{$e->getMessage()}"</p>
</body>
</html>
EOD;

	return $message;
}
