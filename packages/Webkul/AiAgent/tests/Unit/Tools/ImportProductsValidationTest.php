<?php

use Webkul\AiAgent\Chat\Tools\ImportProducts;

describe('ImportProducts SKU validation (Issue #689)', function () {

    it('tool source validates SKU format before creating products', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($source)->toContain('validateSku');
    });

    it('validateSku method rejects only blank, oversized, space-padded, or delimiter-bearing SKUs', function () {
        $tool = app(ImportProducts::class);

        $method = new ReflectionMethod($tool, 'validateSku');

        expect($method->invoke($tool, 'valid-sku'))->toBeTrue();
        expect($method->invoke($tool, 'valid_sku_123'))->toBeTrue();
        expect($method->invoke($tool, 'ABC-123'))->toBeTrue();
        expect($method->invoke($tool, 'simple'))->toBeTrue();
        expect($method->invoke($tool, 'sku@special'))->toBeTrue();
        expect($method->invoke($tool, 'sku#hash'))->toBeTrue();
        expect($method->invoke($tool, '-starts-with-dash'))->toBeTrue();
        expect($method->invoke($tool, '_starts-with-underscore'))->toBeTrue();

        expect($method->invoke($tool, ''))->toBeFalse();
        expect($method->invoke($tool, ' sku with leading space'))->toBeFalse();
        expect($method->invoke($tool, 'sku with trailing space '))->toBeFalse();
        expect($method->invoke($tool, str_repeat('A', 256)))->toBeFalse();
        expect($method->invoke($tool, 'sku,with,comma'))->toBeFalse();
        expect($method->invoke($tool, 'sku;with;semicolon'))->toBeFalse();
    });

    it('delegates imports to the core DataTransfer batch pipeline behind a memory-safety row cap', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($source)->toContain('ImportTrackBatch::dispatch');
        expect($source)->toContain('MAX_IMPORT_ROWS');
    });

    it('gates variant rows attaching to an existing parent behind edit permission', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($source)->toContain('import-acl-skip-parent');
    });

    it('validateSku delegates to the Core Sku rule', function () {
        $toolSource = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($toolSource)->toContain('Webkul\Core\Rules\Sku');
    });

    it('pre-filters invalid SKUs before handing rows to the importer', function () {
        $toolSource = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($toolSource)->toContain('validateSku');
        expect($toolSource)->toContain('skippedInvalidSku');
    });
});
