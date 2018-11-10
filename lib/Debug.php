<?php

class Debug {
	public static function log($text) {
		if(!DEBUG) {
			return;
		}

		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
		$calling = $backtrace[2];
		$message = $calling['file'] . ':'
			. $calling['line'] . ' class '
			. (isset($calling['class']) ? $calling['class'] : '<no-class>') . '->'
			. $calling['function'] . ' - '
			. $text;

		error_log($message);
	}
}
