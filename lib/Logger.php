<?php

declare(strict_types=1);

final class Logger
{
    public static function debug(string $message, array $context = [])
    {
        self::log('DEBUG', $message, $context);
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
            $context['message'] = create_sane_exception_message($context['e']);
            $context['code'] = $context['e']->getCode();
            $context['url'] = get_current_url();
            $context['trace'] = trace_to_call_points(trace_from_exception($context['e']));
            unset($context['e']);
            // Don't log these records
            $ignoredExceptions = [
                'Exception Exception: You must specify a format',
                'Exception InvalidArgumentException: Format name invalid',
                'Exception InvalidArgumentException: Unknown format given',
                'Exception InvalidArgumentException: Bridge name invalid',
                'Exception Exception: Invalid action',
                'Exception Exception: twitter: No results for this query',
                // telegram
                'Exception Exception: Unable to find channel. The channel is non-existing or non-public',
                // fb
                'Exception Exception: This group is not public! RSS-Bridge only supports public groups!',
            ];
            foreach ($ignoredExceptions as $ignoredException) {
                if (str_starts_with($context['message'], $ignoredException)) {
                    return;
                }
            }
        }
        $text = sprintf(
            "[%s] rssbridge.%s %s %s\n",
            now()->format('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? Json::encode($context) : ''
        );
        error_log($text);
    }
}
