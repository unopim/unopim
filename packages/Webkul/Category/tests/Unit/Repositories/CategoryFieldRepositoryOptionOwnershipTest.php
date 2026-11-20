<?php

use Illuminate\Validation\ValidationException;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;
use Webkul\Category\Repositories\CategoryFieldRepository;

beforeEach(function (): void {
    $this->field = CategoryField::factory()->create(['type' => 'select']);
    $this->other = CategoryField::factory()->create(['type' => 'select']);
});

it('keeps options added on a category field update on that field', function (): void {
    app(CategoryFieldRepository::class)->update([
        'options' => [
            'new_1' => ['code' => 'repo_cf_new', 'isNew' => 'true', 'isDelete' => 'false', 'category_field_id' => $this->other->id],
        ],
    ], $this->field->id);

    expect(CategoryFieldOption::where('code', 'repo_cf_new')->pluck('category_field_id')->map(fn (mixed $id): int => (int) $id)->all())
        ->toBe([$this->field->id]);
});

it('keeps existing options on their category field when an update names another field', function (): void {
    $option = CategoryFieldOption::factory()->create(['code' => 'repo_cf_existing', 'category_field_id' => $this->field->id]);

    app(CategoryFieldRepository::class)->update([
        'options' => [
            $option->id => ['code' => $option->code, 'isNew' => 'false', 'isDelete' => 'false', 'category_field_id' => $this->other->id],
        ],
    ], $this->field->id);

    expect((int) $option->fresh()->category_field_id)->toBe($this->field->id);
});

it('rejects options of another category field in a category field update', function (): void {
    $foreignDelete = CategoryFieldOption::factory()->create(['code' => 'repo_cf_foreign_delete', 'category_field_id' => $this->other->id]);
    $foreignUpdate = CategoryFieldOption::factory()->create(['code' => 'repo_cf_foreign_update', 'category_field_id' => $this->other->id]);

    expect(fn () => app(CategoryFieldRepository::class)->update([
        'options' => [
            $foreignDelete->id => ['isNew' => 'false', 'isDelete' => 'true'],
            $foreignUpdate->id => ['code' => 'repo_cf_hijacked', 'isNew' => 'false', 'isDelete' => 'false'],
        ],
    ], $this->field->id))->toThrow(ValidationException::class);

    expect(CategoryFieldOption::whereKey($foreignDelete->id)->exists())->toBeTrue()
        ->and($foreignUpdate->fresh()->code)->toBe('repo_cf_foreign_update');
});
