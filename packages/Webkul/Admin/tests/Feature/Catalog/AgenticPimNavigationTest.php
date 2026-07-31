<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\SearchProducts;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

uses(DatabaseTransactions::class);

function agenticPimChatContext(Admin $admin): ChatContext
{
    return new ChatContext(
        message: 'find product',
        history: [],
        productId: null,
        productSku: null,
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

function agenticPimWidgetSource(): string
{
    return file_get_contents(
        dirname(__DIR__, 4).'/AiAgent/Resources/views/components/chat-widget.blade.php'
    );
}

function agenticPimNavigatorSource(): string
{
    return file_get_contents(
        dirname(__DIR__, 3).'/src/Resources/assets/js/plugins/navigation.js'
    );
}

describe('#1266 Find Product result navigation', function () {
    it('returns an edit_url that the SPA navigator is eligible to intercept', function () {
        $admin = Admin::factory()->create();

        $product = Product::factory()->simple()->create();

        $result = json_decode(
            app(SearchProducts::class)->register(agenticPimChatContext($admin))->handle(new Request([
                'query' => $product->sku,
            ])),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $match = collect($result['products'] ?? [])->firstWhere('sku', $product->sku);

        expect($match)->not->toBeNull()
            ->and($match['edit_url'])->toBe(route('admin.catalog.products.edit', $product->id));

        $path = parse_url($match['edit_url'], PHP_URL_PATH);

        expect($path)->toStartWith('/'.config('app.admin_url', 'admin').'/');
    });

    it('marks admin links inside chat markdown with data-internal-link', function () {
        expect(agenticPimWidgetSource())
            ->toContain("const isInternal = url.includes('/admin/');")
            ->toContain('data-internal-link');
    });

    it('routes chat result links through the SPA navigator instead of a hard page load', function () {
        $source = agenticPimWidgetSource();

        expect($source)
            ->toContain("const link = e.target.closest('a[data-internal-link]');")
            ->toContain('$navigate(link.getAttribute(\'href\'));')
            ->toContain('@click="handleInternalLink"');
    });

    it('lets the chat click handler defer to an already-intercepted click', function () {
        $source = agenticPimWidgetSource();

        $handler = substr(
            $source,
            strpos($source, 'handleInternalLink(e) {'),
            strlen('handleInternalLink(e) {') + 220
        );

        expect($handler)->toContain('e.defaultPrevented')
            ->and($handler)->toContain('$navigate')
            ->and($handler)->not->toContain('window.location');
    });

    it('confirms the SPA navigator claims the same click first in the capture phase', function () {
        $source = agenticPimNavigatorSource();

        expect($source)
            ->toContain("document.addEventListener('click', onDocumentClick, true);")
            ->toContain('event.defaultPrevented')
            ->toContain('visit(link.href, true);');
    });

    it('shows the working View Product anchor carries no data-internal-link', function () {
        $source = agenticPimWidgetSource();

        $anchor = substr($source, strpos($source, ':href="msg.product_url"'), 200);

        expect($anchor)->not->toContain('data-internal-link');
    });
});

describe('#1266 navigation guards are not the reload trigger', function () {
    it('aborts a guarded visit without falling back to a full page load', function () {
        $source = agenticPimNavigatorSource();

        $guardBlock = substr(
            $source,
            strpos($source, 'if (! (await guardsAllow(url)))'),
            120
        );

        expect($guardBlock)->toContain('return;')
            ->and($guardBlock)->not->toContain('window.location');
    });

    it('keeps the unsaved-changes guard to a boolean decision', function () {
        $source = file_get_contents(
            dirname(__DIR__, 3).'/src/Resources/views/components/form/unsaved-changes.blade.php'
        );

        $guard = substr(
            $source,
            strpos($source, 'const unsavedNavGuard = () =>'),
            260
        );

        expect($guard)->not->toContain('window.location')
            ->and($guard)->toContain('return confirmLeave();');
    });
});
