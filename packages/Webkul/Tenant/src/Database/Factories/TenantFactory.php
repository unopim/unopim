<?php

namespace Webkul\Tenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\Tenant\Models\Tenant;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'uuid'          => Str::uuid()->toString(),
            'name'          => $name,
            'domain'        => Str::slug($name),
            'status'        => Tenant::STATUS_ACTIVE,
            'settings'      => null,
            'es_index_uuid' => Str::uuid()->toString(),
        ];
    }

    /**
     * State: provisioning status.
     */
    public function provisioning(): static
    {
        return $this->state(fn () => ['status' => Tenant::STATUS_PROVISIONING]);
    }

    /**
     * State: suspended status.
     */
    public function suspended(): static
    {
        return $this->state(fn () => ['status' => Tenant::STATUS_SUSPENDED]);
    }
}
