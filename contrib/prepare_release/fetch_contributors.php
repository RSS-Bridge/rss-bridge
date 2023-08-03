<?php

/* Generate the "Contributors" list for README.md automatically utilizing the GitHub API */

require __DIR__ . '/../../lib/bootstrap.php';

$url = 'https://api.github.com/repos/rss-bridge/rss-bridge/contributors';
$contributors = [];
$next = true;

while ($next) { /* Collect all contributors */
    $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'User-Agent' => 'RSS-Bridge',
    ];
    $httpClient = new CurlHttpClient();
    $response = $httpClient->request($url, ['headers' => $headers]);

    $json = $response->getBody();
    $json_decode = Json::decode($json, false);
    foreach ($json_decode as $contributor) {
        $contributors[] = $contributor;
    }

    // Extract links to "next", "last", etc...
    $link1 = $response->getHeader('link');
    $links = explode(',', $link1);
    $next = false;

    // Check if there is a link with 'rel="next"'
    foreach ($links as $link) {
        [$url, $type] = explode(';', $link, 2);

        if (trim($type) === 'rel="next"') {
            $url = trim(preg_replace('/([<>])/', '', $url));
            $next = true;
            break;
        }
    }
}

/* Example JSON data: https://api.github.com/repos/rss-bridge/rss-bridge/contributors */

// We want contributors sorted by name
usort($contributors, function ($a, $b) {
    return strcasecmp($a->login, $b->login);
});

// Export as Markdown list
foreach ($contributors as $contributor) {
    echo "  * [{$contributor->login}]({$contributor->html_url})\n";
}
