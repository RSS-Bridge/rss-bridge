<?php

/**
 * Determines the MIME type from a URL/Path file extension.
 *
 * _Remarks_:
 *
 * * The built-in functions `mime_content_type` and `fileinfo` require fetching
 * remote contents.
 * * A caller can hint for a MIME type by appending `#.ext` to the URL (i.e. `#.image`).
 *
 * Based on https://stackoverflow.com/a/1147952
 *
 * @param string $url The URL or path to the file.
 * @return string The MIME type of the file.
 */
function getMimeType($url)
{
    static $mime = null;

    if (is_null($mime)) {
        // Default values, overriden by /etc/mime.types when present
        $mime = [
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'image' => 'image/*',
            'mp3' => 'audio/mpeg',
        ];
        // '@' is used to mute open_basedir warning, see issue #818
        if (@is_readable('/etc/mime.types')) {
            $file = fopen('/etc/mime.types', 'r');
            while (($line = fgets($file)) !== false) {
                $line = trim(preg_replace('/#.*/', '', $line));
                if (!$line) {
                    continue;
                }
                $parts = preg_split('/\s+/', $line);
                if (count($parts) == 1) {
                    continue;
                }
                $type = array_shift($parts);
                foreach ($parts as $part) {
                    $mime[$part] = $type;
                }
            }
            fclose($file);
        }
    }

    if (strpos($url, '?') !== false) {
        $url_temp = substr($url, 0, strpos($url, '?'));
        if (strpos($url, '#') !== false) {
            $anchor = substr($url, strpos($url, '#'));
            $url_temp .= $anchor;
        }
        $url = $url_temp;
    }

    $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    if (!empty($mime[$ext])) {
        return $mime[$ext];
    }

    return 'application/octet-stream';
}
