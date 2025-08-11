<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.categories.create.title')
    </x-slot>

    {!! view_render_event('unopim.admin.catalog.categories.create.before') !!}

    <!-- Category Create Form -->
    <x-admin::form
        :action="route('admin.catalog.categories.store')"
        enctype="multipart/form-data"
    >
        {!! view_render_event('unopim.admin.catalog.categories.create.create_form_controls.before') !!}

        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.catalog.categories.create.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Cancel Button -->
                <a
                    href="{{ route('admin.catalog.categories.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.catalog.categories.create.back-btn')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.catalog.categories.create.save-btn')
                </button>
            </div>
        </div>

        <!-- Full Pannel -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">

            <!-- Left Section -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                {!! view_render_event('unopim.admin.catalog.categories.create.card.general.before') !!}

                <!-- General -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.catalog.categories.create.general')
                    </p>

                    <!-- Locales -->
                    <x-admin::form.control-group.control
                        type="hidden"
                        name="locale"
                        :value="core()->getRequestedLocaleCode()"
                    />

                    <!-- Code -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.catalog.categories.create.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="code"
                            rules="required"
                            :value="old('code')"
                            v-code
                        />

                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                </div>
                
                {!! view_render_event('unopim.admin.catalog.categories.create.card.general.after') !!}

                <!-- Left Section -->
                @if (! $leftCategoryFields->isEmpty())
                    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                        <x-admin::categories.dynamic-fields
                            :fields="$leftCategoryFields"
                        >
                        </x-admin::categories.dynamic-fields>
    
                        {!! view_render_event('unopim.admin.catalog.categories.create.card.left-section.after') !!}
                    </div>
                @endif
            </div>

            <!-- Right Section -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full">
                <!-- Parent category -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <h2 class="block mb-2.5 text-base text-gray-800 dark:text-white font-medium leading-6">
                        @lang('admin::app.catalog.categories.create.parent-category')
                    </h2>

                    <!-- Radio select button -->
                    <div class="flex flex-col gap-3 h-[calc(100vh-100px)] overflow-y-auto">
                        <x-admin::tree.category.view
                            input-type="radio"
                            id-field="id"
                            label-field="name"
                            name-field="parent_id"
                            value-field="id"
                            :items="json_encode($categories)"
                            :fallback-locale="config('app.fallback_locale')"
                            :value="old('parent_id') ?? ''"
                        />
                    </div>
                </div>

                @if (! $rightCategoryFields?->isEmpty())
                    {!! view_render_event('unopim.admin.catalog.categories.create.card.accordion.right-section.before') !!}
                    <x-admin::accordion>
                        <x-slot:header>
                            <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.categories.create.right-section')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::categories.dynamic-fields
                                :fields="$rightCategoryFields"
                            >
                            </x-admin::categories.dynamic-fields>
                        </x-slot>
                    </x-admin::accordion>

                    {!! view_render_event('unopim.admin.catalog.categories.create.card.accordion.right-section.after') !!}
                @endif
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.categories.create.create_form_controls.after') !!}

    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.categories.create.after') !!}
</x-admin::layouts>
