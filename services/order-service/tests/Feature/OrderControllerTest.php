<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the Order REST API.
 */
class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private array  $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant  = Tenant::factory()->create();
        $this->headers = [
            'X-Tenant-ID'   => $this->tenant->id,
            'Authorization' => "Bearer {$this->tenant->api_key}",
            'Accept'        => 'application/json',
        ];
    }

    /** @test */
    public function it_creates_an_order(): void
    {
        $payload = [
            'customer_id' => 'customer-001',
            'items'       => [
                ['product_id' => 'PROD-1', 'quantity' => 2, 'unit_price' => 25.00],
            ],
            'shipping_address' => [
                'street'  => '123 Main St',
                'city'    => 'Springfield',
                'state'   => 'IL',
                'zip'     => '62701',
                'country' => 'US',
            ],
            'currency' => 'USD',
        ];

        $response = $this->postJson('/api/orders', $payload, $this->headers);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'PENDING')
            ->assertJsonPath('customer_id', 'customer-001')
            ->assertJsonPath('currency', 'USD');
    }

    /** @test */
    public function it_rejects_invalid_order_payload(): void
    {
        $response = $this->postJson('/api/orders', [], $this->headers);

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'errors']);
    }

    /** @test */
    public function it_lists_orders_for_the_tenant(): void
    {
        Order::factory()->count(3)->create(['tenant_id' => $this->tenant->id, 'status' => 'PENDING']);

        $response = $this->getJson('/api/orders', $this->headers);

        $response->assertOk()
            ->assertJsonPath('total', 3);
    }

    /** @test */
    public function it_does_not_return_orders_of_other_tenants(): void
    {
        $otherTenant = Tenant::factory()->create();
        Order::factory()->count(2)->create(['tenant_id' => $otherTenant->id]);
        Order::factory()->count(1)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/orders', $this->headers);

        $response->assertOk()
            ->assertJsonPath('total', 1);
    }

    /** @test */
    public function it_shows_a_single_order(): void
    {
        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/orders/{$order->id}", $this->headers);

        $response->assertOk()
            ->assertJsonPath('id', $order->id);
    }

    /** @test */
    public function it_returns_404_for_unknown_order(): void
    {
        $response = $this->getJson('/api/orders/non-existent-id', $this->headers);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_updates_an_order(): void
    {
        $order = Order::factory()->pending()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'notes' => 'Please deliver in the morning.',
        ], $this->headers);

        $response->assertOk()
            ->assertJsonPath('notes', 'Please deliver in the morning.');
    }

    /** @test */
    public function it_cancels_an_order(): void
    {
        $order = Order::factory()->pending()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/orders/{$order->id}", ['reason' => 'Changed mind'], $this->headers);

        $response->assertOk()
            ->assertJsonPath('status', 'CANCELLED');
    }

    /** @test */
    public function it_cannot_cancel_a_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->headers);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_confirms_a_pending_order(): void
    {
        $order = Order::factory()->pending()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->postJson("/api/orders/{$order->id}/confirm", [], $this->headers);

        $response->assertOk()
            ->assertJsonPath('status', 'CONFIRMED');
    }

    /** @test */
    public function it_rejects_request_without_tenant_header(): void
    {
        $response = $this->getJson('/api/orders', ['Accept' => 'application/json']);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_rejects_request_with_invalid_token(): void
    {
        $response = $this->getJson('/api/orders', [
            'X-Tenant-ID'   => $this->tenant->id,
            'Authorization' => 'Bearer wrong-token',
            'Accept'        => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_health_check(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'order-service');
    }
}
