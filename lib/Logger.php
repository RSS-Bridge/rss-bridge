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
            $context['url'] = get_current_url();
            $context['message'] = create_sane_exception_message($context['e']);
            $context['code'] = $context['e']->getCode();
            $context['stacktrace'] = create_sane_stacktrace($context['e']);
            unset($context['e']);
            $ignoredExceptions = [
                'Exception Exception: You must specify a format!',
                'Exception InvalidArgumentException: Format name invalid!',
                'Exception InvalidArgumentException: Unknown format given!',
                'Exception InvalidArgumentException: Bridge name invalid!',
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
