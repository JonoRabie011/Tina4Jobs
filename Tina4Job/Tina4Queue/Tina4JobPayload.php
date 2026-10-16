<?php

namespace Tina4Jobs\Tina4Queue;

/**
 * Encoding and inspection of queued job payloads, shared by the queue drivers and the worker
 */
final class Tina4JobPayload
{
    /**
     * Failed job exceptions are stored in a TEXT column (64KB in MySQL)
     */
    private const MAX_EXCEPTION_LENGTH = 60000;

    /**
     * Serialize a job for storage. uuencoded so binary-safe serialized data survives TEXT columns.
     * @param object|string $job
     * @return string
     */
    public static function encode(object|string $job): string
    {
        return convert_uuencode(serialize($job));
    }

    /**
     * Restore a job from storage. Accepts uuencoded and plain serialized payloads.
     * @param string $payload
     * @return object|null The job, or null when the payload is not a usable job object
     */
    public static function decode(string $payload): ?object
    {
        $serialized = @convert_uudecode($payload);

        if ($serialized === false || $serialized === "") {
            $serialized = $payload;
        }

        $job = @unserialize($serialized);

        if (!is_object($job) || $job instanceof \__PHP_Incomplete_Class || !method_exists($job, 'handle')) {
            return null;
        }

        return $job;
    }

    /**
     * Whether a failed job should be tried again. Only jobs that declare $attempts are retried:
     * retrying is opt-in, because jobs with side effects (payments, emails) may not be safe to repeat.
     * @param object $job
     * @return bool
     */
    public static function canRetry(object $job): bool
    {
        return property_exists($job, 'attempts') && method_exists($job, 'attempt');
    }

    /**
     * Readable description of an exception for the failed jobs table. Not serialize($e):
     * that throws when the trace holds a closure, which turned every such failure into a crash.
     * @param \Throwable|string $exception
     * @return string
     */
    public static function describe(\Throwable|string $exception): string
    {
        return self::truncate((string)$exception);
    }

    /**
     * @param string $text
     * @return string
     */
    public static function truncate(string $text): string
    {
        if (strlen($text) <= self::MAX_EXCEPTION_LENGTH) {
            return $text;
        }

        return substr($text, 0, self::MAX_EXCEPTION_LENGTH) . "\n... (truncated)";
    }
}
