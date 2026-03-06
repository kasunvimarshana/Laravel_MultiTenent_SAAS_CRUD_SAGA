<?php
declare(strict_types=1);
namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        $onHand = $this->faker->numberBetween(0, 500);
        $reserved = $this->faker->numberBetween(0, (int) ($onHand * 0.2));
        return [
            'id'                 => Uuid::uuid4()->toString(),
            'product_id'         => Product::factory(),
            'tenant_id'          => Tenant::factory(),
            'warehouse_id'       => Warehouse::factory(),
            'quantity_available' => $onHand - $reserved,
            'quantity_reserved'  => $reserved,
            'quantity_on_hand'   => $onHand,
            'last_updated_at'    => now(),
        ];
    }
}
