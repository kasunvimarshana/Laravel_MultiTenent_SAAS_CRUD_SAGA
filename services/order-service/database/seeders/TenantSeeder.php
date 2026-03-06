<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'id'        => '11111111-1111-1111-1111-111111111111',
                'name'      => 'Acme Corporation',
                'slug'      => 'acme',
                'api_key'   => 'acme-secret-api-key-00000000000001',
                'is_active' => true,
                'settings'  => ['timezone' => 'UTC', 'currency' => 'USD'],
            ],
            [
                'id'        => '22222222-2222-2222-2222-222222222222',
                'name'      => 'Globex Inc',
                'slug'      => 'globex',
                'api_key'   => 'globex-secret-api-key-0000000000002',
                'is_active' => true,
                'settings'  => ['timezone' => 'America/New_York', 'currency' => 'USD'],
            ],
        ];

        foreach ($tenants as $data) {
            Tenant::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('TenantSeeder: 2 tenants seeded.');
    }
}
