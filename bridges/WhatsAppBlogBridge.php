<?php

class WhatsAppBlogBridge extends BridgeAbstract
{
    const NAME = 'WhatsApp Blog';
    const URI = 'https://blog.whatsapp.com/';
    const DESCRIPTION = 'WhatsApp Blog';
    const MAINTAINER = 'latz';
    const CACHE_TIMEOUT = 3600; // 1h

    public function collectData()
    {
        $html = getContents('https://blog.whatsapp.com/');

        // Extract subjects and bodies with offset capture for better performance
        $subjectPattern = '/Subject=([^&]+)&amp;body=([^"]+)/';
        preg_match_all($subjectPattern, $html, $subjectMatches, PREG_OFFSET_CAPTURE);

        // Cache the count to avoid repeated calls
        $matchCount = count($subjectMatches[0]);

        for ($i = 0; $i < $matchCount; $i++) {
            $subject = urldecode($subjectMatches[1][$i][0]);
            $bodyText = urldecode($subjectMatches[2][$i][0]);

            // Extract URL from body
            if (!preg_match('/http[^\s]*/', $bodyText, $urlMatch)) {
                continue;
            }

            // Unescape and clean URL
            $url = strtr($urlMatch[0], ['\\/' => '/']);
            $url = rtrim($url, '\\');

            // Extract body content by finding position of URL
            $urlPos = strpos($bodyText, $urlMatch[0]);
            $body = trim(substr($bodyText, 0, $urlPos) ?: $bodyText);

            // NOTE: The WhatsApp blog is a JavaScript-rendered React application.
            // Post publish dates are NOT available in the static HTML source code.
            $timestamp = time();

            $item = [
                'title' => $subject,
                'uri' => $url,
                'timestamp' => $timestamp,
                'content' => $body,
                'uid' => hash('sha256', $url . $timestamp) // Better uniqueness than simple md5
            ];

            $this->items[] = $item;
        }
    }
}
