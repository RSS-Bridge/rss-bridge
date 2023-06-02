<?php

class Debug
{
    /**
     * Convenience function for Configuration::getConfig('system', 'enable_debug_mode')
     */
    public static function isEnabled(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $enableDebugMode = Configuration::getConfig('system', 'enable_debug_mode');
        $debugModeWhitelist = Configuration::getConfig('system', 'debug_mode_whitelist') ?: [];
        if ($enableDebugMode && ($debugModeWhitelist === [] || in_array($ip, $debugModeWhitelist))) {
            return true;
        }
        return false;
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
