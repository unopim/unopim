@props([
    'id',
    'action',
    'buttonLabel',
    'title',
    'nameLabel' => null,
    'namePlaceholder' => null,
    'codeLabel',
    'codePlaceholder' => null,
    'typeLabel' => null,
    'typePlaceholder' => null,
    'typeOptions' => null,
    'validationLabel' => null,
    'validationPlaceholder' => null,
    'validationOptions' => null,
    'rootCategoryLabel' => null,
    'rootCategoryPlaceholder' => null,
    'rootCategoryOptions' => null,
    'localesLabel' => null,
    'localesPlaceholder' => null,
    'localesOptions' => null,
    'currenciesLabel' => null,
    'currenciesPlaceholder' => null,
    'currenciesOptions' => null,
    'saveLabel',
])

@php
    $currentLocaleCode = core()->getRequestedLocaleCode();
@endphp

<button
    type="button"
    class="primary-button"
    @click="$refs['{{ $id }}'].toggle()"
>
    {{ $buttonLabel }}
</button>

<x-admin::form
    ajax
    :track-dirty="false"
    :action="$action"
>
    <x-admin::modal ref="{{ $id }}">
        <x-slot:header>
            <p class="text-lg text-gray-800 dark:text-white font-bold">
                {{ $title }}
            </p>
        </x-slot>

        <x-slot:content>
            @if ($nameLabel)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label
                        class="required w-full"
                        localizable="true"
                        :current-locale-code="$currentLocaleCode"
                    >
                        {{ $nameLabel }}
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="{{ $currentLocaleCode }}[name]"
                        rules="required"
                        v-code-generator="'code'"
                        :label="$nameLabel"
                        :placeholder="$namePlaceholder ?: $nameLabel"
                    />

                    <x-admin::form.control-group.error control-name="{{ $currentLocaleCode }}[name]" />
                </x-admin::form.control-group>
            @endif

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    {{ $codeLabel }}
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="code"
                    rules="required"
                    v-code
                    :label="$codeLabel"
                    :placeholder="$codePlaceholder ?: $codeLabel"
                />

                <x-admin::form.control-group.error control-name="code" />
            </x-admin::form.control-group>

            @if ($typeOptions)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        {{ $typeLabel }}
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="type"
                        rules="required"
                        :label="$typeLabel"
                        :placeholder="$typePlaceholder ?: $typeLabel"
                        :options="$typeOptions"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.error control-name="type" />
                </x-admin::form.control-group>
            @endif

            @if ($validationOptions)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        {{ $validationLabel }}
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="validation"
                        rules="required"
                        :label="$validationLabel"
                        :placeholder="$validationPlaceholder ?: $validationLabel"
                        :options="$validationOptions"
                        track-by="id"
                        label-by="label"
                    />

                    <x-admin::form.control-group.error control-name="validation" />
                </x-admin::form.control-group>
            @endif

            @if ($rootCategoryOptions)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        {{ $rootCategoryLabel }}
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="select"
                        name="root_category_id"
                        rules="required"
                        :label="$rootCategoryLabel"
                        :placeholder="$rootCategoryPlaceholder ?: $rootCategoryLabel"
                        :options="$rootCategoryOptions"
                        track-by="id"
                        label-by="name"
                    />

                    <x-admin::form.control-group.error control-name="root_category_id" />
                </x-admin::form.control-group>
            @endif

            @if ($localesOptions)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        {{ $localesLabel }}
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="multiselect"
                        name="locales"
                        rules="required"
                        :label="$localesLabel"
                        :placeholder="$localesPlaceholder ?: $localesLabel"
                        :options="$localesOptions"
                        track-by="id"
                        label-by="name"
                    />

                    <x-admin::form.control-group.error control-name="locales" />
                </x-admin::form.control-group>
            @endif

            @if ($currenciesOptions)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        {{ $currenciesLabel }}
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="multiselect"
                        name="currencies"
                        rules="required"
                        :label="$currenciesLabel"
                        :placeholder="$currenciesPlaceholder ?: $currenciesLabel"
                        :options="$currenciesOptions"
                        track-by="id"
                        label-by="name"
                    />

                    <x-admin::form.control-group.error control-name="currencies" />
                </x-admin::form.control-group>
            @endif
        </x-slot>

        <x-slot:footer>
            <button
                type="submit"
                class="primary-button"
            >
                {{ $saveLabel }}
            </button>
        </x-slot>
    </x-admin::modal>
</x-admin::form>
