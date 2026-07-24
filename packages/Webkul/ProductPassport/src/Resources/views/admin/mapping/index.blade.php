@php
    $attributeTypes = collect(config('attribute_types'))
        ->map(fn ($type) => [
            'id'    => $type['key'],
            'label' => trans($type['name']),
        ])
        ->values()
        ->toJson();

    $swatchOptions = collect(\Webkul\Attribute\Enums\SwatchTypeEnum::getValues())
        ->map(fn ($swatchType) => [
            'id'    => $swatchType,
            'label' => trans('admin::app.catalog.attributes.edit.option.'.$swatchType),
        ])
        ->values()
        ->toJson();

    $creationToggles = collect([
        [
            'name'  => 'is_unique',
            'label' => trans('admin::app.catalog.attributes.edit.is-unique'),
            'hint'  => trans('admin::app.catalog.attributes.create.is-unique-hint'),
            'types' => ['text'],
        ],
        [
            'name'  => 'value_per_locale',
            'label' => trans('admin::app.catalog.attributes.edit.value-per-locale'),
            'hint'  => trans('admin::app.catalog.attributes.create.value-per-locale-hint'),
        ],
        [
            'name'  => 'value_per_channel',
            'label' => trans('admin::app.catalog.attributes.edit.value-per-channel'),
            'hint'  => trans('admin::app.catalog.attributes.create.value-per-channel-hint'),
        ],
    ])->toJson();
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('passport::app.mapping.title')
    </x-slot>

    {{--
        The header sits outside the mapping form so the quick-create modal — which
        renders its own <x-admin::form> — is a sibling, never a nested form.
    --}}
    <x-admin::page-header
        :title="trans('passport::app.mapping.title')"
        :subtitle="trans('passport::app.mapping.info')"
        :back="route('admin.catalog.passports.index')"
    >
        <x-slot:actions>
            @if (bouncer()->hasPermission('catalog.passport.mapping'))
                <x-admin::catalog.quick-create-modal
                    id="passportFieldCreateModal"
                    :action="route('admin.catalog.passports.mapping.field.store')"
                    :button-label="trans('passport::app.mapping.add-field')"
                    :title="trans('passport::app.mapping.add-field-title')"
                    :name-label="trans('admin::app.catalog.attributes.index.datagrid.name')"
                    :name-placeholder="trans('admin::app.catalog.attributes.index.datagrid.name')"
                    :code-label="trans('admin::app.catalog.attributes.create.code')"
                    :code-placeholder="trans('admin::app.catalog.attributes.create.code')"
                    :code-hint="trans('admin::app.catalog.attributes.create.code-hint')"
                    :type-label="trans('admin::app.catalog.attributes.create.type')"
                    :type-placeholder="trans('admin::app.catalog.attributes.create.select-type')"
                    :type-options="$attributeTypes"
                    :type-hint="trans('admin::app.catalog.attributes.create.type-hint')"
                    :swatch-label="trans('admin::app.catalog.attributes.create.swatch')"
                    :swatch-placeholder="trans('admin::app.catalog.attributes.create.swatch')"
                    :swatch-options="$swatchOptions"
                    :toggles="$creationToggles"
                    :save-label="trans('admin::app.catalog.attributes.create.save-btn')"
                />
            @endif
        </x-slot>
    </x-admin::page-header>

    @include('passport::admin.partials.tabs', ['active' => 'mapping'])

    <x-admin::form
        :action="route('admin.catalog.passports.mapping.update')"
        method="PUT"
        :ajax="true"
    >
        <div class="flex justify-end">
            <button type="submit" class="primary-button">
                @lang('passport::app.mapping.save-btn')
            </button>
        </div>

        <div class="mt-4 grid gap-4 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            @foreach ($passportFields as $field)
                {{-- 2-column row: field name on the left, source select on the right;
                     stacks vertically on small screens (desktop-first max-sm idiom). --}}
                <x-admin::form.control-group
                    class="!mb-0 grid grid-cols-2 max-sm:grid-cols-1 gap-x-6 gap-y-2 items-center"
                >
                    <x-admin::form.control-group.label class="!mb-0">
                        {{ $field->getTranslatedValueWithFallback('name') ?: $field->code }}
                    </x-admin::form.control-group.label>

                    <div>
                        <x-admin::form.control-group.control
                            type="select"
                            :name="'mapping[' . $field->code . ']'"
                            :value="$mapping[$field->code] ?? ''"
                            :label="$field->getTranslatedValueWithFallback('name') ?: $field->code"
                            :placeholder="trans('passport::app.mapping.select-source')"
                            :options="json_encode($sourceOptions[$field->code] ?? [])"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error :control-name="'mapping[' . $field->code . ']'" />
                    </div>
                </x-admin::form.control-group>
            @endforeach
        </div>
    </x-admin::form>
</x-admin::layouts>
