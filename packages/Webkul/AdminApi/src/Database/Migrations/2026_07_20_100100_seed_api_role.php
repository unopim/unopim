<?php

use Illuminate\Database\Migrations\Migration;
use Webkul\AdminApi\Support\ApiRole;
use Webkul\User\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        ApiRole::ensure();
    }

    public function down(): void
    {
        Role::where('name', ApiRole::NAME)->delete();
    }
};
