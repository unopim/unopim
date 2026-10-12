<?php

it('should expose user-friendly page titles for attribute families, prompts, and system prompts (Issue #728)', function () {
    expect(trans('admin::app.catalog.families.index.title'))->toBe('Attribute Families');
    expect(trans('admin::app.configuration.prompt.index.title'))->toBe('Prompts');
    expect(trans('admin::app.configuration.system-prompt.index.title'))->toBe('System Prompts');
});
