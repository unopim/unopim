<?php

use Webkul\Tenant\Services\TenantDemoSeeder;

echo "=== Seeding Demo Data for All Tenants ===\n\n";

$seeder = new TenantDemoSeeder();
$results = $seeder->seedAll();

foreach ($results as $tenantName => $data) {
    echo "--- {$tenantName} ---\n";

    if (isset($data['error'])) {
        echo "  ERROR: {$data['error']}\n";
        continue;
    }

    echo "  Categories: " . implode(', ', $data['categories']) . "\n";
    echo "  Products (" . count($data['products']) . "): " . implode(', ', $data['products']) . "\n";
    echo "  Additional Users:\n";
    foreach ($data['users'] as $user) {
        echo "    {$user['email']} / {$user['password']}\n";
    }
    echo "\n";
}

// Summary
echo "=== Database Summary ===\n";
$tenants = DB::table('tenants')->whereNull('deleted_at')->get();
foreach ($tenants as $t) {
    $products = DB::table('products')->where('tenant_id', $t->id)->count();
    $categories = DB::table('categories')->where('tenant_id', $t->id)->count();
    $admins = DB::table('admins')->where('tenant_id', $t->id)->count();
    $channels = DB::table('channels')->where('tenant_id', $t->id)->count();
    echo "{$t->name} (id={$t->id}): {$products} products, {$categories} categories, {$admins} users, {$channels} channels\n";
}

// Platform
$admins = DB::table('admins')->whereNull('tenant_id')->count();
echo "Platform: {$admins} platform operators\n";
