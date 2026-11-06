<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Webkul\Core\Tree;

function renderOnRequest(string $url, string $template, array $data = []): string
{
    app()->instance('request', Request::create($url));

    Facade::clearResolvedInstance('request');

    return Blade::render($template, $data);
}

describe('locale switcher', function () {
    it('lists every locale as a link that keeps the current url and marks the current one', function () {
        $html = renderOnRequest('http://localhost/admin/records', '<x-admin::locale-switcher :locales="$locales" current="fr_FR" param="data_locale" />', [
            'locales' => [
                ['code' => 'en_US', 'name' => 'English (United States)'],
                ['code' => 'fr_FR', 'name' => 'French (France)'],
            ],
        ]);

        expect($html)->toContain('<v-dropdown')
            ->and($html)->toContain('icon-language')
            ->and($html)->toContain('href="http://localhost/admin/records?data_locale=en_US"')
            ->and($html)->toContain('href="http://localhost/admin/records?data_locale=fr_FR"')
            ->and($html)->toContain('English (United States)')
            ->and(substr_count($html, 'French (France)'))->toBeGreaterThanOrEqual(2);
    });

    it('falls back to the locale code when the current locale is not listed', function () {
        $html = Blade::render('<x-admin::locale-switcher :locales="[]" current="xx_XX" />');

        expect($html)->toContain('xx_XX');
    });
});

it('renders form help text with the shared muted style and merged attributes', function () {
    $html = Blade::render('<x-admin::form.control-group.help class="extra" id="code-help">Lowercase letters only.</x-admin::form.control-group.help>');

    expect($html)->toContain('<p class="mt-1 text-xs text-gray-600 dark:text-gray-300 extra" id="code-help">')
        ->and($html)->toContain('Lowercase letters only.');
});

describe('card icon', function () {
    it('masks an icon url so it takes the theme colour', function () {
        $html = Blade::render('<x-admin::card title="Records" icon-url="/icons/records.svg">Body</x-admin::card>');

        expect($html)->toContain('class="icon-mask')
            ->and($html)->toContain("--icon-mask-url: url('/icons/records.svg')")
            ->and($html)->not->toContain('<img');
    });

    it('renders an icon url as an image when masking is off', function () {
        $html = Blade::render('<x-admin::card title="Records" icon-url="/icons/records.png" :icon-mask="false">Body</x-admin::card>');

        expect($html)->toContain('<img')
            ->and($html)->toContain('src="/icons/records.png"')
            ->and($html)->not->toContain('icon-mask');
    });

    it('keeps the font icon class when no icon url is given', function () {
        $html = Blade::render('<x-admin::card title="Records" icon="icon-product">Body</x-admin::card>');

        expect($html)->toContain('icon-product')
            ->and($html)->not->toContain('icon-mask')
            ->and($html)->not->toContain('<img');
    });
});

describe('quick create modal', function () {
    $template = <<<'BLADE'
        <x-admin::catalog.quick-create-modal
            id="quickCreate"
            action="/admin/records"
            button-label="Create"
            title="Create record"
            name-label="Name"
            code-label="Code"
            save-label="Save"
            {{ $extra }}
        >
            <span id="extra-quick-create-field">Extra field</span>
        </x-admin::catalog.quick-create-modal>
        BLADE;

    beforeEach(function () {
        View::share('errors', new ViewErrorBag);
    });

    it('names the name field after the given locale', function () use ($template) {
        $html = Blade::render(str_replace('{{ $extra }}', 'name-locale="fr_FR"', $template));

        expect($html)->toContain('fr_FR[name]')
            ->and($html)->not->toContain(core()->getRequestedLocaleCode().'[name]');
    });

    it('uses an explicit name field over the locale default', function () use ($template) {
        $html = Blade::render(str_replace('{{ $extra }}', 'name-field="labels[de_DE]"', $template));

        expect($html)->toContain('labels[de_DE]')
            ->and($html)->not->toContain('[name]');
    });

    it('renders extra fields passed in the default slot', function () use ($template) {
        $html = Blade::render(str_replace('{{ $extra }}', '', $template));

        expect($html)->toContain('<span id="extra-quick-create-field">Extra field</span>');
    });
});

describe('heading slot', function () {
    it('replaces the page title text with the heading slot', function () {
        $html = Blade::render('<x-admin::page-title title="Plain title" :breadcrumb="false"><x-slot:heading><span id="rich-heading">Rich title</span></x-slot></x-admin::page-title>');

        expect($html)->toContain('<span id="rich-heading">Rich title</span>')
            ->and($html)->not->toContain('Plain title');
    });

    it('passes the heading slot of the edit page header through to the page title', function () {
        $html = Blade::render('<x-admin::layouts.edit-page-header title="Plain title" :breadcrumb="false" :sticky="false"><x-slot:heading><span id="rich-heading">Rich title</span></x-slot></x-admin::layouts.edit-page-header>');

        expect($html)->toMatch('/<h1[^>]*>\s*<span id="rich-heading">Rich title<\/span>\s*<\/h1>/')
            ->and($html)->not->toContain('Plain title');
    });

    it('keeps the title when no heading slot is given', function () {
        $html = Blade::render('<x-admin::layouts.edit-page-header title="Plain title" :breadcrumb="false" :sticky="false" />');

        expect($html)->toMatch('/<h1[^>]*>Plain title<\/h1>/');
    });
});

it('treats the current breadcrumb as the leaf when only the host differs from APP_URL', function () {
    app()->instance('request', Request::create('http://127.0.0.1:8000/admin/catalog/products'));
    Facade::clearResolvedInstance('request');

    $tree = new Tree;
    $tree->items = [
        'catalog' => [
            'key'      => 'catalog',
            'name'     => 'Catalog',
            'url'      => 'https://pim.example.com/admin/catalog',
            'children' => [
                'products' => ['key' => 'catalog.products', 'name' => 'Products', 'url' => 'https://pim.example.com/admin/catalog/products', 'children' => []],
            ],
        ],
    ];

    app()->instance('unopim.admin.menu', ['tree' => $tree, 'landingUrl' => '/admin']);

    $html = Blade::render('<x-admin::breadcrumbs leaf="Edit" />');

    expect($html)->toContain('href="https://pim.example.com/admin/catalog"')
        ->and($html)->not->toContain('href="https://pim.example.com/admin/catalog/products"')
        ->and($html)->toMatch('/<span class="text-gray-400 dark:text-gray-500">\s*Products\s*<\/span>/');
});
