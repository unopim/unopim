<?php

it('returns datagrid json on ajax and the grid view otherwise', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.resource-kit-items.index'))
        ->assertOk()
        ->assertViewIs('resource::index');

    $this->getJson(route('admin.resource-kit-items.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonStructure(['records', 'columns', 'meta']);
});
