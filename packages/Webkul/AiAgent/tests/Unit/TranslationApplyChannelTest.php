<?php

declare(strict_types=1);

describe('Translation apply() sends targetChannel (Issue #647)', function () {

    it('translate-button apply() explicitly appends targetChannel to formData', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/catalog/products/edit/fields/translate-button.blade.php')
        );

        // Confirm targetChannel is explicitly appended in apply(), not just relied on from DOM
        expect($source)->toContain("formData.append('targetChannel', this.targetChannel)");
    });

    it('translate-action apply() explicitly appends targetChannel to formData', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/catalog/products/edit/more-actions/translate-action.blade.php')
        );

        expect($source)->toContain("formData.append('targetChannel', this.targetChannel)");
    });
});
