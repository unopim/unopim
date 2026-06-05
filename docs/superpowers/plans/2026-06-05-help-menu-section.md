# Help & Resources Menu Section — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a config-driven "Help & Resources" admin page (sidebar item) built entirely from UnoPim Blade components, introducing a reusable global `<x-admin::card>` component.

**Architecture:** New anonymous Blade card component renders one link card. A new `Config/help.php` holds sections + items (icon SVG, trans keys, URL, host) and a CTA block. A thin `HelpController@index` returns a view that loops the config through the card component. Menu, ACL, route, and translations (en_US + 32 locales) wire it into the admin.

**Tech Stack:** Laravel 12, Concord, Blade anonymous components, Tailwind (cherry/violet palette), Pest 3.

All paths relative to repo root `/home/users/navneet.kumar/www/html/github/unopim`. Package root: `packages/Webkul/Admin/`.

---

## Reference values (used across tasks)

**Card icon SVGs (from mockup, 24×24):**
- cloud: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19a4.5 4.5 0 0 0 .5-8.97A6 6 0 0 0 6.2 9.1 4 4 0 0 0 6.5 19z"></path></svg>`
- support: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><circle cx="12" cy="12" r="3.2"></circle><path d="m5.6 5.6 3.1 3.1 M15.3 15.3l3.1 3.1 M15.3 8.7l3.1-3.1 M5.6 18.4l3.1-3.1"></path></svg>`
- services: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6a1.4 1.4 0 0 0 2 2l6-6a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2z"></path></svg>`
- extensions: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4a2 2 0 1 1 4 0v1h3a1 1 0 0 1 1 1v3h1a2 2 0 1 1 0 4h-1v3a1 1 0 0 1-1 1h-3v-1a2 2 0 1 0-4 0v1H6a1 1 0 0 1-1-1v-3H4a2 2 0 1 1 0-4h1V6a1 1 0 0 1 1-1h3z"></path></svg>`
- user-guide: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 0 1 2-2h6v18H6a2 2 0 0 1-2-2z M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path></svg>`
- api-docs: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m8 8-4 4 4 4 M16 8l4 4-4 4 M14 5l-4 14"></path></svg>`
- cta: `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.5 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.2A8.4 8.4 0 0 1 4 11.5 8.5 8.5 0 0 1 21 11.5Z"></path></svg>`

**en_US source strings:**
- index.title: `Help & Resources`
- index.subtitle: `Everything you need to get the most out of UnoPim — hosting, support and professional services, plus extensions and developer documentation.`
- index.services: `Services`
- index.resources: `Resources & Documentation`
- cards.cloud-hosting.title: `Cloud Hosting`
- cards.cloud-hosting.description: `Cost-effective, managed cloud hosting — try and launch UnoPim on the cloud in minutes, fully optimised and scalable.`
- cards.support.title: `Support & Maintenance`
- cards.support.description: `Dedicated technical support and ongoing maintenance plans to keep your PIM secure, updated and running smoothly.`
- cards.services.title: `Paid Services`
- cards.services.description: `Expert help for module integration, customisation, data migration, version upgrades and bespoke development.`
- cards.extensions.title: `Extensions`
- cards.extensions.description: `Browse official and community add-ons to extend UnoPim with new connectors, channels and features.`
- cards.user-guide.title: `User Guide`
- cards.user-guide.description: `Developer guides, tutorials and the latest articles to help you build, configure and stay up to date.`
- cards.api-docs.title: `API Docs`
- cards.api-docs.description: `Full REST API reference with endpoints, authentication and examples to integrate UnoPim with your stack.`
- cta.title: `Still need a hand?`
- cta.sub: `Talk to the UnoPim team about hosting, custom development or anything else.`
- cta.button: `Contact us`
- sidebar.help: `Help`
- acl.help: `Help`

---

## Task 1: Global card component

**Files:**
- Create: `packages/Webkul/Admin/src/Resources/views/components/card.blade.php`
- Test: `packages/Webkul/Admin/tests/Feature/Help/CardComponentTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

it('card component renders title, link, slot description and external badge', function () {
    $html = view('admin::components.card', [
        'icon'        => '<svg id="my-svg"></svg>',
        'title'       => 'Cloud Hosting',
        'url'         => 'https://unopim.com/cloud-hosting/',
        'host'        => 'unopim.com/cloud-hosting',
        'external'    => true,
        'slot'        => new \Illuminate\Support\HtmlString('Managed hosting'),
    ])->render();

    expect($html)
        ->toContain('Cloud Hosting')
        ->toContain('https://unopim.com/cloud-hosting/')
        ->toContain('Managed hosting')
        ->toContain('unopim.com/cloud-hosting')
        ->toContain('<svg id="my-svg">')
        ->toContain('rel="noopener"');
});

it('card component renders icon-font class when icon is not svg', function () {
    $html = view('admin::components.card', [
        'icon'  => 'icon-star',
        'title' => 'Paid Services',
        'url'   => 'https://unopim.com/services/',
        'slot'  => new \Illuminate\Support\HtmlString('Expert help'),
    ])->render();

    expect($html)->toContain('icon-star');
});
```

- [ ] **Step 2: Run test, verify it fails**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/CardComponentTest.php`
Expected: FAIL — view `admin::components.card` not found.

- [ ] **Step 3: Create the component**

`packages/Webkul/Admin/src/Resources/views/components/card.blade.php`:

```blade
@props([
    'icon'     => '',
    'title'    => '',
    'url'      => '#',
    'host'     => '',
    'target'   => '_blank',
    'external' => false,
])

<a
    href="{{ $url }}"
    target="{{ $target }}"
    @if ($external) rel="noopener" @endif
    {{ $attributes->merge(['class' => 'group flex flex-col bg-white dark:bg-cherry-800 border border-gray-200 dark:border-cherry-700 rounded-xl p-5 no-underline text-current transition-all hover:border-violet-200 dark:hover:border-violet-500 hover:shadow-lg hover:-translate-y-0.5']) }}
>
    <div class="flex items-start justify-between mb-4">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-violet-50 dark:bg-cherry-900 text-violet-600 transition-all group-hover:bg-violet-600 group-hover:text-white">
            @if (str_starts_with(trim($icon), '<svg'))
                {!! $icon !!}
            @else
                <span class="text-2xl {{ $icon }}"></span>
            @endif
        </span>

        @if ($external)
            <span class="flex items-center justify-center w-7 h-7 rounded-lg text-gray-300 transition-all group-hover:text-violet-600 group-hover:bg-violet-50 dark:group-hover:bg-cherry-900">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 17 17 7 M9 7h8v8"></path>
                </svg>
            </span>
        @endif
    </div>

    <h3 class="text-base font-bold leading-tight mb-1.5 text-gray-900 dark:text-white">
        {{ $title }}
    </h3>

    <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-300 m-0 flex-1">
        {{ $slot }}
    </p>

    @if ($host)
        <div class="flex items-center gap-1.5 mt-4 pt-3.5 border-t border-gray-100 dark:border-cherry-700 text-xs font-semibold text-violet-600">
            <span class="text-gray-400 font-medium">{{ $host }}</span>

            <svg class="opacity-0 -translate-x-1 transition-all group-hover:opacity-100 group-hover:translate-x-0" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14 M13 6l6 6-6 6"></path>
            </svg>
        </div>
    @endif
</a>
```

- [ ] **Step 4: Run test, verify it passes**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/CardComponentTest.php`
Expected: PASS (2 tests).

- [ ] **Step 5: Format + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/tests/Feature/Help/CardComponentTest.php
vendor/bin/pint --test
git add packages/Webkul/Admin/src/Resources/views/components/card.blade.php packages/Webkul/Admin/tests/Feature/Help/CardComponentTest.php
git commit -m "feat(admin): add reusable global card component"
```

---

## Task 2: Help config

**Files:**
- Create: `packages/Webkul/Admin/src/Config/help.php`
- Modify: `packages/Webkul/Admin/src/Providers/AdminServiceProvider.php` (registerConfig, ~line 75)
- Test: `packages/Webkul/Admin/tests/Feature/Help/HelpConfigTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

it('help config exposes sections and a cta', function () {
    $sections = config('help.sections');
    $cta      = config('help.cta');

    expect($sections)->toBeArray()->not->toBeEmpty();
    expect($cta)->toBeArray()->toHaveKeys(['icon', 'title', 'sub', 'url', 'label']);

    $allItems = collect($sections)->flatMap(fn ($s) => $s['items']);

    expect($allItems)->toHaveCount(6);

    $allItems->each(function ($item) {
        expect($item)->toHaveKeys(['icon', 'title', 'description', 'url']);
    });

    expect($allItems->pluck('url'))->toContain('https://unopim.com/cloud-hosting/');
});
```

- [ ] **Step 2: Run test, verify it fails**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpConfigTest.php`
Expected: FAIL — `config('help.sections')` is null.

- [ ] **Step 3: Create `packages/Webkul/Admin/src/Config/help.php`**

Use the exact SVG strings from the Reference section. Full file:

```php
<?php

return [
    'sections' => [
        [
            'title' => 'admin::app.help.index.services',
            'items' => [
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19a4.5 4.5 0 0 0 .5-8.97A6 6 0 0 0 6.2 9.1 4 4 0 0 0 6.5 19z"></path></svg>',
                    'title'       => 'admin::app.help.cards.cloud-hosting.title',
                    'description' => 'admin::app.help.cards.cloud-hosting.description',
                    'url'         => 'https://unopim.com/cloud-hosting/',
                    'host'        => 'unopim.com/cloud-hosting',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><circle cx="12" cy="12" r="3.2"></circle><path d="m5.6 5.6 3.1 3.1 M15.3 15.3l3.1 3.1 M15.3 8.7l3.1-3.1 M5.6 18.4l3.1-3.1"></path></svg>',
                    'title'       => 'admin::app.help.cards.support.title',
                    'description' => 'admin::app.help.cards.support.description',
                    'url'         => 'https://unopim.com/support-maintenance/',
                    'host'        => 'unopim.com/support',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6 6a1.4 1.4 0 0 0 2 2l6-6a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2z"></path></svg>',
                    'title'       => 'admin::app.help.cards.services.title',
                    'description' => 'admin::app.help.cards.services.description',
                    'url'         => 'https://unopim.com/services/',
                    'host'        => 'unopim.com/services',
                    'external'    => true,
                ],
            ],
        ],
        [
            'title' => 'admin::app.help.index.resources',
            'items' => [
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 4a2 2 0 1 1 4 0v1h3a1 1 0 0 1 1 1v3h1a2 2 0 1 1 0 4h-1v3a1 1 0 0 1-1 1h-3v-1a2 2 0 1 0-4 0v1H6a1 1 0 0 1-1-1v-3H4a2 2 0 1 1 0-4h1V6a1 1 0 0 1 1-1h3z"></path></svg>',
                    'title'       => 'admin::app.help.cards.extensions.title',
                    'description' => 'admin::app.help.cards.extensions.description',
                    'url'         => 'https://unopim.com/extensions/',
                    'host'        => 'unopim.com/extensions',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5a2 2 0 0 1 2-2h6v18H6a2 2 0 0 1-2-2z M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"></path></svg>',
                    'title'       => 'admin::app.help.cards.user-guide.title',
                    'description' => 'admin::app.help.cards.user-guide.description',
                    'url'         => 'https://docs.unopim.com/',
                    'host'        => 'docs.unopim.com',
                    'external'    => true,
                ],
                [
                    'icon'        => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m8 8-4 4 4 4 M16 8l4 4-4 4 M14 5l-4 14"></path></svg>',
                    'title'       => 'admin::app.help.cards.api-docs.title',
                    'description' => 'admin::app.help.cards.api-docs.description',
                    'url'         => 'https://docs.unopim.com/api/',
                    'host'        => 'docs.unopim.com/api',
                    'external'    => true,
                ],
            ],
        ],
    ],

    'cta' => [
        'icon'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.5 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.2A8.4 8.4 0 0 1 4 11.5 8.5 8.5 0 0 1 21 11.5Z"></path></svg>',
        'title' => 'admin::app.help.cta.title',
        'sub'   => 'admin::app.help.cta.sub',
        'url'   => 'https://unopim.com/contacts/',
        'label' => 'admin::app.help.cta.button',
    ],
];
```

- [ ] **Step 4: Register config in the provider**

In `packages/Webkul/Admin/src/Providers/AdminServiceProvider.php`, inside `registerConfig()`, after the `system.php` merge (~line 78) add:

```php
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/help.php',
            'help'
        );
```

- [ ] **Step 5: Run test, verify it passes**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpConfigTest.php`
Expected: PASS.

- [ ] **Step 6: Format + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/src/Config/help.php packages/Webkul/Admin/src/Providers/AdminServiceProvider.php packages/Webkul/Admin/tests/Feature/Help/HelpConfigTest.php
vendor/bin/pint --test
git add packages/Webkul/Admin/src/Config/help.php packages/Webkul/Admin/src/Providers/AdminServiceProvider.php packages/Webkul/Admin/tests/Feature/Help/HelpConfigTest.php
git commit -m "feat(admin): add help config with sections and cta"
```

---

## Task 3: en_US translations

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/lang/en_US/app.php`
  - Add `'help' => [...]` top-level block
  - Add `'help' => 'Help'` into `components.layouts.sidebar` (~line 2126, alphabetical near `'groups'`)
  - Add `'help' => 'Help'` into `acl` block (~line 2375, alphabetical near `'groups'`)
- Test: `packages/Webkul/Admin/tests/Feature/Help/HelpTranslationsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

it('en_US has all help translation keys', function () {
    app()->setLocale('en_US');

    expect(trans('admin::app.help.index.title'))->toBe('Help & Resources');
    expect(trans('admin::app.help.index.services'))->toBe('Services');
    expect(trans('admin::app.help.index.resources'))->toBe('Resources & Documentation');
    expect(trans('admin::app.help.cards.cloud-hosting.title'))->toBe('Cloud Hosting');
    expect(trans('admin::app.help.cards.api-docs.title'))->toBe('API Docs');
    expect(trans('admin::app.help.cta.button'))->toBe('Contact us');
    expect(trans('admin::app.components.layouts.sidebar.help'))->toBe('Help');
    expect(trans('admin::app.acl.help'))->toBe('Help');

    // No key should resolve to its own raw path (missing-key signal)
    foreach ([
        'admin::app.help.index.subtitle',
        'admin::app.help.cards.support.description',
        'admin::app.help.cards.services.description',
        'admin::app.help.cards.extensions.description',
        'admin::app.help.cards.user-guide.description',
        'admin::app.help.cta.title',
        'admin::app.help.cta.sub',
    ] as $key) {
        expect(trans($key))->not->toBe($key);
    }
});
```

- [ ] **Step 2: Run test, verify it fails**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpTranslationsTest.php`
Expected: FAIL — keys resolve to raw path.

- [ ] **Step 3: Add the `help` block to en_US/app.php**

Insert this block as a new top-level entry in the returned array (e.g. directly before the `'datagrid' => [` entry — search for `'datagrid' => [` and insert above it):

```php
    'help' => [
        'index' => [
            'title'     => 'Help & Resources',
            'subtitle'  => 'Everything you need to get the most out of UnoPim — hosting, support and professional services, plus extensions and developer documentation.',
            'services'  => 'Services',
            'resources' => 'Resources & Documentation',
        ],

        'cards' => [
            'cloud-hosting' => [
                'title'       => 'Cloud Hosting',
                'description' => 'Cost-effective, managed cloud hosting — try and launch UnoPim on the cloud in minutes, fully optimised and scalable.',
            ],
            'support' => [
                'title'       => 'Support & Maintenance',
                'description' => 'Dedicated technical support and ongoing maintenance plans to keep your PIM secure, updated and running smoothly.',
            ],
            'services' => [
                'title'       => 'Paid Services',
                'description' => 'Expert help for module integration, customisation, data migration, version upgrades and bespoke development.',
            ],
            'extensions' => [
                'title'       => 'Extensions',
                'description' => 'Browse official and community add-ons to extend UnoPim with new connectors, channels and features.',
            ],
            'user-guide' => [
                'title'       => 'User Guide',
                'description' => 'Developer guides, tutorials and the latest articles to help you build, configure and stay up to date.',
            ],
            'api-docs' => [
                'title'       => 'API Docs',
                'description' => 'Full REST API reference with endpoints, authentication and examples to integrate UnoPim with your stack.',
            ],
        ],

        'cta' => [
            'title'  => 'Still need a hand?',
            'sub'    => 'Talk to the UnoPim team about hosting, custom development or anything else.',
            'button' => 'Contact us',
        ],
    ],
```

- [ ] **Step 4: Add sidebar + acl keys**

In `components.layouts.sidebar` block add (keep alignment with neighbours):

```php
                'help'               => 'Help',
```

In the `acl` block add:

```php
        'help'                     => 'Help',
```

- [ ] **Step 5: Run test, verify it passes**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpTranslationsTest.php`
Expected: PASS.

- [ ] **Step 6: Format + commit**

```bash
vendor/bin/pint --test
git add packages/Webkul/Admin/src/Resources/lang/en_US/app.php packages/Webkul/Admin/tests/Feature/Help/HelpTranslationsTest.php
git commit -m "feat(admin): add en_US translations for help section"
```

---

## Task 4: Route + controller

**Files:**
- Create: `packages/Webkul/Admin/src/Http/Controllers/HelpController.php`
- Modify: `packages/Webkul/Admin/src/Routes/rest-routes.php` (add use + route inside the `['admin']` group, near the dashboard controller block ~line 23)
- Test: `packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php` (route-existence assertion in this task; full render added in Task 5)

- [ ] **Step 1: Write the failing test**

```php
<?php

it('help route is registered and named', function () {
    expect(\Illuminate\Support\Facades\Route::has('admin.help.index'))->toBeTrue();
});

it('help page requires authentication', function () {
    $this->get(route('admin.help.index'))->assertRedirect();
});
```

- [ ] **Step 2: Run test, verify it fails**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php`
Expected: FAIL — route `admin.help.index` not defined.

- [ ] **Step 3: Create the controller**

`packages/Webkul/Admin/src/Http/Controllers/HelpController.php`:

```php
<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\View\View;

class HelpController extends Controller
{
    /**
     * Display the help & resources page.
     */
    public function index(): View
    {
        return view('admin::help.index');
    }
}
```

- [ ] **Step 4: Register the route**

In `packages/Webkul/Admin/src/Routes/rest-routes.php`, add the import near the other controller imports (alphabetical, after `DataGridController`):

```php
use Webkul\Admin\Http\Controllers\HelpController;
```

Inside the `Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {` group, after the dashboard controller block, add:

```php
    Route::controller(HelpController::class)->prefix('help')->group(function () {
        Route::get('', 'index')->name('admin.help.index');
    });
```

- [ ] **Step 5: Run test, verify it passes**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Format + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/src/Http/Controllers/HelpController.php packages/Webkul/Admin/src/Routes/rest-routes.php packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php
vendor/bin/pint --test
git add packages/Webkul/Admin/src/Http/Controllers/HelpController.php packages/Webkul/Admin/src/Routes/rest-routes.php packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php
git commit -m "feat(admin): add help route and controller"
```

---

## Task 5: Help view

**Files:**
- Create: `packages/Webkul/Admin/src/Resources/views/help/index.blade.php`
- Modify: `packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php` (append render test)

- [ ] **Step 1: Append the failing render test**

Add inside the existing `HelpPageTest.php`:

```php
it('renders the help page with section labels and cards', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.help.index'));

    $response->assertStatus(200);
    $response->assertSeeText(trans('admin::app.help.index.title'));
    $response->assertSeeText(trans('admin::app.help.index.services'));
    $response->assertSeeText(trans('admin::app.help.index.resources'));
    $response->assertSeeText(trans('admin::app.help.cards.cloud-hosting.title'));
    $response->assertSeeText(trans('admin::app.help.cards.api-docs.title'));
    $response->assertSee('https://unopim.com/cloud-hosting/', false);
    $response->assertSee('https://unopim.com/contacts/', false);
    $response->assertSeeText(trans('admin::app.help.cta.button'));
});
```

- [ ] **Step 2: Run test, verify it fails**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php`
Expected: FAIL — view `admin::help.index` not found.

- [ ] **Step 3: Create the view**

`packages/Webkul/Admin/src/Resources/views/help/index.blade.php`:

```blade
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.help.index.title')
    </x-slot>

    <div class="flex flex-col gap-1.5 mb-8">
        <p class="text-2xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.help.index.title')
        </p>

        <p class="max-w-2xl text-gray-500 dark:text-gray-300 leading-relaxed">
            @lang('admin::app.help.index.subtitle')
        </p>
    </div>

    @foreach (config('help.sections') as $section)
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-8 mb-3.5">
            @lang($section['title'])
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($section['items'] as $item)
                <x-admin::card
                    :icon="$item['icon']"
                    :title="trans($item['title'])"
                    :url="$item['url']"
                    :host="$item['host'] ?? ''"
                    :external="$item['external'] ?? false"
                >
                    @lang($item['description'])
                </x-admin::card>
            @endforeach
        </div>
    @endforeach

    @php($cta = config('help.cta'))

    <div class="flex flex-wrap items-center gap-4 mt-8 p-6 rounded-xl text-white bg-gradient-to-r from-violet-700 to-violet-500">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-white/20 shrink-0">
            {!! $cta['icon'] !!}
        </span>

        <div class="flex-1 min-w-0">
            <p class="text-base font-bold m-0">
                @lang($cta['title'])
            </p>

            <p class="m-0 mt-0.5 text-sm text-white/85">
                @lang($cta['sub'])
            </p>
        </div>

        <a
            href="{{ $cta['url'] }}"
            target="_blank"
            rel="noopener"
            class="shrink-0 inline-flex items-center gap-2 h-10 px-5 rounded-lg bg-white text-violet-700 text-sm font-bold no-underline transition-all hover:shadow-lg"
        >
            @lang($cta['label'])

            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14 M13 6l6 6-6 6"></path>
            </svg>
        </a>
    </div>
</x-admin::layouts>
```

- [ ] **Step 4: Run test, verify it passes**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php`
Expected: PASS (3 tests).

- [ ] **Step 5: Format + commit**

```bash
vendor/bin/pint --test
git add packages/Webkul/Admin/src/Resources/views/help/index.blade.php packages/Webkul/Admin/tests/Feature/Help/HelpPageTest.php
git commit -m "feat(admin): add help page view using card component"
```

---

## Task 6: Menu + ACL entries

**Files:**
- Modify: `packages/Webkul/Admin/src/Config/menu.php` (append after the Configuration entry, ~line 140)
- Modify: `packages/Webkul/Admin/src/Config/acl.php` (append a Help block before the closing `];`)
- Test: `packages/Webkul/Admin/tests/Feature/Help/HelpMenuAclTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

it('registers the help menu item at the bottom', function () {
    $help = collect(config('menu.admin'))->firstWhere('key', 'help');

    expect($help)->not->toBeNull();
    expect($help['route'])->toBe('admin.help.index');
    expect($help['sort'])->toBe(10);
});

it('registers the help acl entry', function () {
    $help = collect(config('acl'))->firstWhere('key', 'help');

    expect($help)->not->toBeNull();
    expect($help['route'])->toBe('admin.help.index');
});
```

- [ ] **Step 2: Run test, verify it fails**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpMenuAclTest.php`
Expected: FAIL — no `help` entry.

- [ ] **Step 3: Add the menu entry**

In `packages/Webkul/Admin/src/Config/menu.php`, after the Configuration array entry (before the final `];`):

```php
    /**
     * Help.
     */
    [
        'key'        => 'help',
        'name'       => 'admin::app.components.layouts.sidebar.help',
        'route'      => 'admin.help.index',
        'sort'       => 10,
        'icon'       => 'icon-information',
    ],
```

- [ ] **Step 4: Add the ACL entry**

In `packages/Webkul/Admin/src/Config/acl.php`, before the final `];`:

```php
    /*
    |--------------------------------------------------------------------------
    | Help
    |--------------------------------------------------------------------------
    |
    | ACL related to the help & resources page.
    |
    */
    [
        'key'   => 'help',
        'name'  => 'admin::app.acl.help',
        'route' => 'admin.help.index',
        'sort'  => 10,
    ],
```

- [ ] **Step 5: Run test, verify it passes**

Run: `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help/HelpMenuAclTest.php`
Expected: PASS (2 tests).

- [ ] **Step 6: Format + commit**

```bash
vendor/bin/pint --test
git add packages/Webkul/Admin/src/Config/menu.php packages/Webkul/Admin/src/Config/acl.php packages/Webkul/Admin/tests/Feature/Help/HelpMenuAclTest.php
git commit -m "feat(admin): add help sidebar menu and acl entries"
```

---

## Task 7: Propagate translations to all 32 remaining locales

The `help` block, the `sidebar.help` key, and the `acl.help` key must exist in
every locale's `app.php` with **naturally translated** values. Locales:

```
ar_AE ca_ES da_DK de_DE en_AU en_GB en_NZ es_ES es_VE fi_FI fr_FR hi_IN hr_HR
id_ID it_IT ja_JP ko_KR mn_MN nl_NL no_NO pl_PL pt_BR pt_PT ro_RO ru_RU sv_SE
tl_PH tr_TR uk_UA vi_VN zh_CN zh_TW
```

(en_US done in Task 3. en_AU/en_GB/en_NZ may reuse the en_US English values.)

- [ ] **Step 1: For each locale, add the three pieces**

For every locale above, in `packages/Webkul/Admin/src/Resources/lang/<locale>/app.php`:
1. Add the `help` top-level block (same structure as Task 3 Step 3) with values
   translated naturally into the locale's language. Keep the `&` / `—` punctuation
   sensible per language; do not translate brand names ("UnoPim", "PIM", "REST API").
2. Add `'help' => '<translated Help>'` into `components.layouts.sidebar`.
3. Add `'help' => '<translated Help>'` into the `acl` block.

> Execution note: dispatch one subagent per locale (or small batches) to translate
> and insert all three pieces, since this is 32 independent edits. Each subagent
> must (a) read the locale's existing `app.php` to match its exact array style and
> indentation, (b) insert the block in a valid position, (c) translate naturally —
> NOT copy English. Brand tokens stay verbatim.

- [ ] **Step 2: Verify with the translations checker**

Run: `php artisan unopim:translations:check`
Expected: PASS with zero missing-key errors across all locales.

- [ ] **Step 3: Verify PHP validity of every edited file**

Run: `for f in packages/Webkul/Admin/src/Resources/lang/*/app.php; do php -l "$f" >/dev/null || echo "SYNTAX ERROR: $f"; done`
Expected: no output (all files lint clean).

- [ ] **Step 4: Format + commit**

```bash
vendor/bin/pint --test
git add packages/Webkul/Admin/src/Resources/lang
git commit -m "i18n(admin): translate help section into all supported locales"
```

---

## Task 8: Full suite + format gate

- [ ] **Step 1: Run the Admin test suite**

Run: `vendor/bin/pest --group=admin` (or `vendor/bin/pest packages/Webkul/Admin/tests/Feature/Help`)
Expected: all Help tests green; no regressions in the Admin suite.

- [ ] **Step 2: Pint full check**

Run: `vendor/bin/pint --test`
Expected: zero issues.

- [ ] **Step 3: Manual / Playwright sanity (if UI verification desired)**

- Log into admin, confirm a "Help" item appears at the bottom of the sidebar.
- Click it → page renders two sections of cards + the CTA banner.
- Hover a card → lift/shadow/arrow animation; click → opens the unopim.com URL in a new tab.
- Toggle dark mode → cards remain legible.

- [ ] **Step 4: Final commit (if any fixups)**

```bash
git add -A
git commit -m "test(admin): verify help section end-to-end"
```

---

## Self-review notes

- **Spec coverage:** card component (T1), config (T2), view (T5), controller (T4), route (T4), menu (T6), ACL (T6), translations en_US (T3) + all locales (T7), tests + pint (each task + T8). All spec sections mapped.
- **Type/name consistency:** route name `admin.help.index`, view `admin::help.index`, config key `help`, component `admin::components.card` (`<x-admin::card>`), trans namespace `admin::app.help.*` — used identically across tasks.
- **No placeholders:** all code blocks complete; the only deferred-by-nature item is the 32-locale natural translation (T7), which is inherently per-language and handled via subagents with explicit rules.
