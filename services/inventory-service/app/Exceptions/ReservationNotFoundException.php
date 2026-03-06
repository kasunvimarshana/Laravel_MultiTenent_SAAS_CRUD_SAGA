<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** Thrown when a requested reservation cannot be found or is in an invalid state. */
class ReservationNotFoundException extends RuntimeException
{
    public function __construct(string $reservationId)
    {
        parent::__construct("Reservation not found: {$reservationId}.");
    }
}
