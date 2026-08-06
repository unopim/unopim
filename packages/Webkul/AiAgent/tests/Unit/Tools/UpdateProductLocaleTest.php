<?php

describe('UpdateProduct tool locale targeting', function () {

    it('exposes a locale parameter in the tool schema', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/UpdateProduct.php')
        );

        expect($source)->toMatch('/\'locale\'\s+=>\s+\$schema->string\(\)/');
    });

    it('mentions the locale parameter in the tool description so the model can find it', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/UpdateProduct.php')
        );

        expect($source)->toContain('pass "locale"');
    });

    it('rejects a locale that is not active instead of writing it somewhere else', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/UpdateProduct.php')
        );

        expect($source)->toContain('Unknown or inactive locale')
            ->and($source)->toContain('active_locales');
    });

    it('writes locale-scoped values to the requested locale rather than the conversation locale', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/UpdateProduct.php')
        );

        expect($source)->toContain("\$values['locale_specific'][\$targetLocale][\$code] = \$value;")
            ->and($source)->toContain("\$values['channel_locale_specific'][\$ch->code][\$targetLocale][\$code] = \$value;");
    });

    it('falls back to the conversation locale when no locale is given', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/UpdateProduct.php')
        );

        expect($source)->toContain("\$targetLocale = \$requestedLocale !== '' ? \$requestedLocale : \$this->context->locale;");
    });

    it('does not auto-translate over the other locales when one was explicitly targeted', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/UpdateProduct.php')
        );

        expect($source)->toContain("if (\$requestedLocale === '' && \\in_array(\$meta['type'], ['text', 'textarea'], true) && is_string(\$value)) {");
    });

    it('treats a value as a locale map only when every key is an active locale', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/UpdateProduct.php')
        );

        expect($source)->toContain('$looksLikeLocaleMap = $localeKeys !== [] && array_all(');
    });
});

describe('chat widget catalog scope', function () {

    it('sends the page locale and channel so the backend does not fall back to the default', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php')
        );

        expect($source)->toContain("fd.append('context[locale]', scopeLocale);")
            ->and($source)->toContain("fd.append('context[channel]', scopeChannel);");
    });

    it('reads the scope from the query string and falls back to the scope inputs on the page', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php')
        );

        expect($source)->toContain("scopeParams.get('locale')")
            ->and($source)->toContain('document.querySelector(\'input[name="locale"]\')?.value');
    });

    it('omits the scope keys entirely when the page has none, leaving the server fallback intact', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php')
        );

        expect($source)->toContain('if (scopeLocale) fd.append')
            ->and($source)->toContain('if (scopeChannel) fd.append');
    });
});
