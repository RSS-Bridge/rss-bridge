<?php
class HttpException extends \Exception{}

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
	if(!isset($title) || !isset($body) || empty($title) || empty($body)) {
		return null;
	}

	// Add title and body
	$uri = 'https://github.com/rss-bridge/rss-bridge/issues/new?title='
		. urlencode($title)
		. '&body='
		. urlencode($body);

	// Add labels
	if(!is_null($labels) && is_array($labels) && count($labels) > 0) {
		if(count($lables) === 1) {
			$uri .= '&labels=' . urlencode($labels[0]);
		} else {
			foreach($labels as $label) {
				$uri .= '&labels[]=' . urlencode($label);
			}
		}
	} elseif(!is_null($labels) && is_string($labels)) {
		$uri .= '&labels=' . urlencode($labels);
	}

	// Add maintainer
	if(!empty($maintainer)) {
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
	if(( !($e instanceof \Exception) && !($e instanceof \Error)) || !($bridge instanceof \BridgeInterface)) {
		return null;
	}

	$title = $bridge->getName() . ' failed with error ' . $e->getCode();

	// Build a GitHub compatible message
	$body = 'Error message: `'
	. $e->getMessage()
	. "`\nQuery string: `"
	. $_SERVER['QUERY_STRING']
	. "`\nVersion: `"
	. Configuration::getVersion()
	. '`';

	$link = buildGitHubIssueQuery($title, $body, 'bug report', $bridge->getMaintainer());

	$header = buildHeader($e, $bridge);
	$message = "<strong>{$bridge->getName()}</strong> was
unable to receive or process the remote website's content!";
	$section = buildSection($e, $bridge, $message, $link);

	return buildPage($title, $header, $section);
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
	if(( !($e instanceof \Exception) && !($e instanceof \Error)) || !($bridge instanceof \BridgeInterface)) {
		return null;
	}

	$title = $bridge->getName() . ' failed with error ' . $e->getCode();

	// Build a GitHub compatible message
	$body = 'Error message: `'
	. $e->getMessage()
	. "`\nQuery string: `"
	. $_SERVER['QUERY_STRING'] . '`';

	$link = buildGitHubIssueQuery($title, $body, 'bug report', $bridge->getMaintainer());
	$header = buildHeader($e, $bridge);
	$message = "RSS-Bridge was unable to transform the contents returned by
<strong>{$bridge->getName()}</strong>!";
	$section = buildSection($e, $bridge, $message, $link);

	return buildPage($title, $header, $section);
}

function buildHeader($e, $bridge){
	return <<<EOD
<header>
	<h1>Error {$e->getCode()}</h1>
	<h2>{$e->getMessage()}</h2>
	<p class="status">{$bridge->getName()}</p>
</header>
EOD;
}

function buildSection($e, $bridge, $message, $link){
	return <<<EOD
<section>
	<p class="exception-message">{$message}</p>
	<div class="advice">
		<ul class="advice">
			<li>Press Return to check your input parameters</li>
			<li>Press F5 to retry</li>
			<li>Open a GitHub Issue if this error persists</li>
		</ul>
	</div>
	<a href="{$link}" title="After clicking this button you can review
	the issue before submitting it"><button>Open GitHub Issue</button></a>
	<p class="maintainer">{$bridge->getMaintainer()}</p>
</section>
EOD;
}

function buildPage($title, $header, $section){
	return <<<EOD
<!DOCTYPE html>
<html lang="en">
<head>
	<title>{$title}</title>
	<link href="static/style.css" rel="stylesheet">
</head>
<body>
	{$header}
	{$section}
</body>
</html>
EOD;
}
