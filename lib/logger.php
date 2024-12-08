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
        $ignoredMessages = [
            'Format name invalid',
            'Unknown format given',
            'Unable to find channel',
        ];
        foreach ($ignoredMessages as $ignoredMessage) {
            if (str_starts_with($message, $ignoredMessage)) {
                return;
            }
        }
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
    private string $stream;
    private int $level;

    public function __construct(string $stream, int $level = Logger::DEBUG)
    {
        $this->stream = $stream;
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
            $record['message'],
            $context
        );
        $bytes = file_put_contents($this->stream, $text, FILE_APPEND);
    }
}

final class ErrorLogHandler
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
        // Intentionally omitting newline
        $text = sprintf(
            '[%s] %s.%s %s %s',
            $record['created_at']->format('Y-m-d H:i:s'),
            $record['name'],
            $record['level_name'],
            $record['message'],
            $context
        );
        error_log($text);
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
