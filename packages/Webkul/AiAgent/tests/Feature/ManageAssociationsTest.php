<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\ManageAssociations;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Contracts\AssociationType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Task 7: dynamic AI `manage_associations` tool.
 *
 * Proves the AI tool supports any active association type (not just the 3
 * legacy sections) with per-link `additional_data`, and actually writes the
 * `product_associations` link table (previously it only wrote the legacy
 * `values['associations']` JSON and only understood 3 hardcoded types).
 */
function seedBundleKitAssociationTypeForAiTool(): AssociationType
{
    return app(AssociationTypeRepository::class)->create([
        'code'            => 'bundle_kit_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Bundle Kit'],
        'fields'          => [
            [
                'code'        => 'quantity',
                'type'        => 'text',
                'validation'  => 'number',
                'is_required' => 1,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Quantity'],
            ],
        ],
    ]);
}

function adminWithProductEditPermissionForAiTool(): Admin
{
    $role = Role::factory()->create([
        'permission_type'  => 'custom',
        'permissions'      => ['catalog', 'catalog.products', 'catalog.products.edit'],
    ]);

    return Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $role->id,
    ]);
}

function buildManageAssociationsChatContext(Admin $admin, ?string $productSku = null): ChatContext
{
    return new ChatContext(
        message: 'Manage associations',
        history: [],
        productId: null,
        productSku: $productSku,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform([
            'provider' => 'openai',
            'models'   => 'gpt-4o',
        ]),
        model: 'gpt-4o',
        uploadedImagePaths: [],
        uploadedFilePaths: [],
        currentPage: null,
        user: $admin,
    );
}

function decodeManageAssociationsResult(string $result): array
{
    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
}

it('adds a custom bundle_kit association with a quantity via associations_json in append mode', function () {
    $bundleKitType = seedBundleKitAssociationTypeForAiTool();

    $product = Product::factory()->simple()->create();
    $related = Product::factory()->simple()->create();

    $context = buildManageAssociationsChatContext(adminWithProductEditPermissionForAiTool(), $product->sku);

    $associationsJson = json_encode([
        [
            'association_type' => $bundleKitType->code,
            'related_sku'      => $related->sku,
            'additional_data'  => ['quantity' => '5'],
        ],
    ]);

    $result = decodeManageAssociationsResult(
        app(ManageAssociations::class)->register($context)->handle(new Request([
            'sku'               => $product->sku,
            'associations_json' => $associationsJson,
            'mode'              => 'append',
        ]))
    );

    expect($result['error'] ?? null)->toBeNull();

    $row = DB::table('product_associations')
        ->where('product_id', $product->id)
        ->where('association_type_id', $bundleKitType->id)
        ->where('related_product_id', $related->id)
        ->first();

    expect($row)->not->toBeNull();
    expect(json_decode($row->additional_data, true))->toBe(['common' => ['quantity' => '5']]);
});

it('still supports the legacy up_sells scalar param, dual-writing JSON and the link table', function () {
    $product = Product::factory()->simple()->create();
    $upSell = Product::factory()->simple()->create();

    $context = buildManageAssociationsChatContext(adminWithProductEditPermissionForAiTool(), $product->sku);

    $result = decodeManageAssociationsResult(
        app(ManageAssociations::class)->register($context)->handle(new Request([
            'sku'      => $product->sku,
            'up_sells' => $upSell->sku,
            'mode'     => 'append',
        ]))
    );

    expect($result['error'] ?? null)->toBeNull();

    $upSellsType = app(AssociationTypeRepository::class)->findByCode('up_sells');

    expect(
        DB::table('product_associations')
            ->where('product_id', $product->id)
            ->where('association_type_id', $upSellsType->id)
            ->where('related_product_id', $upSell->id)
            ->exists()
    )->toBeTrue();

    $product->refresh();

    expect($product->values['associations']['up_sells'] ?? null)->toBe([$upSell->sku]);
});

it('replace mode sets a type\'s links to exactly the given set, dropping previous links', function () {
    $product = Product::factory()->simple()->create();
    $oldUpSell = Product::factory()->simple()->create();
    $newUpSell = Product::factory()->simple()->create();

    $context = buildManageAssociationsChatContext(adminWithProductEditPermissionForAiTool(), $product->sku);

    // First call (append) establishes an existing link.
    decodeManageAssociationsResult(
        app(ManageAssociations::class)->register($context)->handle(new Request([
            'sku'      => $product->sku,
            'up_sells' => $oldUpSell->sku,
            'mode'     => 'append',
        ]))
    );

    // Second call (replace) should drop the old link and only keep the new one.
    $result = decodeManageAssociationsResult(
        app(ManageAssociations::class)->register($context)->handle(new Request([
            'sku'      => $product->sku,
            'up_sells' => $newUpSell->sku,
            'mode'     => 'replace',
        ]))
    );

    expect($result['error'] ?? null)->toBeNull();

    $upSellsType = app(AssociationTypeRepository::class)->findByCode('up_sells');

    expect(
        DB::table('product_associations')
            ->where('product_id', $product->id)
            ->where('association_type_id', $upSellsType->id)
            ->where('related_product_id', $oldUpSell->id)
            ->exists()
    )->toBeFalse();

    expect(
        DB::table('product_associations')
            ->where('product_id', $product->id)
            ->where('association_type_id', $upSellsType->id)
            ->where('related_product_id', $newUpSell->id)
            ->exists()
    )->toBeTrue();

    $product->refresh();

    expect($product->values['associations']['up_sells'] ?? null)->toBe([$newUpSell->sku]);
});

it('returns a tool error and persists nothing when additional_data fails validation', function () {
    $bundleKitType = seedBundleKitAssociationTypeForAiTool();

    $product = Product::factory()->simple()->create();
    $related = Product::factory()->simple()->create();

    $context = buildManageAssociationsChatContext(adminWithProductEditPermissionForAiTool(), $product->sku);

    $associationsJson = json_encode([
        [
            'association_type' => $bundleKitType->code,
            'related_sku'      => $related->sku,
            'additional_data'  => ['quantity' => 'not-a-number'],
        ],
    ]);

    $result = decodeManageAssociationsResult(
        app(ManageAssociations::class)->register($context)->handle(new Request([
            'sku'               => $product->sku,
            'associations_json' => $associationsJson,
            'mode'              => 'append',
        ]))
    );

    expect($result['error'] ?? null)->not->toBeNull();

    expect(
        DB::table('product_associations')
            ->where('product_id', $product->id)
            ->where('association_type_id', $bundleKitType->id)
            ->exists()
    )->toBeFalse();
});

it('denies the tool when the user lacks catalog.products.edit permission', function () {
    $product = Product::factory()->simple()->create();
    $related = Product::factory()->simple()->create();

    $role = Role::factory()->create([
        'permission_type'  => 'custom',
        'permissions'      => ['catalog', 'catalog.products'],
    ]);
    $admin = Admin::factory()->create(['password' => Hash::make('password'), 'role_id' => $role->id]);

    $context = buildManageAssociationsChatContext($admin, $product->sku);

    $result = decodeManageAssociationsResult(
        app(ManageAssociations::class)->register($context)->handle(new Request([
            'sku'      => $product->sku,
            'up_sells' => $related->sku,
        ]))
    );

    expect($result['error'])->toContain('catalog.products.edit');
});
