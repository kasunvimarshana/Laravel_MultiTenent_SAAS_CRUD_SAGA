<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SagaStep model.
 *
 * Represents a single step within a SAGA transaction.  Tracks the step's
 * execution status, its input payload, and the result returned by the
 * remote service so that compensation can be performed if needed.
 *
 * @property string                $id
 * @property string                $saga_transaction_id
 * @property string                $step_name
 * @property int                   $step_order
 * @property string                $status
 * @property array<string, mixed>  $payload
 * @property array<string, mixed>  $result
 * @property \Carbon\Carbon|null   $started_at
 * @property \Carbon\Carbon|null   $completed_at
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 */
class SagaStep extends Model
{
    use HasFactory;
    use HasUuids;

    // -----------------------------------------------------------------------
    // Status constants
    // -----------------------------------------------------------------------

    public const STATUS_PENDING      = 'PENDING';
    public const STATUS_RUNNING      = 'RUNNING';
    public const STATUS_COMPLETED    = 'COMPLETED';
    public const STATUS_FAILED       = 'FAILED';
    public const STATUS_COMPENSATING = 'COMPENSATING';
    public const STATUS_COMPENSATED  = 'COMPENSATED';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_RUNNING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_COMPENSATING,
        self::STATUS_COMPENSATED,
    ];

    // -----------------------------------------------------------------------
    // Eloquent configuration
    // -----------------------------------------------------------------------

    /** @var string */
    protected $table = 'saga_steps';

    /** @var list<string> */
    protected $fillable = [
        'saga_transaction_id',
        'step_name',
        'step_order',
        'status',
        'payload',
        'result',
        'started_at',
        'completed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload'      => 'array',
        'result'       => 'array',
        'step_order'   => 'integer',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status'  => self::STATUS_PENDING,
        'payload' => '{}',
        'result'  => '{}',
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    /**
     * The saga transaction this step belongs to.
     *
     * @return BelongsTo<SagaTransaction, SagaStep>
     */
    public function sagaTransaction(): BelongsTo
    {
        return $this->belongsTo(SagaTransaction::class, 'saga_transaction_id');
    }

    // -----------------------------------------------------------------------
    // Helper methods
    // -----------------------------------------------------------------------

    /**
     * Mark this step as running (side-effect: sets started_at).
     */
    public function markRunning(): void
    {
        $this->update([
            'status'     => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark this step as completed (side-effect: sets completed_at).
     *
     * @param array<string, mixed> $result
     */
    public function markCompleted(array $result = []): void
    {
        $this->update([
            'status'       => self::STATUS_COMPLETED,
            'result'       => $result,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark this step as failed.
     */
    public function markFailed(string $reason = ''): void
    {
        $this->update([
            'status'       => self::STATUS_FAILED,
            'result'       => ['error' => $reason],
            'completed_at' => now(),
        ]);
    }
}
