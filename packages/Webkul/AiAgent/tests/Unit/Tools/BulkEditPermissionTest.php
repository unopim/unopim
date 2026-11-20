<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\BulkEdit;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Build a chat context for a custom role.
 *
 * @param  array<int, string>  $permissions
 */
function bulkEditToolContext(array $permissions): ChatContext
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    return new ChatContext(
        message: 'Bulk edit products',
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

/**
 * Run the bulk_edit tool and decode its JSON answer.
 *
 * @return array<string, mixed>
 */
function runBulkEditTool(ChatContext $context): array
{
    return json_decode(app(BulkEdit::class)->register($context)->handle(new Request([
        'filter_by'    => 'status',
        'filter_value' => 'active',
        'changes_json' => json_encode(['name' => 'Overwritten']),
        'limit'        => 1,
    ])), true, 512, JSON_THROW_ON_ERROR);
}

it('denies bulk_edit to a user who can only toggle product status', function (): void {
    $result = runBulkEditTool(bulkEditToolContext(['catalog', 'catalog.products', 'catalog.products.mass_update']));

    expect($result['error'] ?? '')->toContain('catalog.products.edit');
});

it('allows bulk_edit for a user with the product edit permission', function (): void {
    $result = runBulkEditTool(bulkEditToolContext(['catalog', 'catalog.products', 'catalog.products.edit']));

    expect($result['error'] ?? '')->not->toContain('Permission denied');
});

it('registers bulk_edit under the product edit permission', function (): void {
    expect(config('ai-agent.tools.'.BulkEdit::class.'.permission'))->toBe('catalog.products.edit');
});
