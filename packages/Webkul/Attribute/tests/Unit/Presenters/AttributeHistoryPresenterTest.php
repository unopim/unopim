<?php

use Webkul\Attribute\Presenters\AttributeHistoryPresenter;

it('represents a created-event empty old value as blank without crashing', function () {
    $result = AttributeHistoryPresenter::representValueForHistory([], 'text', 'type');

    expect($result)->toHaveKey('type');
    expect($result['type']['old'])->toBe('');
    expect($result['type']['new'])->not->toBe('');
});

it('formats a boolean field with an empty old value as blank', function () {
    $result = AttributeHistoryPresenter::representValueForHistory([], 1, 'is_required');

    expect($result['is_required']['old'])->toBe('');
    expect($result['is_required']['new'])->not->toBe('');
});

it('formats scalar old and new type values', function () {
    $result = AttributeHistoryPresenter::representValueForHistory('text', 'textarea', 'type');

    expect($result['type']['old'])->not->toBe('');
    expect($result['type']['new'])->not->toBe('');
});
