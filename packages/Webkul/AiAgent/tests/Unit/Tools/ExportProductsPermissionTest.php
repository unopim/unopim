<?php

use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\ExportProducts;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Build a chat context for a custom role.
 *
 * @param  array<int, string>  $permissions
 */
function exportToolContext(array $permissions): ChatContext
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    return new ChatContext(
        message: 'Export products',
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
 * Run the export_products tool and decode its JSON answer.
 *
 * @return array<string, mixed>
 */
function runExportTool(ChatContext $context): array
{
    return json_decode(app(ExportProducts::class)->register($context)->handle(new Request(['status' => 'all'])), true, 512, JSON_THROW_ON_ERROR);
}

it('denies export_products to a user who can only view exports', function (): void {
    $result = runExportTool(exportToolContext(['data_transfer', 'data_transfer.export']));

    expect($result['error'] ?? '')->toContain('data_transfer.export.execute');
});

it('allows export_products for a user with the export execute permission', function (): void {
    $result = runExportTool(exportToolContext(['data_transfer', 'data_transfer.export', 'data_transfer.export.execute']));

    expect($result['error'] ?? '')->not->toContain('Permission denied');
});

it('registers export_products under the export execute permission', function (): void {
    expect(config('ai-agent.tools.'.ExportProducts::class.'.permission'))->toBe('data_transfer.export.execute');
});
