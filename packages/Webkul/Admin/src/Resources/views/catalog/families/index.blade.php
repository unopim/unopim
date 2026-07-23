@php
    $currentLocaleCode = core()->getRequestedLocaleCode();

    $families = app(\Webkul\Attribute\Repositories\AttributeFamilyRepository::class)
        ->all()
        ->map(fn ($family) => [
            'id'    => $family->id,
            'label' => $family->name ?: '['.$family->code.']',
        ])
        ->values();
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.families.index.title')
    </x-slot>

    <x-admin::layouts.page-header
        :title="trans('admin::app.catalog.families.index.title')"
    >
        <x-slot:actions>
            {!! view_render_event('unopim.admin.catalog.families.create.before') !!}

            @if (bouncer()->hasPermission('catalog.families.create'))
                <v-create-family-form></v-create-family-form>
            @endif

            {!! view_render_event('unopim.admin.catalog.families.create.after') !!}
        </x-slot>
    </x-admin::layouts.page-header>

    {!! view_render_event('unopim.admin.catalog.families.list.before') !!}

    <x-admin::datagrid src="{{ route('admin.catalog.families.index') }}" />

    {!! view_render_event('unopim.admin.catalog.families.list.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-create-family-form-template">
            <div>
                <button
                    type="button"
                    class="primary-button"
                    @click="$refs.familyCreateModal.toggle()"
                >
                    @lang('admin::app.catalog.families.index.add')
                </button>

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, create)" ref="familyCreateForm">
                        <x-admin::modal ref="familyCreateModal">
                            <x-slot:header>
                                <p class="text-lg text-gray-800 dark:text-white font-bold">
                                    @lang('admin::app.catalog.families.index.create.title')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                {!! view_render_event('unopim.admin.catalog.families.create_form.controls.before') !!}

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label
                                        class="required w-full"
                                        localizable="true"
                                        :current-locale-code="$currentLocaleCode"
                                    >
                                        @lang('admin::app.catalog.families.index.create.name')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="{{ $currentLocaleCode }}[name]"
                                        rules="required"
                                        v-code-generator="'code'"
                                        :label="trans('admin::app.catalog.families.index.create.name')"
                                        :placeholder="trans('admin::app.catalog.families.index.create.enter-name')"
                                    />

                                    <x-admin::form.control-group.error control-name="{{ $currentLocaleCode }}[name]" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.catalog.families.index.create.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        rules="required"
                                        v-code
                                        :label="trans('admin::app.catalog.families.index.create.code')"
                                        :placeholder="trans('admin::app.catalog.families.index.create.enter-code')"
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                @if ($families->isNotEmpty())
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label
                                            :title="trans('admin::app.catalog.families.index.create.based-on-info')"
                                        >
                                            @lang('admin::app.catalog.families.index.create.based-on')

                                            <span class="icon-information text-base align-middle cursor-help"></span>
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="based_on"
                                            :label="trans('admin::app.catalog.families.index.create.based-on')"
                                            :placeholder="trans('admin::app.catalog.families.index.create.select-family')"
                                            :options="json_encode($families)"
                                            track-by="id"
                                            label-by="label"
                                        />

                                        <x-admin::form.control-group.error control-name="based_on" />
                                    </x-admin::form.control-group>
                                @endif

                                {!! view_render_event('unopim.admin.catalog.families.create_form.controls.after') !!}
                            </x-slot>

                            <x-slot:footer>
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('admin::app.catalog.families.index.create.save-btn')
                                </button>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-family-form', {
                template: '#v-create-family-form-template',

                methods: {
                    create(params, { setErrors }) {
                        this.$axios.post("{{ route('admin.catalog.families.store') }}", new FormData(this.$refs.familyCreateForm))
                            .then((response) => {
                                this.$navigate(response.data.data.redirect_url);
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
