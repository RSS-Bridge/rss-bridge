<?php

declare(strict_types=1);

final class Logger
{
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
            $context['file'] = trim_path_prefix($context['e']->getFile());
            $context['line'] = $context['e']->getLine();
            $context['code'] = $context['e']->getCode();
            $context['url'] = get_current_url();
            $context['trace'] = create_sane_stacktrace($context['e']);
            unset($context['e']);
            $ignoredExceptions = [
                'Exception Exception: You must specify a format!',
                'Exception InvalidArgumentException: Format name invalid!',
                'Exception InvalidArgumentException: Unknown format given!',
                'Exception InvalidArgumentException: Bridge name invalid!',
                'Exception Exception: twitter: No results for this query',
            ];
            foreach ($ignoredExceptions as $ignoredException) {
                if (str_starts_with($context['message'], $ignoredException)) {
                    // Don't log this record because it's usually a bot
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
