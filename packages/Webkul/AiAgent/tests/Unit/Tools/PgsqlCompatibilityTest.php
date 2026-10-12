<?php

describe('AiAgent tools — PostgreSQL raw SQL compatibility', function () {

    it('SearchProducts does not contain hardcoded MySQL JSON functions', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/SearchProducts.php')
        );

        expect($source)->not->toContain('JSON_UNQUOTE');
        expect($source)->not->toContain('JSON_EXTRACT');
        expect($source)->not->toContain('JSON_CONTAINS');
    });

    it('SearchProducts uses the grammar helper for JSON extraction', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/SearchProducts.php')
        );

        expect($source)->toContain('GrammarQueryManager');
        expect($source)->toContain('->jsonExtract(');
    });

    it('DataQualityReport does not contain hardcoded JSON_CONTAINS', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/DataQualityReport.php')
        );

        expect($source)->not->toContain('JSON_CONTAINS');
        expect($source)->not->toContain('JSON_EXTRACT');
    });

    it('DataQualityReport uses the grammar helper for JSON containment', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/DataQualityReport.php')
        );

        expect($source)->toContain('GrammarQueryManager');
        expect($source)->toContain('->jsonContains(');
    });

    it('ListCategories does not contain hardcoded JSON_EXTRACT in raw SQL', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ListCategories.php')
        );

        expect($source)->not->toContain('JSON_EXTRACT');
        expect($source)->not->toContain('JSON_UNQUOTE');
    });

    it('ListCategories uses the grammar helper for JSON extraction', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ListCategories.php')
        );

        expect($source)->toContain('GrammarQueryManager');
        expect($source)->toContain('->jsonExtract(');
    });

    it('BulkEdit does not contain hardcoded JSON_CONTAINS', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/BulkEdit.php')
        );

        expect($source)->not->toContain('JSON_CONTAINS');
        expect($source)->not->toContain('JSON_EXTRACT');
    });

    it('BulkEdit uses the grammar helper for JSON containment', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/BulkEdit.php')
        );

        expect($source)->toContain('GrammarQueryManager');
        expect($source)->toContain('->jsonContains(');
    });
});
