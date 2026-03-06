<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * SagaException – thrown when a SAGA workflow encounters a business-level error.
 *
 * Examples of conditions that raise this exception:
 *   – An unknown saga type was requested.
 *   – A saga ID could not be resolved to a stored transaction.
 *   – Compensation was triggered on an already-compensated saga.
 *   – A message broker publish/subscribe operation fails.
 */
final class SagaException extends RuntimeException
{
    /**
     * @param string          $message   Human-readable description of the error.
     * @param int             $code      HTTP status code hint (default: 422).
     * @param \Throwable|null $previous  Wrapped lower-level exception, if any.
     */
    public function __construct(
        string $message = '',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Factory method for "not found" errors.
     */
    public static function notFound(string $sagaId): self
    {
        return new self("Saga not found: {$sagaId}", 404);
    }

    /**
     * Factory method for "invalid transition" errors.
     */
    public static function invalidTransition(string $from, string $to): self
    {
        return new self("Invalid saga status transition from '{$from}' to '{$to}'.", 422);
    }

    /**
     * Factory method for broker failures.
     */
    public static function brokerFailure(string $detail, \Throwable $previous = null): self
    {
        return new self("Message broker error: {$detail}", 503, $previous);
    }
}
