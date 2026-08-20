<?php

use Illuminate\Testing\TestResponse;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Models\MagicPrompt;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

beforeEach(fn () => config(['elasticsearch.enabled' => false]));

/**
 * @return array{0: string, 1: array<int, string>, 2: string, 3: callable, 4: string}
 */
dataset('editable datagrids', [
    'products' => [
        'admin.catalog.products.index',
        ['catalog', 'catalog.products'],
        'catalog.products.edit',
        fn () => Product::factory()->simple()->create()->id,
        'admin.catalog.products.edit',
    ],

    'categories' => [
        'admin.catalog.categories.index',
        ['catalog', 'catalog.categories'],
        'catalog.categories.edit',
        fn () => Category::factory()->create()->id,
        'admin.catalog.categories.edit',
    ],

    'attributes' => [
        'admin.catalog.attributes.index',
        ['catalog', 'catalog.attributes'],
        'catalog.attributes.edit',
        fn () => Attribute::factory()->create()->id,
        'admin.catalog.attributes.edit',
    ],

    'attribute families' => [
        'admin.catalog.families.index',
        ['catalog', 'catalog.families'],
        'catalog.families.edit',
        fn () => AttributeFamily::factory()->create()->id,
        'admin.catalog.families.edit',
    ],

    'locales' => [
        'admin.settings.locales.index',
        ['settings', 'settings.locales'],
        'settings.locales.edit',
        fn () => Locale::query()->value('id') ?? Locale::factory()->create()->id,
        'admin.settings.locales.edit',
    ],

    'currencies' => [
        'admin.settings.currencies.index',
        ['settings', 'settings.currencies'],
        'settings.currencies.edit',
        fn () => Currency::query()->value('id') ?? Currency::factory()->create()->id,
        'admin.settings.currencies.edit',
    ],

    'users' => [
        'admin.settings.users.index',
        ['settings', 'settings.users', 'settings.users.users'],
        'settings.users.users.edit',
        fn () => Admin::query()->value('id'),
        'admin.settings.users.edit',
    ],

    'magic ai prompts' => [
        'admin.magic_ai.prompt.index',
        ['ai-agent', 'ai-agent.prompt'],
        'ai-agent.prompt.edit',
        fn () => MagicPrompt::factory()->create()->id,
        'admin.magic_ai.prompt.edit',
    ],

    'magic ai system prompts' => [
        'admin.magic_ai.system_prompt.index',
        ['ai-agent', 'ai-agent.system-prompt'],
        'ai-agent.system-prompt.edit',
        fn () => MagicAISystemPrompt::factory()->create()->id,
        'admin.magic_ai.system_prompt.edit',
    ],
]);

function fetchDatagrid(string $routeName): TestResponse
{
    return test()->get(
        route($routeName),
        ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']
    );
}

it('does not expose a row edit action when the edit permission is missing', function (
    string $indexRoute,
    array $viewPermissions,
    string $editPermission,
    callable $seed
) {
    $this->loginWithPermissions('custom', [...$viewPermissions, 'dashboard']);

    $seed();

    $records = fetchDatagrid($indexRoute)->assertOk()->json('records');

    expect($records)->toBeArray()->not->toBeEmpty();

    $indices = collect($records)->flatMap(fn (array $record) => collect($record['actions'] ?? [])->pluck('index'));

    expect($indices)->not->toContain('edit');
})->with('editable datagrids');

it('exposes a row edit action when the edit permission is granted', function (
    string $indexRoute,
    array $viewPermissions,
    string $editPermission,
    callable $seed
) {
    $this->loginWithPermissions('custom', [...$viewPermissions, 'dashboard', $editPermission]);

    $seed();

    $records = fetchDatagrid($indexRoute)->assertOk()->json('records');

    expect($records)->toBeArray()->not->toBeEmpty();

    $indices = collect($records)->flatMap(fn (array $record) => collect($record['actions'] ?? [])->pluck('index'));

    expect($indices)->toContain('edit');
})->with('editable datagrids');

it('never emits a row action without a usable url', function (
    string $indexRoute,
    array $viewPermissions,
    string $editPermission,
    callable $seed
) {
    $this->loginWithPermissions('custom', [...$viewPermissions, 'dashboard']);

    $seed();

    $records = fetchDatagrid($indexRoute)->assertOk()->json('records');

    $urls = collect($records)->flatMap(fn (array $record) => collect($record['actions'] ?? [])->pluck('url'));

    foreach ($urls as $url) {
        expect($url)->toBeString()->not->toBeEmpty();
        expect($url)->not->toContain('undefined');
    }
})->with('editable datagrids');

it('refuses the edit route when the edit permission is missing', function (
    string $indexRoute,
    array $viewPermissions,
    string $editPermission,
    callable $seed,
    string $editRoute
) {
    $this->loginWithPermissions('custom', [...$viewPermissions, 'dashboard']);

    $id = $seed();

    $this->get(route($editRoute, ['id' => $id]))->assertStatus(403);
})->with('editable datagrids');

it('does not make category tree nodes navigable without the edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories']);

    $this->get(route('admin.catalog.categories.index', ['view' => 'tree']))
        ->assertOk()
        ->assertSee('allow-edit="false"', escape: false);
});

it('makes category tree nodes navigable with the edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories', 'catalog.categories.edit']);

    $this->get(route('admin.catalog.categories.index', ['view' => 'tree']))
        ->assertOk()
        ->assertSee('allow-edit="true"', escape: false);
});

it('does not link the category overview tree rows without the edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories']);

    $root = Category::query()->whereNull('parent_id')->firstOrFail();

    $response = $this->get(route('admin.catalog.categories.index', ['view' => 'tree']))->assertOk();

    $response->assertSee(trans('admin::app.catalog.categories.browse.trees'), escape: false);

    $response->assertDontSee(
        route('admin.catalog.categories.index', ['category' => $root->id]),
        escape: false
    );
});

it('links the category overview tree rows with the edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories', 'catalog.categories.edit']);

    $root = Category::query()->whereNull('parent_id')->firstOrFail();

    $this->get(route('admin.catalog.categories.index', ['view' => 'tree']))
        ->assertOk()
        ->assertSee(
            route('admin.catalog.categories.index', ['category' => $root->id]),
            escape: false
        );
});

it('denies the category edit panel without the edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories']);

    $category = Category::factory()->create();

    $this->get(route('admin.catalog.categories.index', ['category' => $category->id]))
        ->assertStatus(403);
});

it('renders the category edit panel with the edit permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories', 'catalog.categories.edit']);

    $category = Category::factory()->create();

    $this->get(route('admin.catalog.categories.index', ['category' => $category->id]))
        ->assertOk()
        ->assertSee(trans('admin::app.catalog.categories.edit.title'), escape: false);
});

it('opens the create panel for a user who may only create categories', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories', 'catalog.categories.create']);

    $this->get(route('admin.catalog.categories.index', ['panel' => 'create']))
        ->assertOk()
        ->assertSee(trans('admin::app.catalog.categories.create.title'), escape: false);
});

it('denies the create panel without the create permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories']);

    $this->get(route('admin.catalog.categories.index', ['panel' => 'create']))
        ->assertStatus(403);
});

it('lands a create-only user on a page they can load after saving from the panel', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.categories', 'catalog.categories.create']);

    $localeCode = core()->getRequestedLocaleCode();

    $response = $this->post(route('admin.catalog.categories.store'), [
        'code'            => 'acl_panel_create_only',
        'parent_id'       => null,
        'panel'           => 1,
        'additional_data' => [
            'locale_specific' => [
                $localeCode => ['name' => 'ACL Panel Create Only'],
            ],
        ],
    ]);

    $response->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $response->assertRedirect(route('admin.catalog.categories.index'));

    $this->get($response->headers->get('Location'))->assertOk();
});

it('keeps sending an editor back into the panel after saving', function () {
    $this->loginWithPermissions('custom', [
        'dashboard',
        'catalog',
        'catalog.categories',
        'catalog.categories.create',
        'catalog.categories.edit',
    ]);

    $localeCode = core()->getRequestedLocaleCode();

    $response = $this->post(route('admin.catalog.categories.store'), [
        'code'            => 'acl_panel_editor',
        'parent_id'       => null,
        'panel'           => 1,
        'additional_data' => [
            'locale_specific' => [
                $localeCode => ['name' => 'ACL Panel Editor'],
            ],
        ],
    ]);

    $category = Category::query()->where('code', 'acl_panel_editor')->firstOrFail();

    $response->assertRedirect(route('admin.catalog.categories.index', [
        'category' => $category->id,
        'locale'   => $localeCode,
    ]));

    $this->get($response->headers->get('Location'))->assertOk();
});

it('does not index the product copy action as the row edit action', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.products', 'catalog.products.copy']);

    Product::factory()->simple()->create();

    $records = fetchDatagrid('admin.catalog.products.index')->assertOk()->json('records');

    $actions = collect($records)->flatMap(fn (array $record) => $record['actions'] ?? []);

    expect($actions->pluck('index'))->toContain('copy')->not->toContain('edit');
});
