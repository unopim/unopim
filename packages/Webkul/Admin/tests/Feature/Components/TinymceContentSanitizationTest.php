<?php

function tinymceComponent(): string
{
    return file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/components/tinymce/index.blade.php'));
}

it('does not load the media plugin', function () {
    preg_match("/plugins:\s*'([^']*)'/", tinymceComponent(), $matches);

    expect($matches[1] ?? null)->not->toBeNull()
        ->and(explode(' ', $matches[1]))->not->toContain('media');
});

it('converts unsafe object and embed elements', function () {
    expect(tinymceComponent())->toContain('convert_unsafe_embeds: true');
});

it('sanitizes content set into or pasted into the editor', function () {
    $component = tinymceComponent();

    expect($component)
        ->toContain("editor.on('BeforeSetContent'")
        ->toContain("editor.on('PastePreProcess'")
        ->toContain('DOMPurify.sanitize(html')
        ->toContain("FORBID_TAGS: ['svg', 'math', 'object', 'embed']")
        ->toContain('ALLOW_DATA_ATTR: false');
});
