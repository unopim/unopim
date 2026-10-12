<?php

use Webkul\Category\Models\Category;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('renders the history modal on the tree workspace so a version can be opened', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    $this->get(route('admin.catalog.categories.index', ['category' => $category->id, 'history' => 1]))
        ->assertOk()
        ->assertSee('v-modal-history', false);
});

it('falls back to the overview when the requested category no longer exists', function () {
    Category::factory()->create(['parent_id' => null]);

    $missingId = (int) Category::query()->max('id') + 1000;

    $this->get(route('admin.catalog.categories.index', ['category' => $missingId, 'history' => 1]))
        ->assertOk()
        ->assertSee(trans('admin::app.catalog.categories.browse.trees'), false);
});

it('keeps the list view on its own create flow', function () {
    $this->get(route('admin.catalog.categories.index', ['view' => 'list']))
        ->assertOk()
        ->assertSee(route('admin.catalog.categories.create'), false)
        ->assertDontSee(route('admin.catalog.categories.index', ['panel' => 'create']), false);
});

it('keeps the tree view on the panel create flow', function () {
    $this->get(route('admin.catalog.categories.index', ['view' => 'tree']))
        ->assertOk()
        ->assertSee(route('admin.catalog.categories.index', ['panel' => 'create']), false);
});

it('labels the create button generically since a parent can be picked', function () {
    $response = $this->get(route('admin.catalog.categories.index', ['view' => 'list']))->assertOk();

    expect($response->getContent())
        ->toContain(route('admin.catalog.categories.create').'">')
        ->toContain(trans('admin::app.catalog.categories.browse.add-category'));
});

it('submits the picked parent from a field that survives the drawer closing', function () {
    $parent = Category::factory()->create(['parent_id' => null]);

    $response = $this->get(route('admin.catalog.categories.index', [
        'panel'     => 'create',
        'parent_id' => $parent->id,
    ]))->assertOk();

    expect($response->getContent())->toMatch(
        '/<input\s+type="hidden"\s+name="parent_id"\s+ref="parentIdField"\s+value="'.$parent->id.'"/'
    );
});

it('carries the current parent into the edit panel field', function () {
    $parent = Category::factory()->create(['parent_id' => null]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $response = $this->get(route('admin.catalog.categories.index', ['category' => $child->id]))->assertOk();

    expect($response->getContent())->toMatch(
        '/<input\s+type="hidden"\s+name="parent_id"\s+ref="parentIdField"\s+value="'.$parent->id.'"/'
    );
});

it('keeps the picker inputs out of the submitted payload', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    $response = $this->get(route('admin.catalog.categories.index', ['category' => $category->id]))->assertOk();

    expect($response->getContent())->toContain('name="parent_id_picker"');
});

it('creates the category under the picked parent', function () {
    $parent = Category::factory()->create(['parent_id' => null]);

    $this->post(route('admin.catalog.categories.store'), [
        'code'      => 'picked_child',
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => $parent->id,
    ])->assertSessionHas('success');

    $this->assertDatabaseHas('categories', ['code' => 'picked_child', 'parent_id' => $parent->id]);
});

it('rejects a parent that does not exist', function () {
    $this->post(route('admin.catalog.categories.store'), [
        'code'      => 'orphan_child',
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => 999999,
    ])->assertSessionHasErrors('parent_id');

    $this->assertDatabaseMissing('categories', ['code' => 'orphan_child']);
});

it('moves a category back to root level', function () {
    $parent = Category::factory()->create(['parent_id' => null]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $this->put(route('admin.catalog.categories.update', $child->id), [
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => '',
    ])->assertSessionHas('success');

    $this->assertDatabaseHas('categories', ['id' => $child->id, 'parent_id' => null]);
});

it('stays on the newly created category when saved from the panel', function () {
    $parent = Category::factory()->create(['parent_id' => null]);

    $this->post(route('admin.catalog.categories.store'), [
        'code'      => 'panel_child',
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => $parent->id,
        'panel'     => 1,
    ])->assertRedirect(route('admin.catalog.categories.index', [
        'category' => Category::where('code', 'panel_child')->value('id'),
        'locale'   => core()->getRequestedLocaleCode(),
    ]));
});

it('stays on the edited category when saved from the panel', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    $this->put(route('admin.catalog.categories.update', $category->id), [
        'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'panel'  => 1,
    ])->assertRedirect(route('admin.catalog.categories.index', [
        'category' => $category->id,
        'locale'   => core()->getRequestedLocaleCode(),
    ]));
});

it('leaves the legacy pages on their own redirects', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    $this->put(route('admin.catalog.categories.update', $category->id), [
        'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
    ])->assertRedirect(route('admin.catalog.categories.edit', [
        'id'     => $category->id,
        'locale' => core()->getRequestedLocaleCode(),
    ]));
});

it('moves through the tree without a hard page load', function () {
    $view = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/views/components/tree/category/view.blade.php'));

    expect($view)->not->toContain('window.location.href = url.toString()');
});

it('updates the parent field and closes the drawer as soon as a category is picked', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    $content = $this->get(route('admin.catalog.categories.index', ['category' => $category->id]))
        ->assertOk()
        ->getContent();

    preg_match('/select-node="([^"]*)"/', $content, $matches);

    expect($matches[1] ?? '')
        ->toContain('$refs.parentPathLabel.textContent = $event.path')
        ->toContain('$refs.parentDrawer.close()')
        ->not->toContain('CustomEvent');
});

it('updates the parent field and closes the drawer when root level is picked', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    $content = $this->get(route('admin.catalog.categories.index', ['category' => $category->id]))
        ->assertOk()
        ->getContent();

    preg_match('/name="parent_id_picker"[^>]*@change="([^"]*)"/s', $content, $matches);

    expect($matches[1] ?? '')
        ->toContain('$refs.parentPathLabel.textContent = $refs.rootLevelLabel.textContent.trim()')
        ->toContain('$refs.parentDrawer.close()')
        ->not->toContain('CustomEvent');
});
