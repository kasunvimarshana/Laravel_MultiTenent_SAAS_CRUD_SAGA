<?php
declare(strict_types=1);
namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::firstOrCreate(['slug' => 'acme'], [
            'name'      => 'ACME Corporation',
            'slug'      => 'acme',
            'domain'    => 'acme.example.com',
            'is_active' => true,
            'settings'  => ['currency' => 'USD', 'timezone' => 'UTC'],
        ]);

        Tenant::firstOrCreate(['slug' => 'globex'], [
            'name'      => 'Globex Inc.',
            'slug'      => 'globex',
            'domain'    => 'globex.example.com',
            'is_active' => true,
            'settings'  => ['currency' => 'EUR', 'timezone' => 'Europe/Berlin'],
        ]);
    }
}
