<?php

use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;

describe('HtmlPurifier cache path configuration (Issue #350)', function () {

    it('purifyText sets Cache.SerializerPath to storage instead of vendor directory', function () {
        $processor = new FieldProcessor;

        // Use reflection to inspect the purifyText method source
        $traitFile = (new ReflectionClass($processor))->getTraitNames();

        expect($traitFile)->toContain('Webkul\Core\Traits\HtmlPurifier');

        // Read the trait source to verify Cache.SerializerPath is configured
        $reflection = new ReflectionMethod($processor, 'purifyText');
        $source = file_get_contents($reflection->getFileName());

        expect($source)->toContain('Cache.SerializerPath');
        expect($source)->toContain("storage_path('app/purifier')");
    });

    it('purifyText sanitizes HTML without throwing cache directory errors', function () {
        $processor = new FieldProcessor;

        $result = $processor->purifyText('<p>Hello <b>World</b></p><script>alert("xss")</script>');

        // Script tag should be stripped, p and b should remain
        expect($result)->toContain('Hello');
        expect($result)->toContain('<b>World</b>');
        expect($result)->not->toContain('<script>');
    });

    it('purifyText handles plain text without errors', function () {
        $processor = new FieldProcessor;

        $result = $processor->purifyText('Simple text value for import');

        expect($result)->toContain('Simple text value for import');
    });

    it('FieldProcessor handles textarea with wysiwyg enabled using purifyText', function () {
        $processor = new FieldProcessor;

        $field = new stdClass;
        $field->type = 'textarea';
        $field->enable_wysiwyg = true;

        $result = $processor->handleField($field, '<p>Product <b>description</b></p>', null);

        expect($result)->toContain('Product');
        expect($result)->toContain('<b>description</b>');
    });
});
