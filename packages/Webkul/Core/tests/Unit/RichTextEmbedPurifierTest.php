<?php

use Webkul\Core\HtmlPurifier\SandboxIframe;
use Webkul\Core\Traits\HtmlPurifier;

/**
 * Purify rich text through the shared purifier configuration.
 */
function purifyRichText(string $html): string
{
    $purifier = new class
    {
        use HtmlPurifier;
    };

    return $purifier->purifyText($html);
}

it('keeps an allowed video embed and locks it into a sandbox', function (string $src): void {
    $html = purifyRichText('<iframe src="'.$src.'" width="560" sandbox="allow-top-navigation" srcdoc="&lt;script&gt;alert(1)&lt;/script&gt;" onload="alert(2)"></iframe>');

    expect($html)->toContain('src="'.$src.'"')
        ->toContain('sandbox="'.SandboxIframe::SANDBOX.'"')
        ->toContain('referrerpolicy="'.SandboxIframe::REFERRER_POLICY.'"')
        ->not->toContain('allow-top-navigation')
        ->not->toContain('srcdoc')
        ->not->toContain('onload');
})->with([
    'youtube'           => ['https://www.youtube.com/embed/abc'],
    'youtube nocookie'  => ['https://www.youtube-nocookie.com/embed/abc'],
    'vimeo'             => ['https://player.vimeo.com/video/123'],
]);

it('drops an iframe from any other source', function (string $src): void {
    expect(purifyRichText('<p>before</p><iframe src="'.$src.'"></iframe><p>after</p>'))->not->toContain('<iframe');
})->with([
    'other host'      => ['https://evil.example/embed/x'],
    'lookalike host'  => ['https://www.youtube.com.evil.example/embed/x'],
    'plain http'      => ['http://www.youtube.com/embed/abc'],
    'non-embed path'  => ['https://www.youtube.com/watch?v=abc'],
    'javascript'      => ['javascript:alert(1)'],
    'data url'        => ['data:text/html,<script>alert(1)</script>'],
    'no source'       => [''],
]);

it('allows no iframe when no embed source is configured', function (): void {
    config(['rich_text.embed_url_prefixes' => []]);

    expect(purifyRichText('<iframe src="https://www.youtube.com/embed/abc"></iframe>'))->not->toContain('<iframe');
});

it('keeps a new window link and forces noopener and noreferrer', function (): void {
    $html = purifyRichText('<a href="https://example.com" target="_blank" rel="opener">x</a>');

    expect($html)->toContain('target="_blank"')
        ->toContain('noopener')
        ->toContain('noreferrer')
        ->not->toContain('rel="opener"');
});

it('drops any link target other than a new window', function (string $target): void {
    expect(purifyRichText('<a href="https://example.com" target="'.$target.'">x</a>'))->not->toContain('target=');
})->with(['_top', '_parent', '_self', 'some-frame']);

it('still removes scripts and event handlers', function (): void {
    $html = purifyRichText('<script>alert(1)</script><img src="https://example.com/a.png" onerror="alert(2)"><a href="javascript:alert(3)">x</a><svg onload="alert(4)"></svg>');

    expect($html)->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->not->toContain('<svg');
});
