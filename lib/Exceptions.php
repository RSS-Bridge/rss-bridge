<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

/**
 * Builds a GitHub search query to find open bugs for the current bridge
 */
function buildGitHubSearchQuery($bridgeName)
{
    return REPOSITORY
    . 'issues?q='
    . urlencode('is:issue is:open ' . $bridgeName);
}

/**
 * Returns an URL that automatically populates a new issue on GitHub based
 * on the information provided
 *
 * @param string $title string Sets the title of the issue
 * @param string $body string Sets the body of the issue (GitHub markdown applies)
 * @param string $labels mixed (optional) Specifies labels to add to the issue
 * @param string $maintainer string (optional) Specifies the maintainer for the issue.
 * The maintainer only applies if part of the development team!
 * @return string|null A qualified URL to a new issue with populated conent or null.
 *
 * @todo This function belongs inside a class
 */
function buildGitHubIssueQuery($title, $body, $labels = null, $maintainer = null)
{
    if (!isset($title) || !isset($body) || empty($title) || empty($body)) {
        return null;
    }

    // Add title and body
    $uri = REPOSITORY
        . 'issues/new?title='
        . urlencode($title)
        . '&body='
        . urlencode($body);

    // Add labels
    if (!is_null($labels) && is_array($labels) && count($labels) > 0) {
        if (count($lables) === 1) {
            $uri .= '&labels=' . urlencode($labels[0]);
        } else {
            foreach ($labels as $label) {
                $uri .= '&labels[]=' . urlencode($label);
            }
        }
    } elseif (!is_null($labels) && is_string($labels)) {
        $uri .= '&labels=' . urlencode($labels);
    }

    // Add maintainer
    if (!empty($maintainer)) {
        $uri .= '&assignee=' . urlencode($maintainer);
    }

    return $uri;
}

/**
 * Returns the exception message as HTML string
 *
 * @param object $e Exception The exception to show
 * @param object $bridge object The bridge object
 * @return string|null Returns the exception as HTML string or null.
 *
 * @todo This function belongs inside a class
 */
function buildBridgeException($e, $bridge)
{
    if (( !($e instanceof \Exception) && !($e instanceof \Error)) || !($bridge instanceof \BridgeInterface)) {
        return null;
    }

    $title = $bridge->getName() . ' failed with error ' . $e->getCode();

    // Build a GitHub compatible message
    $body = 'Error message: `'
    . $e->getMessage()
    . "`\nQuery string: `"
    . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '')
    . "`\nVersion: `"
    . Configuration::getVersion()
    . '`';

    $body_html = nl2br($body);
    $link = buildGitHubIssueQuery($title, $body, 'Bridge-Broken', $bridge->getMaintainer());
    $searchQuery = buildGitHubSearchQuery($bridge::NAME);

    $header = buildHeader($e, $bridge);
    $message = <<<EOD
<strong>{$bridge->getName()}</strong> was unable to receive or process the
remote website's content!<br>
{$body_html}
EOD;
    $section = buildSection($e, $bridge, $message, $link, $searchQuery);

    return $section;
}

/**
 * Returns the exception message as HTML string
 *
 * @param object $e Exception The exception to show
 * @param object $bridge object The bridge object
 * @return string|null Returns the exception as HTML string or null.
 *
 * @todo This function belongs inside a class
 */
function buildTransformException($e, $bridge)
{
    if (( !($e instanceof \Exception) && !($e instanceof \Error)) || !($bridge instanceof \BridgeInterface)) {
        return null;
    }

    $title = $bridge->getName() . ' failed with error ' . $e->getCode();

    // Build a GitHub compatible message
    $body = 'Error message: `'
    . $e->getMessage()
    . "`\nQuery string: `"
    . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '')
    . '`';

    $link = buildGitHubIssueQuery($title, $body, 'Bridge-Broken', $bridge->getMaintainer());
    $searchQuery = buildGitHubSearchQuery($bridge::NAME);
    $header = buildHeader($e, $bridge);
    $message = "RSS-Bridge was unable to transform the contents returned by
<strong>{$bridge->getName()}</strong>!";
    $section = buildSection($e, $bridge, $message, $link, $searchQuery);

    return buildPage($title, $header, $section);
}

/**
 * Builds a new HTML header with data from a exception an a bridge
 *
 * @param object $e The exception object
 * @param object $bridge The bridge object
 * @return string The HTML header
 *
 * @todo This function belongs inside a class
 */
function buildHeader($e, $bridge)
{
    return <<<EOD
<header>
	<h1>Error {$e->getCode()}</h1>
	<h2>{$e->getMessage()}</h2>
	<p class="status">{$bridge->getName()}</p>
</header>
EOD;
}

/**
 * Builds a new HTML section
 *
 * @param object $e The exception object
 * @param object $bridge The bridge object
 * @param string $message The message to display
 * @param string $link The link to include in the anchor
 * @param string $searchQuery A GitHub search query for the current bridge
 * @return string The HTML section
 *
 * @todo This function belongs inside a class
 */
function buildSection($e, $bridge, $message, $link, $searchQuery)
{
    return <<<EOD
<section>
	<p class="exception-message">{$message}</p>
	<div class="advice">
		<ul class="advice">
			<li>Press Return to check your input parameters</li>
			<li>Press F5 to retry</li>
			<li>Check if this issue was already reported on <a href="{$searchQuery}">GitHub</a> (give it a thumbs-up)</li>
			<li>Open a <a href="{$link}">GitHub Issue</a> if this error persists</li>
		</ul>
	</div>
	<a href="{$searchQuery}" title="Opens GitHub to search for similar issues">
		<button>Search GitHub Issues</button>
	</a>
	<a href="{$link}" title="After clicking this button you can review
	the issue before submitting it"><button>Open GitHub Issue</button></a>
	<p class="maintainer">{$bridge->getMaintainer()}</p>
</section>
EOD;
}

/**
 * Builds a new HTML page
 *
 * @param string $title The HTML title
 * @param string $header The HTML header
 * @param string $section The HTML section
 * @return string The HTML page
 *
 * @todo This function belongs inside a class
 */
function buildPage($title, $header, $section)
{
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
