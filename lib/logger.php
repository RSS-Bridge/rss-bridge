<?php

declare(strict_types=1);

interface Logger
{
    public const DEBUG      = 10;
    public const INFO       = 20;
    public const WARNING    = 30;
    public const ERROR      = 40;

    public const LEVEL_NAMES = [
        self::DEBUG     => 'DEBUG',
        self::INFO      => 'INFO',
        self::WARNING   => 'WARNING',
        self::ERROR     => 'ERROR',
    ];

    public function debug(string $message, array $context = []);

    public function info(string $message, array $context = []): void;

    public function warning(string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;
}

final class SimpleLogger implements Logger
{
    private string $name;
    private array $handlers;

    /**
     * @param callable[] $handlers
     */
    public function __construct(
        string $name,
        array $handlers = []
    ) {
        $this->name = $name;
        $this->handlers = $handlers;
    }

    public function addHandler(callable $fn)
    {
        $this->handlers[] = $fn;
    }

    public function debug(string $message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    private function log(int $level, string $message, array $context = []): void
    {
        foreach ($this->handlers as $handler) {
            $handler([
                'name'          => $this->name,
                'created_at'    => now(),
                'level'         => $level,
                'level_name'    => self::LEVEL_NAMES[$level],
                'message'       => $message,
                'context'       => $context,
            ]);
        }
    }
}

final class StreamHandler
{
    private int $level;

    public function __construct(int $level = Logger::DEBUG)
    {
        $this->level = $level;
    }

    public function __invoke(array $record)
    {
        if ($record['level'] < $this->level) {
            return;
        }
        if (isset($record['context']['e'])) {
            /** @var \Throwable $e */
            $e = $record['context']['e'];
            unset($record['context']['e']);
            $record['context']['type'] = get_class($e);
            $record['context']['code'] = $e->getCode();
            $record['context']['message'] = sanitize_root($e->getMessage());
            $record['context']['file'] = sanitize_root($e->getFile());
            $record['context']['line'] = $e->getLine();
            $record['context']['url'] = get_current_url();
            $record['context']['trace'] = trace_to_call_points(trace_from_exception($e));

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
                'You must be logged in to view this page',
                'Unable to get the page id. You should consider getting the ID by hand',
                // tiktok 404
                'https://www.tiktok.com/@',
            ];
            foreach ($ignoredExceptions as $ignoredException) {
                if (str_starts_with($e->getMessage(), $ignoredException)) {
                    return;
                }
            }
        }
        $context = '';
        if ($record['context']) {
            try {
                $context = Json::encode($record['context']);
            } catch (\JsonException $e) {
                $record['context']['message'] = null;
                $context = Json::encode($record['context']);
            }
        }
        $text = sprintf(
            "[%s] %s.%s %s %s\n",
            $record['created_at']->format('Y-m-d H:i:s'),
            $record['name'],
            $record['level_name'],
            // Should probably sanitize message for output context
            $record['message'],
            $context
        );
        error_log($text);
        if ($record['level'] < Logger::ERROR && Debug::isEnabled()) {
            // Not a good idea to print here because http headers might not have been sent
            print sprintf("<pre>%s</pre>\n", e($text));
        }
        //$bytes = file_put_contents('/tmp/rss-bridge.log', $text, FILE_APPEND | LOCK_EX);
    }
}

final class NullLogger implements Logger
{
    public function debug(string $message, array $context = [])
    {
    }

    public function info(string $message, array $context = []): void
    {
    }

    public function warning(string $message, array $context = []): void
    {
    }

    public function error(string $message, array $context = []): void
    {
    }
}
