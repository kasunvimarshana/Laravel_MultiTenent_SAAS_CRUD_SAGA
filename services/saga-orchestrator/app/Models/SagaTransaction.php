<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SagaTransaction model.
 *
 * Represents the state of a single distributed transaction coordinated by
 * the SAGA orchestrator.  Each record tracks progress through a sequence of
 * steps and holds the data needed to compensate already-executed steps when
 * a failure occurs.
 *
 * @property string                         $id
 * @property string                         $saga_id
 * @property string|null                    $tenant_id
 * @property string                         $saga_type
 * @property string|null                    $current_step
 * @property string                         $status
 * @property array<string, mixed>           $payload
 * @property array<int, string>             $completed_steps
 * @property string|null                    $failed_step
 * @property string|null                    $error_message
 * @property int                            $retry_count
 * @property \Carbon\Carbon|null            $started_at
 * @property \Carbon\Carbon|null            $completed_at
 * @property \Carbon\Carbon                 $created_at
 * @property \Carbon\Carbon                 $updated_at
 */
class SagaTransaction extends Model
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
    protected $table = 'saga_transactions';

    /** @var list<string> */
    protected $fillable = [
        'saga_id',
        'tenant_id',
        'saga_type',
        'current_step',
        'status',
        'payload',
        'completed_steps',
        'failed_step',
        'error_message',
        'retry_count',
        'started_at',
        'completed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload'         => 'array',
        'completed_steps' => 'array',
        'started_at'      => 'datetime',
        'completed_at'    => 'datetime',
        'retry_count'     => 'integer',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status'          => self::STATUS_PENDING,
        'completed_steps' => '[]',
        'retry_count'     => 0,
    ];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    /**
     * The individual steps that make up this saga transaction.
     *
     * @return HasMany<SagaStep>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(SagaStep::class, 'saga_transaction_id')
                    ->orderBy('step_order');
    }

    // -----------------------------------------------------------------------
    // Helper methods
    // -----------------------------------------------------------------------

    /**
     * Check whether the saga is currently in a terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_COMPENSATED,
        ], true);
    }

    /**
     * Check whether the saga has failed and awaits compensation.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check whether the saga is currently compensating.
     */
    public function isCompensating(): bool
    {
        return $this->status === self::STATUS_COMPENSATING;
    }

    /**
     * Mark the step name as completed in the completed_steps array.
     */
    public function markStepCompleted(string $stepName): void
    {
        $steps   = $this->completed_steps ?? [];
        $steps[] = $stepName;

        $this->completed_steps = array_values(array_unique($steps));
    }

    /**
     * Increment the retry counter and persist.
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }
}
