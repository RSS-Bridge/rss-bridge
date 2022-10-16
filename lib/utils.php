<?php

final class HttpException extends \Exception
{
}

final class Json
{
    public static function encode($value): string
    {
        $flags = JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        return \json_encode($value, $flags);
    }

    public static function decode(string $json, bool $assoc = true)
    {
        return \json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
    }
}

/**
 * Get the home page url of rss-bridge e.g. 'https://example.com/' or 'https://example.com/bridge/'
 */
function get_home_page_url(): string
{
    $https = $_SERVER['HTTPS'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (($pos = strpos($uri, '?')) !== false) {
        $uri = substr($uri, 0, $pos);
    }
    $scheme = $https === 'on' ? 'https' : 'http';
    return "$scheme://$host$uri";
}

/**
 * Get the full current url e.g. 'http://example.com/?action=display&bridge=FooBridge'
 */
function get_current_url(): string
{
    $https = $_SERVER['HTTPS'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $scheme = $https === 'on' ? 'https' : 'http';
    return "$scheme://$host$uri";
}

function create_sane_exception_message(\Throwable $e): string
{
    return sprintf(
        'Exception %s: %s in %s line %s',
        get_class($e),
        $e->getMessage(),
        trim_path_prefix($e->getFile()),
        $e->getLine()
    );
}

/**
 * Returns e.g. https://github.com/RSS-Bridge/rss-bridge/blob/master/bridges/AO3Bridge.php#L8
 */
function render_github_url(string $file, int $line, string $revision = 'master'): string
{
    return sprintf('https://github.com/RSS-Bridge/rss-bridge/blob/%s/%s#L%s', $revision, $file, $line);
}

function trace_from_exception(\Throwable $e): array
{
    $frames = array_reverse($e->getTrace());
    $frames[] = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
    $trace = [];
    foreach ($frames as $frame) {
        $trace[] = [
            'file'      => trim_path_prefix($frame['file'] ?? ''),
            'line'      => $frame['line'] ?? null,
            'class'     => $frame['class'] ?? null,
            'type'      => $frame['type'] ?? null,
            'function'  => $frame['function'] ?? null,
        ];
    }
    return $trace;
}

function trace_to_call_points(array $trace): array
{
    return array_map(fn($frame) => frame_to_call_point($frame), $trace);
}

function frame_to_call_point(array $frame): string
{
    if ($frame['class']) {
        return sprintf(
            '%s(%s): %s%s%s()',
            $frame['file'],
            $frame['line'],
            $frame['class'],
            $frame['type'],
            $frame['function'],
        );
    } elseif ($frame['function']) {
        return sprintf(
            '%s(%s): %s()',
            $frame['file'],
            $frame['line'],
            $frame['function'],
        );
    } else {
        return sprintf(
            '%s(%s)',
            $frame['file'],
            $frame['line'],
        );
    }
}

/**
 * Trim path prefix for privacy/security reasons
 *
 * Example: "/var/www/rss-bridge/index.php" => "index.php"
 */
function trim_path_prefix(string $filePath): string
{
    return mb_substr($filePath, mb_strlen(dirname(__DIR__)) + 1);
}

/**
 * This is buggy because strip tags removes a lot that isn't html
 */
function is_html(string $text): bool
{
    return strlen(strip_tags($text)) !== strlen($text);
}

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
function parse_mime_type($url)
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

/**
 * https://stackoverflow.com/a/2510459
 */
function format_bytes(int $bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function now(): \DateTimeImmutable
{
    return new \DateTimeImmutable();
}
