<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Helpers\Upgrade\CheckStatus;
use Webkul\Installer\Helpers\Upgrade\PostUpgradeVerifier;

/**
 * A clean `migrate` exit only proves no statement threw. These assertions cover
 * the failures this release can produce silently — associations that never
 * backfilled, integrations left owned by a person, credentials still in audit
 * history.
 */
function verifierResult(string $name): ?object
{
    return collect(app(PostUpgradeVerifier::class)->run())->firstWhere('name', $name);
}

/**
 * The seeded fixture ships records these checks are designed to catch, so each
 * test starts from a verified-clean baseline and reintroduces only its own.
 */
function clearVerifierBaseline(): void
{
    DB::table('api_keys')->update(['revoked' => true]);

    DB::table('audits')->where('auditable_type', 'like', '%\\\\Admin')->delete();
}

it('fails when legacy association data survived without being migrated', function () {
    $product = ProductProxy::factory()->create();

    DB::table('product_associations')->delete();

    DB::table('products')->where('id', $product->id)->update([
        'values' => json_encode([
            'associations' => [
                'related_products' => ['some-other-sku'],
            ],
        ]),
    ]);

    $check = verifierResult(trans('installer::app.upgrade.verify.associations'));

    expect($check->status)->toBe(CheckStatus::Failed)
        ->and($check->remedy)->not->toBe('');
});

it('passes the association check once normalised rows exist', function () {
    $products = ProductProxy::factory()->count(2)->create();

    DB::table('products')->where('id', $products[0]->id)->update([
        'values' => json_encode(['associations' => ['related_products' => ['sku']]]),
    ]);

    $type = DB::table('association_types')->first();

    expect($type)->not->toBeNull();

    DB::table('product_associations')->insertOrIgnore([
        'product_id'          => $products[0]->id,
        'association_type_id' => $type->id,
        'related_product_id'  => $products[1]->id,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    expect(verifierResult(trans('installer::app.upgrade.verify.associations'))->status)
        ->toBe(CheckStatus::Passed);
});

it('fails when integrations are still owned by a human administrator', function () {
    clearVerifierBaseline();

    $admin = DB::table('admins')->where('type', '!=', 'api')->first();

    expect($admin)->not->toBeNull();

    DB::table('api_keys')->insert([
        'name'            => 'upgrade-test-key',
        'admin_id'        => $admin->id,
        'permission_type' => 'all',
        'revoked'         => false,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $check = verifierResult(trans('installer::app.upgrade.verify.robot-users'));

    expect($check->status)->toBe(CheckStatus::Failed)
        ->and($check->remedy)->not->toBe('');
});

it('passes the integration owner check when no live keys belong to a person', function () {
    clearVerifierBaseline();

    expect(verifierResult(trans('installer::app.upgrade.verify.robot-users'))->status)
        ->toBe(CheckStatus::Passed);
});

it('fails when category nested set bounds are invalid', function () {
    $category = DB::table('categories')->first();

    expect($category)->not->toBeNull();

    DB::table('categories')->where('id', $category->id)->update([
        '_lft' => 10,
        '_rgt' => 5,
    ]);

    expect(verifierResult(trans('installer::app.upgrade.verify.category-bounds'))->status)
        ->toBe(CheckStatus::Failed);
});

it('fails when administrator audit history still holds credentials', function () {
    clearVerifierBaseline();

    DB::table('audits')->insert([
        'user_type'      => 'Webkul\User\Models\Admin',
        'event'          => 'updated',
        'auditable_type' => 'Webkul\User\Models\Admin',
        'auditable_id'   => 1,
        'old_values'     => json_encode(['password' => 'hashed-secret']),
        'new_values'     => json_encode(['password' => 'another-hash']),
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    $check = verifierResult(trans('installer::app.upgrade.verify.scrubbed-audits'));

    expect($check->status)->toBe(CheckStatus::Failed)
        ->and($check->detail)->toContain('1');
});

it('passes every check on a correctly migrated installation', function () {
    clearVerifierBaseline();

    $failed = collect(app(PostUpgradeVerifier::class)->run())
        ->filter(fn (object $result): bool => $result->status === CheckStatus::Failed);

    expect($failed)->toBeEmpty();
});
