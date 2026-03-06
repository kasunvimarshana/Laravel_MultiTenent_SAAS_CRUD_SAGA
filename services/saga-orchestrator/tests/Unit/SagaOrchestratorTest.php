<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\SagaException;
use App\Interfaces\MessageBrokerInterface;
use App\Interfaces\SagaStepInterface;
use App\Interfaces\SagaTransactionRepositoryInterface;
use App\Models\SagaStep;
use App\Models\SagaTransaction;
use App\Services\SagaOrchestrator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SagaOrchestrator.
 *
 * All dependencies are mocked so no database or message broker is required.
 */
class SagaOrchestratorTest extends TestCase
{
    private SagaOrchestrator $orchestrator;

    /** @var SagaTransactionRepositoryInterface&MockInterface */
    private MockInterface $repository;

    /** @var MessageBrokerInterface&MockInterface */
    private MockInterface $broker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(SagaTransactionRepositoryInterface::class);
        $this->broker     = Mockery::mock(MessageBrokerInterface::class);

        $this->orchestrator = new SagaOrchestrator($this->repository, $this->broker);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // startSaga
    // -----------------------------------------------------------------------

    public function test_start_saga_throws_for_unknown_type(): void
    {
        $this->expectException(SagaException::class);
        $this->expectExceptionMessage("Unknown saga type: 'unknown_saga'");

        $this->orchestrator->startSaga('unknown_saga', []);
    }

    public function test_start_saga_creates_transaction_and_executes_first_step(): void
    {
        $step = $this->makeStep('step_one');

        $this->orchestrator->registerSteps('test_saga', [$step]);

        $saga = $this->makeSagaTransaction([
            'saga_id'         => 'saga-uuid-001',
            'saga_type'       => 'test_saga',
            'status'          => SagaTransaction::STATUS_RUNNING,
            'payload'         => ['key' => 'value'],
            'completed_steps' => [],
        ]);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->andReturn($saga);

        $this->repository
            ->shouldReceive('update')
            ->andReturn($saga);

        $stepsRelation = Mockery::mock(HasMany::class);
        $stepsRelation->shouldReceive('orderBy')->andReturnSelf();
        $stepsRelation->shouldReceive('create')->andReturn(new SagaStep());
        $stepsRelation->shouldReceive('where')->andReturnSelf();
        $stepsRelation->shouldReceive('update')->andReturn(1);

        $saga->shouldReceive('steps')->andReturn($stepsRelation);
        $saga->shouldReceive('fresh')->andReturn($saga);
        $saga->shouldReceive('refresh')->andReturn($saga);

        $step->shouldReceive('execute')->once()->andReturn([]);

        $result = $this->orchestrator->startSaga('test_saga', ['key' => 'value']);

        $this->assertSame($saga, $result);
    }

    // -----------------------------------------------------------------------
    // processSagaEvent
    // -----------------------------------------------------------------------

    public function test_process_saga_event_throws_when_saga_not_found(): void
    {
        $this->repository
            ->shouldReceive('findBySagaId')
            ->with('missing-id')
            ->andReturnNull();

        $this->expectException(SagaException::class);
        $this->expectExceptionMessage('Saga not found: missing-id');

        $this->orchestrator->processSagaEvent('missing-id', 'some_event', []);
    }

    public function test_process_saga_event_returns_saga_unchanged_when_terminal(): void
    {
        $saga = $this->makeSagaTransaction([
            'status'          => SagaTransaction::STATUS_COMPLETED,
            'completed_steps' => [],
        ]);

        $this->repository
            ->shouldReceive('findBySagaId')
            ->with('saga-123')
            ->andReturn($saga);

        $result = $this->orchestrator->processSagaEvent('saga-123', 'any_event', []);

        $this->assertSame($saga, $result);
    }

    public function test_process_saga_event_triggers_compensation_on_failure_event(): void
    {
        $step = $this->makeStep('step_one');

        $this->orchestrator->registerSteps('test_saga', [$step]);

        $saga = $this->makeSagaTransaction([
            'saga_id'         => 'saga-456',
            'saga_type'       => 'test_saga',
            'status'          => SagaTransaction::STATUS_RUNNING,
            'current_step'    => 'step_one',
            'completed_steps' => [],
            'payload'         => [],
        ]);

        $this->repository
            ->shouldReceive('findBySagaId')
            ->with('saga-456')
            ->andReturn($saga);

        $this->repository->shouldReceive('update')->andReturn($saga);

        $stepsRelation = Mockery::mock(HasMany::class);
        $stepsRelation->shouldReceive('where')->andReturnSelf();
        $stepsRelation->shouldReceive('update')->andReturn(1);
        $stepsRelation->shouldReceive('first')->andReturn(null);

        $saga->shouldReceive('steps')->andReturn($stepsRelation);
        $saga->shouldReceive('fresh')->andReturn($saga);
        $saga->shouldReceive('refresh')->andReturn($saga);

        $step->shouldReceive('compensate')->once();

        $this->orchestrator->processSagaEvent('saga-456', 'step_one_failed', ['error' => 'timeout']);
    }

    // -----------------------------------------------------------------------
    // compensateSaga
    // -----------------------------------------------------------------------

    public function test_compensate_saga_throws_when_not_found(): void
    {
        $this->repository
            ->shouldReceive('findBySagaId')
            ->andReturnNull();

        $this->expectException(SagaException::class);

        $this->orchestrator->compensateSaga('missing', 'test');
    }

    public function test_compensate_saga_skips_when_already_compensated(): void
    {
        $saga = $this->makeSagaTransaction([
            'status'          => SagaTransaction::STATUS_COMPENSATED,
            'completed_steps' => [],
        ]);

        $this->repository
            ->shouldReceive('findBySagaId')
            ->andReturn($saga);

        $result = $this->orchestrator->compensateSaga('saga-789', 'already done');

        $this->assertSame($saga, $result);
    }

    // -----------------------------------------------------------------------
    // getSagaStatus
    // -----------------------------------------------------------------------

    public function test_get_saga_status_throws_when_not_found(): void
    {
        $this->repository
            ->shouldReceive('findBySagaId')
            ->andReturnNull();

        $this->expectException(SagaException::class);

        $this->orchestrator->getSagaStatus('missing');
    }

    public function test_get_saga_status_loads_steps(): void
    {
        $saga = $this->makeSagaTransaction(['status' => SagaTransaction::STATUS_RUNNING]);

        $this->repository
            ->shouldReceive('findBySagaId')
            ->andReturn($saga);

        $saga->shouldReceive('load')->with('steps')->andReturn($saga);

        $result = $this->orchestrator->getSagaStatus('any-id');

        $this->assertSame($saga, $result);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Create a partial mock of SagaTransaction.
     *
     * @param  array<string, mixed> $attributes
     * @return SagaTransaction&MockInterface
     */
    private function makeSagaTransaction(array $attributes = []): SagaTransaction
    {
        /** @var SagaTransaction&MockInterface $mock */
        $mock = Mockery::mock(SagaTransaction::class)->makePartial();

        foreach ($attributes as $key => $value) {
            $mock->{$key} = $value;
        }

        return $mock;
    }

    /**
     * Create a mock SagaStepInterface with the given name.
     *
     * @return SagaStepInterface&MockInterface
     */
    private function makeStep(string $name): MockInterface
    {
        $step = Mockery::mock(SagaStepInterface::class);
        $step->shouldReceive('getName')->andReturn($name);
        $step->shouldReceive('getTimeout')->andReturn(30);
        return $step;
    }
}
