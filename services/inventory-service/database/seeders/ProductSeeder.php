<?php
declare(strict_types=1);
namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'acme')->first();
        if ($tenant === null) return;

        $warehouse = Warehouse::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Main Warehouse'],
            ['location' => '123 Industrial Ave', 'is_active' => true],
        );

        $products = [
            ['sku' => 'WIDGET-001', 'name' => 'Widget Alpha', 'category' => 'tools', 'minimum_stock_level' => 20],
            ['sku' => 'GADGET-002', 'name' => 'Gadget Beta',  'category' => 'electronics', 'minimum_stock_level' => 10],
            ['sku' => 'PART-003',   'name' => 'Part Gamma',   'category' => 'tools', 'minimum_stock_level' => 50],
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $productData['sku']],
                array_merge($productData, ['tenant_id' => $tenant->id, 'unit_of_measure' => 'unit', 'is_active' => true]),
            );

            Inventory::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                [
                    'tenant_id'          => $tenant->id,
                    'quantity_available' => 100,
                    'quantity_reserved'  => 0,
                    'quantity_on_hand'   => 100,
                    'last_updated_at'    => now(),
                ],
            );
        }
    }
}
