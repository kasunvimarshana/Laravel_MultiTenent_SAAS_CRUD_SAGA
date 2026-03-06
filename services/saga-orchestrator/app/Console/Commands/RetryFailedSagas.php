<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SagaException;
use App\Interfaces\SagaOrchestratorInterface;
use App\Interfaces\SagaTransactionRepositoryInterface;
use App\Models\SagaTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * RetryFailedSagas – Artisan command that scans for failed saga transactions
 * and attempts to restart them, up to the configured retry limit.
 *
 * Usage:
 *   php artisan saga:retry-failed [--max-retries=3] [--dry-run]
 */
final class RetryFailedSagas extends Command
{
    /** @var string */
    protected $signature = 'saga:retry-failed
                            {--max-retries=3 : Maximum number of attempts before giving up}
                            {--dry-run       : List eligible sagas without actually retrying}';

    /** @var string */
    protected $description = 'Retry failed SAGA transactions that have not exceeded the maximum retry limit.';

    public function __construct(
        private readonly SagaOrchestratorInterface           $orchestrator,
        private readonly SagaTransactionRepositoryInterface  $repository,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxRetries = (int) $this->option('max-retries');
        $dryRun     = (bool) $this->option('dry-run');

        $failedSagas = $this->repository->findFailedRetryable($maxRetries);

        if ($failedSagas->isEmpty()) {
            $this->info('[RetryFailedSagas] No eligible failed sagas found.');
            return self::SUCCESS;
        }

        $this->info("[RetryFailedSagas] Found {$failedSagas->count()} saga(s) to retry.");

        if ($dryRun) {
            $this->table(
                ['Saga ID', 'Type', 'Failed Step', 'Retry Count', 'Error'],
                $failedSagas->map(fn (SagaTransaction $s) => [
                    $s->saga_id,
                    $s->saga_type,
                    $s->failed_step ?? '-',
                    $s->retry_count,
                    mb_strimwidth($s->error_message ?? '-', 0, 60, '…'),
                ]),
            );
            $this->warn('[RetryFailedSagas] Dry-run mode – no sagas were retried.');
            return self::SUCCESS;
        }

        $succeeded = 0;
        $failed    = 0;

        foreach ($failedSagas as $saga) {
            try {
                $saga->incrementRetry();

                $this->orchestrator->startSaga(
                    sagaType: $saga->saga_type,
                    payload:  $saga->payload ?? [],
                    tenantId: $saga->tenant_id,
                );

                $succeeded++;
                $this->line("[RetryFailedSagas] ✓ Retried saga '{$saga->saga_id}'.");

                Log::info('[RetryFailedSagas] Saga retried successfully', [
                    'saga_id'     => $saga->saga_id,
                    'retry_count' => $saga->retry_count,
                ]);
            } catch (SagaException $e) {
                $failed++;
                $this->error("[RetryFailedSagas] ✗ Failed to retry saga '{$saga->saga_id}': {$e->getMessage()}");

                Log::error('[RetryFailedSagas] Saga retry failed', [
                    'saga_id' => $saga->saga_id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->info("[RetryFailedSagas] Done. Succeeded: {$succeeded}, Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
