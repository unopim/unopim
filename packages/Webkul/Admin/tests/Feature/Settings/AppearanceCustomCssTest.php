<?php

use Webkul\Admin\Helpers\CustomCss;
use Webkul\Core\Models\CoreConfig;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

function storedCustomCss(): ?string
{
    return CoreConfig::query()
        ->where('code', CustomCss::CONFIG_CODE)
        ->whereNull('channel_code')
        ->whereNull('locale_code')
        ->value('value');
}

it('shows the custom css field on the appearance page', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.appearance.index'))
        ->assertOk()
        ->assertSee('name="custom_css"', false)
        ->assertSeeText(trans('admin::app.settings.appearance.custom-css'));
});

it('saves custom css to the existing config key and renders it on admin pages', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.appearance.update'), [
        'custom_css' => '.box-shadow { border-radius: 12px; }',
    ])->assertRedirect(route('admin.settings.system.index'));

    expect(storedCustomCss())->toBe('.box-shadow { border-radius: 12px; }')
        ->and(core()->getConfigData(CustomCss::CONFIG_CODE))->toBe('.box-shadow { border-radius: 12px; }');

    get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('<style id="unopim-custom-css">'.PHP_EOL.'.box-shadow { border-radius: 12px; }'.PHP_EOL.'</style>', false);
});

it('renders custom css on the anonymous login page', function () {
    CoreConfig::query()->create([
        'code'  => CustomCss::CONFIG_CODE,
        'value' => '.login-card { color: red; }',
    ]);

    get(route('admin.session.create'))
        ->assertOk()
        ->assertSee('.login-card { color: red; }', false);
});

it('keeps child combinators intact', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.appearance.update'), [
        'custom_css' => 'nav > a { color: red; }',
    ])->assertRedirect();

    expect(storedCustomCss())->toBe('nav > a { color: red; }');
});

it('neutralises a style breakout attempt on save and on render', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.appearance.update'), [
        'custom_css' => 'a{color:red}</style><script>alert(1)</script><style>',
    ])->assertRedirect();

    $stored = storedCustomCss();

    expect($stored)->not->toContain('<')
        ->and($stored)->toContain('a{color:red}');

    get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('</style><script>', false);
});

it('neutralises a breakout written into the config directly', function () {
    CoreConfig::query()->create([
        'code'  => CustomCss::CONFIG_CODE,
        'value' => 'a{}</STYLE ><img src=x onerror=alert(1)>',
    ]);

    get(route('admin.session.create'))
        ->assertOk()
        ->assertDontSee('<img src=x onerror=alert(1)>', false)
        ->assertDontSee('</STYLE >', false);
});

it('unwraps a legacy value that carries its own style tags', function () {
    CoreConfig::query()->create([
        'code'  => CustomCss::CONFIG_CODE,
        'value' => '<style type="text/css">body { margin: 0; }</style>',
    ]);

    get(route('admin.session.create'))
        ->assertOk()
        ->assertSee('<style id="unopim-custom-css">'.PHP_EOL.'body { margin: 0; }'.PHP_EOL.'</style>', false)
        ->assertDontSee('<style type="text/css">', false);
});

it('omits the style element when no custom css is stored', function () {
    get(route('admin.session.create'))
        ->assertOk()
        ->assertDontSee('unopim-custom-css', false);
});

it('clears the stored custom css when the field is emptied', function () {
    $this->loginAsAdmin();

    CoreConfig::query()->create([
        'code'  => CustomCss::CONFIG_CODE,
        'value' => 'body { margin: 0; }',
    ]);

    put(route('admin.settings.appearance.update'), ['custom_css' => ''])->assertRedirect();

    expect(storedCustomCss())->toBeNull();
});

it('leaves stored custom css untouched when the field is not submitted', function () {
    $this->loginAsAdmin();

    CoreConfig::query()->create([
        'code'  => CustomCss::CONFIG_CODE,
        'value' => 'body { margin: 0; }',
    ]);

    put(route('admin.settings.appearance.update'), [])->assertRedirect();

    expect(storedCustomCss())->toBe('body { margin: 0; }');
});

it('rejects custom css longer than the maximum length', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.appearance.update'), [
        'custom_css' => str_repeat('a', CustomCss::MAX_LENGTH + 1),
    ])->assertSessionHasErrors('custom_css');

    expect(storedCustomCss())->toBeNull();
});

it('denies saving custom css without the appearance permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'configuration']);

    put(route('admin.settings.appearance.update'), [
        'custom_css' => 'body { display: none; }',
    ])->assertForbidden();

    expect(storedCustomCss())->toBeNull();
});

it('renders custom css through the shared component on every admin layout', function (string $layout) {
    $source = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/components/layouts/'.$layout));

    expect($source)->toContain('<x-admin::layouts.custom-css />')
        ->and($source)->not->toContain("getConfigData('general.content.custom_scripts.custom_css')");
})->with([
    'index.blade.php',
    'with-history/index.blade.php',
    'anonymous.blade.php',
]);

it('blocks external resource loading in custom css', function (string $css, string $forbidden) {
    $this->loginAsAdmin();

    put(route('admin.settings.appearance.update'), ['custom_css' => $css])->assertRedirect();

    expect(strtolower((string) storedCustomCss()))->not->toContain($forbidden);

    get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertDontSee($forbidden, false);
})->with([
    'import'           => ['@import "https://evil.test/a.css"; a{color:red}', '@import'],
    'import url'       => ["@IMPORT url('https://evil.test/a.css');", '@import'],
    'url function'     => ['a{background:url(https://evil.test/x.png)}', 'url('],
    'url with space'   => ['a{background:URL (https://evil.test/x.png)}', 'url ('],
    'escaped url'      => ['a{background:\\75 rl(https://evil.test/x.png)}', 'url('],
    'escaped import'   => ['@\\69mport "https://evil.test/a.css";', '@import'],
    'image-set'        => ['a{background:image-set("https://evil.test/x.png" 1x)}', 'image-set('],
    'webkit image-set' => ['a{background:-webkit-image-set("https://evil.test/x.png" 1x)}', 'image-set('],
]);

it('keeps unrelated css and escapes intact when blocking external resources', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.appearance.update'), [
        'custom_css' => '.a::before { content: "\\2022"; } .b { color: red; }',
    ])->assertRedirect();

    expect(storedCustomCss())->toBe('.a::before { content: "\\2022"; } .b { color: red; }');
});

it('blocks escaped resource loaders terminated by any line break', function (string $css) {
    $this->loginAsAdmin();

    put(route('admin.settings.appearance.update'), ['custom_css' => $css])->assertRedirect();

    expect((string) storedCustomCss())->not->toContain('\\');

    get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertDontSee('\\75', false)
        ->assertDontSee('\\69', false);
})->with([
    'url crlf'    => ["a{background:\\75\r\nrl(https://evil.test/x.png)}"],
    'url cr'      => ["a{background:\\75\rrl(https://evil.test/x.png)}"],
    'url lf'      => ["a{background:\\75\nrl(https://evil.test/x.png)}"],
    'url ff'      => ["a{background:\\75\frl(https://evil.test/x.png)}"],
    'import crlf' => ["@\\69\r\nmport \"https://evil.test/a.css\";"],
]);
