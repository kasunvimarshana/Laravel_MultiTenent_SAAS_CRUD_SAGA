<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * TenantSeeder – seeds sample tenants for development and testing.
 */
class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name'          => 'Acme Corporation',
                'domain'        => 'acme',
                'database_name' => 'tenant_acme',
                'is_active'     => true,
                'api_token'     => Hash::make('acme-secret-token-001'),
                'settings'      => [
                    'currency'               => 'USD',
                    'notification_channels'  => ['email', 'sms'],
                    'max_order_value'        => 10000,
                ],
            ],
            [
                'name'          => 'Globex Industries',
                'domain'        => 'globex',
                'database_name' => 'tenant_globex',
                'is_active'     => true,
                'api_token'     => Hash::make('globex-secret-token-002'),
                'settings'      => [
                    'currency'               => 'EUR',
                    'notification_channels'  => ['email'],
                    'max_order_value'        => 50000,
                ],
            ],
            [
                'name'          => 'Initech LLC',
                'domain'        => 'initech',
                'database_name' => 'tenant_initech',
                'is_active'     => false,
                'api_token'     => Hash::make('initech-secret-token-003'),
                'settings'      => null,
            ],
        ];

        foreach ($tenants as $data) {
            Tenant::updateOrCreate(
                ['domain' => $data['domain']],
                $data,
            );
        }

        $this->command->info('TenantSeeder: seeded ' . count($tenants) . ' tenants.');
    }
}
