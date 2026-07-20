@props([
    'id',
    'action',
    'buttonLabel',
    'title',
    'nameLabel' => null,
    'namePlaceholder' => null,
    'codeLabel',
    'codePlaceholder' => null,
    'codeHint' => null,
    'typeLabel' => null,
    'typePlaceholder' => null,
    'typeOptions' => null,
    'typeHint' => null,
    'swatchLabel' => null,
    'swatchPlaceholder' => null,
    'swatchOptions' => null,
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
    'toggles' => null,
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
            <v-quick-create-type-fields v-slot="{ selectedType, updateType }">
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
                <x-admin::form.control-group.label
                    class="required"
                    :title="$codeHint"
                >
                    {{ $codeLabel }}

                    @if ($codeHint)
                        <span class="icon-information text-base align-middle cursor-help"></span>
                    @endif
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
                    <x-admin::form.control-group.label
                        class="required"
                        :title="$typeHint"
                    >
                        {{ $typeLabel }}

                        @if ($typeHint)
                            <span class="icon-information text-base align-middle cursor-help"></span>
                        @endif
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
                        @input="updateType($event)"
                    />

                    <x-admin::form.control-group.error control-name="type" />
                </x-admin::form.control-group>

                @if ($swatchOptions)
                    <x-admin::form.control-group v-if="['select', 'multiselect'].includes(selectedType)">
                        <x-admin::form.control-group.label class="required">
                            {{ $swatchLabel }}
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="swatch_type"
                            rules="required"
                            value="text"
                            :label="$swatchLabel"
                            :placeholder="$swatchPlaceholder ?: $swatchLabel"
                            :options="$swatchOptions"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="swatch_type" />
                    </x-admin::form.control-group>
                @endif
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

            @if ($toggles)
                @foreach (json_decode($toggles, true) as $toggle)
                    @php
                        $toggleCondition = empty($toggle['types'])
                            ? 'true'
                            : str_replace('"', "'", json_encode($toggle['types'])).'.includes(selectedType)';
                    @endphp

                    <x-admin::form.control-group
                        class="flex gap-2.5 items-center !mb-2 select-none"
                        v-if="{{ $toggleCondition }}"
                    >
                        <x-admin::form.control-group.control
                            type="checkbox"
                            :id="$toggle['name']"
                            :name="$toggle['name']"
                            value="1"
                            :for="$toggle['name']"
                        />

                        <label
                            class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer"
                            for="{{ $toggle['name'] }}"
                            @if (! empty($toggle['hint'])) title="{{ $toggle['hint'] }}" @endif
                        >
                            {{ $toggle['label'] }}

                            @if (! empty($toggle['hint']))
                                <span class="icon-information text-base align-middle cursor-help"></span>
                            @endif
                        </label>
                    </x-admin::form.control-group>
                @endforeach
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
            </v-quick-create-type-fields>
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

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-quick-create-type-fields-template"
    >
        <div>
            <slot
                :selected-type="selectedType"
                :update-type="updateType"
            ></slot>
        </div>
    </script>

    <script type="module">
        app.component('v-quick-create-type-fields', {
            template: '#v-quick-create-type-fields-template',

            data() {
                return {
                    selectedType: '',
                };
            },

            methods: {
                updateType(value) {
                    try {
                        this.selectedType = JSON.parse(value)?.id ?? value;
                    } catch (error) {
                        this.selectedType = value;
                    }
                },
            },
        });
    </script>
@endPushOnce
