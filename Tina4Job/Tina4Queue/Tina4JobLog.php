<?php

namespace Tina4Jobs\Tina4Queue;

/**
 * Timestamped worker log lines: info to stdout, errors to stderr, so container log
 * collectors can tell them apart
 */
final class Tina4JobLog
{
    public static function info(string $message, string $queue = ""): void
    {
        self::write(STDOUT, "INFO", $message, $queue);
    }

    public static function error(string $message, string $queue = ""): void
    {
        self::write(STDERR, "ERROR", $message, $queue);
    }

    private static function write($stream, string $level, string $message, string $queue): void
    {
        $context = $queue !== "" ? " [{$queue}]" : "";
        $line = "[" . date("Y-m-d H:i:s") . "] {$level}{$context} [" . getmypid() . "] {$message}\n";

        if (is_resource($stream)) {
            fwrite($stream, $line);
        } else {
            error_log(rtrim($line));
        }
    }
}
