<?php

use Illuminate\Support\Facades\Blade;

$modalView = __DIR__.'/../../../src/Resources/views/components/modal/index.blade.php';

/*
 * The DAM package forked this modal into its own `v-full-modal` to gain
 * `noClass`, `preventSubmit` and a scoped content slot. These tests lock the
 * new extension points in as strictly additive: the 32 existing consumers pass
 * none of the new props, so their behaviour and markup must be unchanged.
 */

it('keeps the new modal props optional and defaulted off', function () use ($modalView) {
    $source = file_get_contents($modalView);

    expect($source)->toContain("'noClass'       => false,")
        ->and($source)->toContain("'preventSubmit' => false,")
        ->and($source)->toContain("props: ['isActive', 'type', 'clip', 'noClass', 'preventSubmit'],");
});

it('gates the Enter-key guard on preventSubmit so default modals still submit', function () use ($modalView) {
    $source = file_get_contents($modalView);

    expect($source)->toContain('handleKeydown(event)')
        ->and($source)->toContain("event.key === 'Enter' && this.isOpen && this.preventSubmit")
        ->and($source)->toContain("window.addEventListener('keydown', this.handleKeydown);")
        ->and($source)->toContain("window.removeEventListener('keydown', this.handleKeydown);");
});

it('renders default markup unchanged when no new props are supplied', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin::modal>
            <x-slot:header>Title</x-slot:header>
            <x-slot:content>Body</x-slot:content>
        </x-admin::modal>
    BLADE);

    expect($html)->toContain('<v-modal')
        ->and($html)->toContain('is-active=""')
        ->and($html)->not->toContain(':no-class')
        ->and($html)->not->toContain(':prevent-submit');
});

it('emits the opt-in bindings only when the new props are passed', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin::modal :noClass="true" :preventSubmit="true">
            <x-slot:content>Body</x-slot:content>
        </x-admin::modal>
    BLADE);

    expect($html)->toContain(':no-class="true"')
        ->and($html)->toContain(':prevent-submit="true"');
});

it('exposes the toggle/isOpen scope on the content slot for pickers', function () use ($modalView) {
    $source = file_get_contents($modalView);

    expect($source)->toContain('<template v-slot:content="{ toggle, isOpen }">')
        ->and($source)->toContain('name="content"')
        ->and($source)->toContain(':toggle="toggle"');
});

it('drops the card chrome only under noClass, preserving it by default', function () use ($modalView) {
    $source = file_get_contents($modalView);

    expect($source)->toContain("noClass ? '' : 'flex min-h-full items-end justify-center")
        ->and($source)->toContain("noClass\n                                ? 'w-full h-full'");
});
