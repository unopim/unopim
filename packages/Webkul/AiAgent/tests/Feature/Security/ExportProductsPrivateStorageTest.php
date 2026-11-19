<?php

use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\ExportProducts;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function privateExportToolRun(string $format): array
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['data_transfer', 'data_transfer.export', 'data_transfer.export.execute'],
    ]);

    $user = Admin::factory()->create(['role_id' => $role->id]);

    $context = new ChatContext(
        message: 'Export products',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        user: $user,
    );

    $result = json_decode(app(ExportProducts::class)->register($context)->handle(new Request([
        'status' => 'all',
        'format' => $format,
    ])), true, 512, JSON_THROW_ON_ERROR);

    return [$result, $user];
}

it('writes the ai export to the private disk and links it through the authenticated download route', function (string $format) {
    Storage::fake('private');
    Storage::fake('public');

    Product::factory()->simple()->create();

    [$result, $user] = privateExportToolRun($format);

    $downloadUrl = $result['download_url'];
    $file = basename((string) parse_url($downloadUrl, PHP_URL_PATH));

    expect($downloadUrl)->toBe(route('ai-agent.exports.download', ['file' => $file]))
        ->and($downloadUrl)->not->toContain('/storage/')
        ->and($file)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.'.$format.'$/');

    Storage::disk('private')->assertExists(ExportProducts::DIRECTORY.'/'.$user->id.'/'.$file);

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
})->with(['csv', 'xlsx']);
