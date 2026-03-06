<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $quantity  = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 5, 500);

        return [
            'id'          => Uuid::uuid4()->toString(),
            'tenant_id'   => Tenant::factory(),
            'saga_id'     => Uuid::uuid4()->toString(),
            'customer_id' => Uuid::uuid4()->toString(),
            'status'      => $this->faker->randomElement(Order::$statuses),
            'items'       => [
                [
                    'product_id' => Uuid::uuid4()->toString(),
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                ],
            ],
            'total_amount'     => $quantity * $unitPrice,
            'currency'         => 'USD',
            'shipping_address' => [
                'street'  => $this->faker->streetAddress(),
                'city'    => $this->faker->city(),
                'state'   => $this->faker->stateAbbr(),
                'zip'     => $this->faker->postcode(),
                'country' => 'US',
            ],
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => Order::STATUS_PENDING]);
    }

    public function confirmed(): static
    {
        return $this->state([
            'status'       => Order::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status'       => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}
