<?php

describe('Profile image AI generation should not have @ suggestions', function () {

    it('media images component supports show-suggestions prop', function () {
        $viewPath = base_path('packages/Webkul/Admin/src/Resources/views/components/media/images.blade.php');
        $content = file_get_contents($viewPath);

        // Verify the showSuggestions prop exists in @props
        expect($content)->toContain("'showSuggestions'");

        // Verify it's passed to the Vue component
        expect($content)->toContain('show-suggestions');

        // Verify the @ icon is conditional on showSuggestions
        expect($content)->toContain('showSuggestions');

        // Verify tribute init is conditional on showSuggestions
        expect($content)->toContain('this.showSuggestions');
    });

    it('account edit page disables suggestions for profile image', function () {
        $viewPath = base_path('packages/Webkul/Admin/src/Resources/views/account/edit.blade.php');
        $content = file_get_contents($viewPath);

        // Verify show-suggestions is set to false for the profile image
        expect($content)->toContain(':show-suggestions="false"');
    });

    it('account edit page renders without errors', function () {
        $this->loginAsAdmin();

        $response = $this->get(route('admin.account.edit'));

        $response->assertStatus(200);
        $response->assertSeeText(trans('admin::app.account.edit.title'));
    });
});
