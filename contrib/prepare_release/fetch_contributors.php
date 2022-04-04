<?php
/* Generate the "Contributors" list for README.md automatically utilizing the GitHub API */

$url = 'https://api.github.com/repos/rss-bridge/rss-bridge/contributors';
$contributors = array();
$next = true;

while($next) { /* Collect all contributors */

	$c = curl_init();

	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/json',
			'User-Agent: RSS-Bridge'
			));
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_HEADER, true);

	$data = curl_exec($c);

	$headerSize = curl_getinfo($c, CURLINFO_HEADER_SIZE);
	$header = substr($data, 0, $headerSize);
	$headers = parseResponseHeader($header);

	curl_close($c);

	foreach(json_decode(substr($data, $headerSize)) as $contributor)
		$contributors[] = $contributor;

	// Extract links to "next", "last", etc...
	$links = explode(',', $headers[0]['link']);
	$next = false;

	// Check if there is a link with 'rel="next"'
	foreach($links as $link) {
		list($url, $type) = explode(';', $link, 2);

		if(trim($type) === 'rel="next"') {
			$url = trim(preg_replace('/([<>])/', '', $url));
			$next = true;
			break;
		}
	}

}

/* Example JSON data: https://api.github.com/repos/rss-bridge/rss-bridge/contributors */

// We want contributors sorted by name
usort($contributors, function($a, $b){
	return strcasecmp($a->login, $b->login);
});

// Export as Markdown list
foreach($contributors as $contributor) {
	echo "  * [{$contributor->login}]({$contributor->html_url})\n";
}

/**
 * Parses the provided response header into an associative array
 *
 * Based on https://stackoverflow.com/a/18682872
 */
function parseResponseHeader($header) {

	$headers = array();
	$requests = explode("\r\n\r\n", trim($header));

	foreach ($requests as $request) {

		$header = array();

		foreach (explode("\r\n", $request) as $i => $line) {

			if($i === 0) {
				$header['http_code'] = $line;
			} else {

				list ($key, $value) = explode(': ', $line);
				$header[$key] = $value;

			}

		}

		$headers[] = $header;

	}

	return $headers;

}
