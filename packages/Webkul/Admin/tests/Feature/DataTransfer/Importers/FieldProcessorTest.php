<?php

use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;

it('should processes textarea field with WYSIWYG enabled', function () {
    $this->loginAsAdmin();

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

it('should processes textarea field with WYSIWYG disabled', function () {
    $this->loginAsAdmin();

    $fieldProcessor = new FieldProcessor;

    $field = (object) [
        'type'           => 'textarea',
        'enable_wysiwyg' => 0,
    ];

    $textContent = 'This is plain text content';

    $result = $fieldProcessor->handleField($field, $textContent, 'images/');

    expect($result)->toBe($textContent);
});

it('should handles HTML entities in textarea field with WYSIWYG enabled', function () {
    $this->loginAsAdmin();

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

it('should preserves allowed HTML tags in textarea field with WYSIWYG enabled', function () {
    $this->loginAsAdmin();

    $fieldProcessor = new FieldProcessor;

    $field = (object) [
        'type'           => 'textarea',
        'enable_wysiwyg' => 1,
    ];

    $allowedTags = [
        '<p>Paragraph</p>',
        '<b>Bold</b>',
        '<a href="https://example.com">Link</a>',
        '<i>Italic</i>',
        '<em>Emphasis</em>',
        '<strong>Strong</strong>',
        '<ul><li>List item</li></ul>',
        '<ol><li>Ordered list item</li></ol>',
        '<br>',
        '<img src="image.jpg" alt="Image" width="100" height="100">',
        '<h2>Heading 2</h2>',
        '<h3>Heading 3</h3>',
        '<h4>Heading 4</h4>',
        '<table><thead><tr><th>Header</th></tr></thead><tbody><tr><td>Cell</td></tr></tbody></table>',
    ];

    $htmlContent = implode('', $allowedTags);

    $result = $fieldProcessor->handleField($field, $htmlContent, 'images/');

    foreach ($allowedTags as $tag) {
        $tagName = substr($tag, 1, strpos($tag, '>') - 1);
        expect($result)->toContain("<$tagName");
    }
});
