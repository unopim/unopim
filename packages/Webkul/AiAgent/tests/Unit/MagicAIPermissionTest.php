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

    it('More button dropdown itself is hidden when no actions are available', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/catalog/products/edit/more-actions/index.blade.php')
        );

        // The <v-custom-dropdown> trigger must be wrapped in the same gate as its
        // only menu item so the empty "More" button is not rendered when magic AI
        // translation is disabled (issue: empty dropdown served no purpose).
        expect($source)->toMatch('/@if\s*\(\s*\$hasTranslateAction\s*\)\s*\R\s*<v-custom-dropdown><\/v-custom-dropdown>\s*\R\s*@endif/');
    });

    it('image generation enabled flag also checks ai-agent permission in file and images components', function () {
        $fileSource = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/media/file.blade.php')
        );

        $imagesSource = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/media/images.blade.php')
        );

        expect($fileSource)->toContain("bouncer()->hasPermission('ai-agent')");
        expect($imagesSource)->toContain("bouncer()->hasPermission('ai-agent')");
    });

    it('gallery component image generation enabled flag checks ai-agent permission', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/media/gallery.blade.php')
        );

        expect($source)->toContain("bouncer()->hasPermission('ai-agent')");
    });

    it('tinymce content generation enabled flag checks ai-agent permission', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/tinymce/index.blade.php')
        );

        expect($source)->toContain("bouncer()->hasPermission('ai-agent')");
    });

    it('gallery component does not use the Boolean-string JS pattern for ai enabled', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/media/gallery.blade.php')
        );

        expect($source)->not->toContain("Boolean(\"{{ core()->getConfigData('general.magic_ai.image_generation.enabled') }}\")");
    });

    it('tinymce component does not use the Boolean-string JS pattern for ai enabled', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/components/tinymce/index.blade.php')
        );

        expect($source)->not->toContain("Boolean(\"{{ core()->getConfigData('general.magic_ai.settings.enabled') }}\")");
    });

    it('content generation controller methods are individually guarded', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Http/Controllers/MagicAI/MagicAIController.php')
        );

        $guardCount = substr_count($source, "bouncer()->hasPermission('ai-agent')");

        // content, platforms, suggestionValues, defaultPrompt + the 6 translation methods = at least 10 guards
        expect($guardCount)->toBeGreaterThanOrEqual(10);
    });
});
