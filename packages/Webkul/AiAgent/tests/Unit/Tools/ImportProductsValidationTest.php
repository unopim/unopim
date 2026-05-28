<?php

use Webkul\AiAgent\Chat\Tools\ImportProducts;

describe('ImportProducts SKU validation (Issue #689)', function () {

    it('tool source validates SKU format before creating products', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        // Must contain SKU validation using the same pattern as Core\Rules\Sku
        expect($source)->toContain('validateSku');
    });

    it('validateSku method rejects SKUs with special characters', function () {
        $tool = app(ImportProducts::class);

        $method = new ReflectionMethod($tool, 'validateSku');

        expect($method->invoke($tool, 'valid-sku'))->toBeTrue();
        expect($method->invoke($tool, 'valid_sku_123'))->toBeTrue();
        expect($method->invoke($tool, 'ABC-123'))->toBeTrue();
        expect($method->invoke($tool, 'simple'))->toBeTrue();

        // Invalid SKUs with special characters
        expect($method->invoke($tool, 'invalid-sku!!'))->toBeFalse();
        expect($method->invoke($tool, 'sku with spaces'))->toBeFalse();
        expect($method->invoke($tool, 'sku@special'))->toBeFalse();
        expect($method->invoke($tool, 'sku#hash'))->toBeFalse();
        expect($method->invoke($tool, 'sku$dollar'))->toBeFalse();
        expect($method->invoke($tool, ''))->toBeFalse();
        expect($method->invoke($tool, '-starts-with-dash'))->toBeFalse();
        expect($method->invoke($tool, '_starts-with-underscore'))->toBeFalse();
    });

    it('has no MAX_ROWS constant — all rows are dispatched to a background job', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        expect($source)->not->toContain('MAX_ROWS');
        expect($source)->toContain('ImportProductsJob::dispatch');
    });

    it('validateSku uses the same regex pattern as Core Sku rule', function () {
        $toolSource = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        $ruleSource = file_get_contents(
            base_path('packages/Webkul/Core/src/Rules/Sku.php')
        );

        // Extract the regex pattern from the Sku rule
        preg_match("/preg_match\('([^']+)'/", $ruleSource, $ruleMatch);
        $skuPattern = $ruleMatch[1];

        // The tool must use the same pattern
        expect($toolSource)->toContain($skuPattern);
    });

    it('invalid SKUs are counted as skipped before the job is dispatched', function () {
        $toolSource = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ImportProducts.php')
        );

        $jobSource = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Jobs/ImportProductsJob.php')
        );

        // Tool pre-filters invalid SKUs before dispatching
        expect($toolSource)->toContain('validateSku');
        expect($toolSource)->toContain('skippedInvalidSku');

        // Job handles per-row errors during processing
        expect($jobSource)->toContain('errors[]');
    });
});
