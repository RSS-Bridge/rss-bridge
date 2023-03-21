<?php

class Debug
{
    /**
     * Convenience function for Configuration::getConfig('system', 'debug')
     */
    public static function isEnabled(): bool
    {
        return Configuration::getConfig('system', 'debug') === true;
    }

    public static function log($message)
    {
        $e = new \Exception();
        $trace = trace_from_exception($e);
        // Drop the current frame
        array_pop($trace);
        $lastFrame = $trace[array_key_last($trace)];
        $text = sprintf('%s(%s): %s', $lastFrame['file'], $lastFrame['line'], $message);
        Logger::debug($text);
    }
}
