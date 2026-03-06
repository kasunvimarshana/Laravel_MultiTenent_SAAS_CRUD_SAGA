<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'id'        => Uuid::uuid4()->toString(),
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . $this->faker->unique()->randomNumber(4),
            'api_key'   => Str::random(32),
            'is_active' => true,
            'settings'  => ['timezone' => 'UTC', 'currency' => 'USD'],
        ];
    }
}
