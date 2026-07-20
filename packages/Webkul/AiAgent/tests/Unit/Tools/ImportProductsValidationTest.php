<?php

use Webkul\AiAgent\Chat\Tools\ImportProducts;

describe('ImportProducts SKU validation (Issue #689)', function () {

    it('tool source validates SKU format before creating products', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($source)->toContain('validateSku');
    });

    it('validateSku method rejects SKUs with special characters', function () {
        $tool = app(ImportProducts::class);

        $method = new ReflectionMethod($tool, 'validateSku');

        expect($method->invoke($tool, 'valid-sku'))->toBeTrue();
        expect($method->invoke($tool, 'valid_sku_123'))->toBeTrue();
        expect($method->invoke($tool, 'ABC-123'))->toBeTrue();
        expect($method->invoke($tool, 'simple'))->toBeTrue();

        expect($method->invoke($tool, 'invalid-sku!!'))->toBeFalse();
        expect($method->invoke($tool, 'sku with spaces'))->toBeFalse();
        expect($method->invoke($tool, 'sku@special'))->toBeFalse();
        expect($method->invoke($tool, 'sku#hash'))->toBeFalse();
        expect($method->invoke($tool, 'sku$dollar'))->toBeFalse();
        expect($method->invoke($tool, ''))->toBeFalse();
        expect($method->invoke($tool, '-starts-with-dash'))->toBeFalse();
        expect($method->invoke($tool, '_starts-with-underscore'))->toBeFalse();
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

    it('validateSku uses the same regex pattern as Core Sku rule', function () {
        $toolSource = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        $ruleSource = file_get_contents(
            base_path('packages/Webkul/Core/src/Rules/Sku.php')
        );

        preg_match("/preg_match\('([^']+)'/", $ruleSource, $ruleMatch);
        $skuPattern = $ruleMatch[1];

        expect($toolSource)->toContain($skuPattern);
    });

    it('pre-filters invalid SKUs before handing rows to the importer', function () {
        $toolSource = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($toolSource)->toContain('validateSku');
        expect($toolSource)->toContain('skippedInvalidSku');
    });
});
