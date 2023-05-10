<?php

declare(strict_types=1);

final class Logger
{
    public static function debug(string $message, array $context = [])
    {
        if (Debug::isEnabled()) {
            self::log('DEBUG', $message, $context);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (isset($context['e'])) {
            /** @var \Throwable $e */
            $e = $context['e'];
            unset($context['e']);
            $context['type'] = get_class($e);
            $context['code'] = $e->getCode();
            $context['message'] = sanitize_root($e->getMessage());
            $context['file'] = sanitize_root($e->getFile());
            $context['line'] = $e->getLine();
            $context['url'] = get_current_url();
            $context['trace'] = trace_to_call_points(trace_from_exception($e));
            // Don't log these exceptions
            $ignoredExceptions = [
                'You must specify a format',
                'Format name invalid',
                'Unknown format given',
                'Bridge name invalid',
                'Invalid action',
                'twitter: No results for this query',
                // telegram
                'Unable to find channel. The channel is non-existing or non-public',
                // fb
                'This group is not public! RSS-Bridge only supports public groups!',
            ];
            foreach ($ignoredExceptions as $ignoredException) {
                if (str_starts_with($e->getMessage(), $ignoredException)) {
                    return;
                }
            }
        }
        // Intentionally not sanitizing $message
        $text = sprintf(
            "[%s] rssbridge.%s %s %s\n",
            now()->format('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? Json::encode($context) : ''
        );
        // Log to stderr/stdout whatever that is
        error_log($text);
    }
}
