<?php

namespace Webkul\User\Tests\Concerns;

use Illuminate\Support\Facades\Hash;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Models\Admin;

trait UserAssertions
{
    /**
     * Assert model wise.
     */
    public function assertModelWise(array $modelWiseAssertions): void
    {
        foreach ($modelWiseAssertions as $modelClassName => $modelAssertions) {
            foreach ($modelAssertions as $assertion) {
                $this->assertDatabaseHas(app($modelClassName)->getTable(), $assertion);
            }
        }
    }

    public function loginAsAdmin(?AdminContract $admin = null): AdminContract
    {
        $admin = $admin ?? Admin::factory()->create([
            'password'     => Hash::make('password'),
            'ui_locale_id' => 1,
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
