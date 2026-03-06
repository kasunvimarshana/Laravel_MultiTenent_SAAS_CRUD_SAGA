<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'id'                  => Uuid::uuid4()->toString(),
            'tenant_id'           => Tenant::factory(),
            'sku'                 => strtoupper($this->faker->bothify('??-####')),
            'name'                => $this->faker->words(3, true),
            'description'         => $this->faker->sentence(),
            'category'            => $this->faker->randomElement(['electronics', 'clothing', 'food', 'tools']),
            'unit_of_measure'     => $this->faker->randomElement(['unit', 'kg', 'litre', 'box']),
            'minimum_stock_level' => $this->faker->numberBetween(5, 50),
            'is_active'           => true,
            'attributes'          => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
