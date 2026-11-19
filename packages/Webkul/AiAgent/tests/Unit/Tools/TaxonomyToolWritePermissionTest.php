<?php

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Chat\Tools\CreateAttribute;
use Webkul\AiAgent\Chat\Tools\ManageFamilies;
use Webkul\AiAgent\Chat\Tools\ManageOptions;
use Webkul\Attribute\Models\Attribute;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function taxonomyToolContext(array $permissions): ChatContext
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    return new ChatContext(
        message: 'Manage taxonomy',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        uploadedImagePaths: [],
        uploadedFilePaths: [],
        currentPage: null,
        user: Admin::factory()->create(['role_id' => $role->id]),
    );
}

function runTaxonomyTool(string $tool, ChatContext $context, array $arguments): array
{
    return json_decode(app($tool)->register($context)->handle(new Request($arguments)), true, 512, JSON_THROW_ON_ERROR);
}

it('denies create_attribute to a user who can only view attributes', function () {
    $context = taxonomyToolContext(['catalog', 'catalog.attributes']);

    $result = runTaxonomyTool(CreateAttribute::class, $context, ['code' => 'ai_acl_blocked', 'name' => 'Blocked', 'type' => 'text']);

    expect($result['error'])->toContain('catalog.attributes.create')
        ->and(DB::table('attributes')->where('code', 'ai_acl_blocked')->exists())->toBeFalse();
});

it('allows create_attribute for a user with the create permission', function () {
    $context = taxonomyToolContext(['catalog', 'catalog.attributes', 'catalog.attributes.create']);

    $result = runTaxonomyTool(CreateAttribute::class, $context, ['code' => 'ai_acl_allowed', 'name' => 'Allowed', 'type' => 'text']);

    expect($result['result']['created'])->toBeTrue()
        ->and(DB::table('attributes')->where('code', 'ai_acl_allowed')->exists())->toBeTrue();
});

it('does not offer create_attribute to a user without the create permission', function () {
    $names = array_map(
        fn ($tool) => $tool->name(),
        app(ToolRegistry::class)->build(taxonomyToolContext(['catalog', 'catalog.attributes'])),
    );

    expect($names)->toContain('list_attributes')
        ->not->toContain('create_attribute');
});

it('denies adding attribute options to a user who can only view attributes', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $context = taxonomyToolContext(['catalog', 'catalog.attributes']);

    $result = runTaxonomyTool(ManageOptions::class, $context, ['attribute_code' => $attribute->code, 'action' => 'add', 'options' => 'AclBlocked']);

    expect($result['error'])->toContain('catalog.attributes.edit')
        ->and(DB::table('attribute_options')->where('attribute_id', $attribute->id)->where('code', 'aclblocked')->exists())->toBeFalse();
});

it('still lists attribute options for a user who can only view attributes', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $context = taxonomyToolContext(['catalog', 'catalog.attributes']);

    $result = runTaxonomyTool(ManageOptions::class, $context, ['attribute_code' => $attribute->code, 'action' => 'list']);

    expect($result)->toHaveKey('options');
});

it('denies creating a family to a user who can only view families', function () {
    $context = taxonomyToolContext(['catalog', 'catalog.families']);

    $result = runTaxonomyTool(ManageFamilies::class, $context, ['action' => 'create', 'code' => 'ai_acl_family', 'name' => 'Blocked']);

    expect($result['error'])->toContain('catalog.families.create')
        ->and(DB::table('attribute_families')->where('code', 'ai_acl_family')->exists())->toBeFalse();
});

it('allows creating a family for a user with the create permission', function () {
    $context = taxonomyToolContext(['catalog', 'catalog.families', 'catalog.families.create']);

    $result = runTaxonomyTool(ManageFamilies::class, $context, ['action' => 'create', 'code' => 'ai_acl_family_ok', 'name' => 'Allowed']);

    expect($result['result']['created'])->toBeTrue();
});
