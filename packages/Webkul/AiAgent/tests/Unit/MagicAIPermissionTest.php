<?php

describe('Magic AI permission guard (Issue #647)', function () {

    it('translation controller methods check ai-agent permission', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Http/Controllers/MagicAI/MagicAIController.php')
        );

        expect($source)->toContain("bouncer()->hasPermission('ai-agent')");
    });

    it('translate button is gated by ai-agent permission in the view', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/products/dynamic-attribute-fields.blade.php')
        );

        expect($source)->toContain("bouncer()->hasPermission('ai-agent')");
    });

    it('translate-action menu item is gated by ai-agent permission in the view', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/catalog/products/edit/more-actions/index.blade.php')
        );

        expect($source)->toContain("bouncer()->hasPermission('ai-agent')");
    });

    it('image generation enabled flag also checks ai-agent permission', function () {
        $fileSource = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/media/file.blade.php')
        );

        $imagesSource = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/media/images.blade.php')
        );

        expect($fileSource)->toContain("bouncer()->hasPermission('ai-agent')");
        expect($imagesSource)->toContain("bouncer()->hasPermission('ai-agent')");
    });
});
