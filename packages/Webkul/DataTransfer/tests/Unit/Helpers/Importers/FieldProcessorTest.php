<?php

use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;

it('should sanitize textarea content when WYSIWYG is enabled', function () {
    $fieldProcessor = new FieldProcessor;

    $field = (object) [
        'type'           => 'textarea',
        'enable_wysiwyg' => 1,
    ];

    $htmlContent = '<p>This is <strong>bold</strong> text with <script>alert("XSS")</script> and <iframe src="malicious-url"></iframe></p>';

    $result = $fieldProcessor->handleField($field, $htmlContent, 'images/');

    expect($result)->toContain('<p>');
    expect($result)->toContain('<strong>bold</strong>');
    expect($result)->not->toContain('<script>');
    expect($result)->not->toContain('<iframe');
});

it('should return plain text as-is when WYSIWYG is disabled', function () {
    $fieldProcessor = new FieldProcessor;

    $field = (object) [
        'type'           => 'textarea',
        'enable_wysiwyg' => 0,
    ];

    $textContent = 'This is plain text content';

    $result = $fieldProcessor->handleField($field, $textContent, 'images/');

    expect($result)->toBe($textContent);
});

it('should decode and sanitize HTML entities when WYSIWYG is enabled', function () {
    $fieldProcessor = new FieldProcessor;

    $field = (object) [
        'type'           => 'textarea',
        'enable_wysiwyg' => 1,
    ];

    $htmlContent = '&lt;p&gt;This is encoded HTML&lt;/p&gt;';

    $result = $fieldProcessor->handleField($field, $htmlContent, 'images/');

    expect($result)->toContain('<p>');
    expect($result)->toContain('This is encoded HTML');
});

it('should preserve only allowed HTML tags when WYSIWYG is enabled', function () {
    $fieldProcessor = new FieldProcessor;

    $field = (object) [
        'type'           => 'textarea',
        'enable_wysiwyg' => 1,
    ];

    $inputHtml = <<<'HTML'
        <p>Paragraph</p>
        <b>Bold</b>
        <a href="https://example.com" target="_blank" onclick="alert('XSS')">Link</a>
        <i>Italic</i>
        <em>Emphasis</em>
        <strong>Strong</strong>
        <ul><li>List item</li></ul>
        <ol><li>Ordered list item</li></ol>
        <br>
        <img src="image.jpg" alt="Image" width="100" height="100" onclick="stealCookies()">
        <h2>Heading 2</h2>
        <h3>Heading 3</h3>
        <h4>Heading 4</h4>
        <table>
            <thead><tr><th>Header</th></tr></thead>
            <tbody><tr><td>Cell</td></tr></tbody>
        </table>
        <script>alert("XSS")</script>
    HTML;

    $result = $fieldProcessor->handleField($field, $inputHtml, 'images/');

    expect($result)->toContain('<p>Paragraph</p>');
    expect($result)->toContain('<b>Bold</b>');
    expect($result)->toContain('<a href="https://example.com">Link</a>');
    expect($result)->toContain('<i>Italic</i>');
    expect($result)->toContain('<em>Emphasis</em>');
    expect($result)->toContain('<strong>Strong</strong>');
    expect($result)->toContain('<ul><li>List item</li></ul>');
    expect($result)->toContain('<ol><li>Ordered list item</li></ol>');
    expect($result)->toContain('<br />');
    expect($result)->toContain('<img src="image.jpg" alt="Image" width="100" height="100" />');
    expect($result)->toContain('<h2>Heading 2</h2>');
    expect($result)->toContain('<h3>Heading 3</h3>');
    expect($result)->toContain('<h4>Heading 4</h4>');
    expect($result)->toContain('<table>');
    expect($result)->toContain('<thead><tr><th>Header</th></tr></thead>');
    expect($result)->toContain('<tbody><tr><td>Cell</td></tr></tbody>');

    expect($result)->not->toContain('<script>');
    expect($result)->not->toContain('onclick="');
    expect($result)->not->toContain('target="_blank"');
});
