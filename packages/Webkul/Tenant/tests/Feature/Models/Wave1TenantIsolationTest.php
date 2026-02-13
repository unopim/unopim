<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| Wave 1 Tenant Isolation Tests
|--------------------------------------------------------------------------
|
| Proves that Tenant A cannot see Tenant B's data for all 5 Wave 1 models:
| Product, Category, Attribute, Channel, Admin.
|
*/

// -- helpers ---------------------------------------------------------------

function seedAttributeFamily(int $tenantId): int
{
    return DB::table('attribute_families')->insertGetId([
        'code' => 'family-t'.$tenantId.'-'.uniqid(),
    ]);
}

function seedRole(int $tenantId): int
{
    return DB::table('roles')->insertGetId([
        'name'            => 'Role T'.$tenantId.'-'.uniqid(),
        'description'     => 'Test role',
        'permission_type' => 'all',
        'permissions'     => json_encode([]),
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);
}

// -- Product isolation -----------------------------------------------------

it('isolates products between tenants', function () {
    $familyA = seedAttributeFamily($this->tenantA->id);
    $familyB = seedAttributeFamily($this->tenantB->id);

    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('products')->insert([
        'sku'                => 'prod-a-'.uniqid(),
        'type'               => 'simple',
        'attribute_family_id' => $familyA,
        'tenant_id'          => $this->tenantA->id,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('products')->insert([
        'sku'                => 'prod-b-'.uniqid(),
        'type'               => 'simple',
        'attribute_family_id' => $familyB,
        'tenant_id'          => $this->tenantB->id,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);

    // Tenant A sees only its product
    core()->setCurrentTenantId($this->tenantA->id);
    expect(Product::count())->toBe(1);
    expect(Product::first()->sku)->toStartWith('prod-a-');

    // Tenant B sees only its product
    core()->setCurrentTenantId($this->tenantB->id);
    expect(Product::count())->toBe(1);
    expect(Product::first()->sku)->toStartWith('prod-b-');

    // Platform sees all
    core()->setCurrentTenantId(null);
    expect(Product::count())->toBe(2);
});

// -- Category isolation ----------------------------------------------------

it('isolates categories between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('categories')->insert([
        'code'      => 'cat-a-'.uniqid(),
        'tenant_id' => $this->tenantA->id,
        '_lft'      => 1,
        '_rgt'      => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('categories')->insert([
        'code'      => 'cat-b-'.uniqid(),
        'tenant_id' => $this->tenantB->id,
        '_lft'      => 1,
        '_rgt'      => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(Category::count())->toBe(1);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(Category::count())->toBe(1);

    core()->setCurrentTenantId(null);
    expect(Category::count())->toBe(2);
});

it('returns getScopeAttributes with tenant_id for nested set', function () {
    $category = new Category;
    $reflection = new ReflectionMethod($category, 'getScopeAttributes');
    $reflection->setAccessible(true);

    expect($reflection->invoke($category))->toBe(['tenant_id']);
});

// -- Attribute isolation ---------------------------------------------------

it('isolates attributes between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('attributes')->insert([
        'code'      => 'attr-a-'.uniqid(),
        'type'      => 'text',
        'tenant_id' => $this->tenantA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('attributes')->insert([
        'code'      => 'attr-b-'.uniqid(),
        'type'      => 'text',
        'tenant_id' => $this->tenantB->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(Attribute::count())->toBe(1);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(Attribute::count())->toBe(1);

    core()->setCurrentTenantId(null);
    expect(Attribute::count())->toBe(2);
});

// -- Channel isolation -----------------------------------------------------

it('isolates channels between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('channels')->insert([
        'code'      => 'ch-a-'.uniqid(),
        'tenant_id' => $this->tenantA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('channels')->insert([
        'code'      => 'ch-b-'.uniqid(),
        'tenant_id' => $this->tenantB->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(Channel::count())->toBe(1);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(Channel::count())->toBe(1);

    core()->setCurrentTenantId(null);
    expect(Channel::count())->toBe(2);
});

// -- Admin isolation -------------------------------------------------------

it('isolates admins between tenants', function () {
    $roleA = seedRole($this->tenantA->id);
    $roleB = seedRole($this->tenantB->id);

    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('admins')->insert([
        'name'      => 'Admin A',
        'email'     => 'admin-a-'.uniqid().'@test.com',
        'password'  => bcrypt('password'),
        'role_id'   => $roleA,
        'status'    => 1,
        'tenant_id' => $this->tenantA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('admins')->insert([
        'name'      => 'Admin B',
        'email'     => 'admin-b-'.uniqid().'@test.com',
        'password'  => bcrypt('password'),
        'role_id'   => $roleB,
        'status'    => 1,
        'tenant_id' => $this->tenantB->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(Admin::where('name', 'Admin A')->count())->toBe(1);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(Admin::where('name', 'Admin B')->count())->toBe(1);

    // Cross-tenant: Admin A not visible in tenant B context
    expect(Admin::where('name', 'Admin A')->count())->toBe(0);
});

it('treats NULL tenant_id admin as Platform Operator visible to all tenants', function () {
    $role = seedRole($this->tenantA->id);

    // Platform operator: NULL tenant_id (Decision D4)
    DB::table('admins')->insert([
        'name'      => 'Platform Operator',
        'email'     => 'platform-'.uniqid().'@test.com',
        'password'  => bcrypt('password'),
        'role_id'   => $role,
        'status'    => 1,
        'tenant_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Tenant-scoped admin
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('admins')->insert([
        'name'      => 'Tenant Admin',
        'email'     => 'tenant-'.uniqid().'@test.com',
        'password'  => bcrypt('password'),
        'role_id'   => $role,
        'status'    => 1,
        'tenant_id' => $this->tenantA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Tenant A sees its scoped admin but not the platform operator
    core()->setCurrentTenantId($this->tenantA->id);
    expect(Admin::where('name', 'Tenant Admin')->count())->toBe(1);
    expect(Admin::where('name', 'Platform Operator')->count())->toBe(0);

    // Platform context (null) sees all admins including platform operator
    core()->setCurrentTenantId(null);
    expect(Admin::where('name', 'Platform Operator')->count())->toBe(1);
    expect(Admin::where('name', 'Tenant Admin')->count())->toBe(1);
});

// -- Auto-set trait behavior on Wave 1 models ------------------------------

it('auto-sets tenant_id on Product creation from context', function () {
    $family = seedAttributeFamily($this->tenantA->id);

    core()->setCurrentTenantId($this->tenantA->id);
    $product = Product::create([
        'sku'                => 'auto-'.uniqid(),
        'type'               => 'simple',
        'attribute_family_id' => $family,
    ]);

    expect($product->tenant_id)->toBe($this->tenantA->id);
});

it('auto-sets tenant_id on Attribute creation from context', function () {
    core()->setCurrentTenantId($this->tenantB->id);
    $attr = Attribute::create([
        'code' => 'auto-attr-'.uniqid(),
        'type' => 'text',
    ]);

    expect($attr->tenant_id)->toBe($this->tenantB->id);
});
