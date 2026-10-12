<?php

it('publishes product editor defaults', function () {
    expect(config('product_editor.lazy_group_threshold'))->toBe(200)
        ->and(config('product_editor.groups_per_page'))->toBe(20);
});
