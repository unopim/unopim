<x-admin::layouts.with-history>
    <x-slot:entityName>
        category
    </x-slot>
    
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.categories.edit.title')
    </x-slot>

    @php
        $currentLocale = core()->getRequestedLocale();

        $categoryCount = $categories->count();

        $isEmptyRightSection = $rightCategoryFields?->isEmpty();
    @endphp

    {!! view_render_event('unopim.admin.catalog.categories.edit.before') !!}

    <!-- Category Edit Form -->
    <x-admin::form
        :action="route('admin.catalog.categories.update', $category->id)"
        enctype="multipart/form-data"
        method="PUT"
    >

        {!! view_render_event('unopim.admin.catalog.categories.edit.edit_form_controls.before', ['category' => $category]) !!}

        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.catalog.categories.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Back Button -->
                <a
                    href="{{ route('admin.catalog.categories.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.catalog.categories.edit.back-btn')
                </a>

                <!-- Save Button -->
                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.catalog.categories.edit.save-btn')
                </button>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="flex  gap-4 justify-between items-center mt-2 max-md:flex-wrap">
            <div class="flex gap-x-1 items-center">
                <!-- Locale Switcher -->
                @php $allActiveLocales = core()->getAllActiveLocales(); @endphp

                <x-admin::dropdown :class="$allActiveLocales->count() <= 1 ? 'hidden' : ''">
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="flex gap-x-1 items-center px-3 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-violet-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50"
                        >
                            <span class="icon-language text-2xl"></span>

                            {{ $currentLocale->name }}

                            <input type="hidden" name="locale" value="{{ $currentLocale->code }}"/>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:content class="!p-0">
                        @foreach ($allActiveLocales as $locale)
                            <a
                                href="?{{ Arr::query(['locale' => $locale->code]) }}"
                                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale->code == $currentLocale->code ? 'bg-gray-100 dark:bg-cherry-800' : ''}}"
                            >
                                {{ $locale->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>
            </div>
        </div>

        <!-- Full Pannel -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Section -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                {!! view_render_event('unopim.admin.catalog.categories.edit.card.general.before', ['category' => $category]) !!}

                <!-- General -->
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.catalog.categories.edit.general')
                    </p>

                    <!-- Code -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.catalog.categories.edit.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            class="cursor-not-allowed"
                            name="code"
                            :disabled="(boolean) $category->code"
                            rules="required"
                            :value="$category->code"
                        />

                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('unopim.admin.catalog.categories.edit.card.general.after', ['category' => $category]) !!}

                <!-- Left Section -->
                @if (! $leftCategoryFields->isEmpty())
                    <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                        <x-admin::categories.dynamic-fields
                            :fields="$leftCategoryFields"
                            :fieldValues="$category->additional_data"
                        >
                        </x-admin::categories.dynamic-fields>
                    </div>
                @endif
            </div>

            <!-- Right Section -->
            @if (! $isEmptyRightSection || $categoryCount)
                <div class="flex flex-col gap-2 w-[360px] max-w-full">
                    @if ($categoryCount)
                        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                            <div>
                                <!-- Parent category -->
                                <h2 class="block mb-2.5 text-base text-gray-800 dark:text-white font-medium leading-6">
                                    @lang('admin::app.catalog.categories.edit.select-parent-category')
                                </h2>

                                <!-- Radio select button -->
                                <div class="flex flex-col gap-3">
                                    <x-admin::tree.view
                                        input-type="radio"
                                        name-field="parent_id"
                                        label-field="name"
                                        value-field="id"
                                        id-field="id"
                                        :current-category="$category->id"
                                        :expanded-branch="json_encode($branchToParent)"
                                        :items="json_encode($categories)"
                                        :value="old('parent_id') ?? json_encode($category->parent_id)"
                                        :fallback-locale="config('app.fallback_locale')"
                                    >
                                    </x-admin::tree.view>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (! $isEmptyRightSection)
                            {!! view_render_event('unopim.admin.catalog.categories.edit.card.accordion.settings.before', ['category' => $category]) !!}

                            <x-admin::accordion>
                                <x-slot:header>
                                    <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                        @lang('admin::app.catalog.categories.edit.right-section')
                                    </p>
                                </x-slot>

                                <x-slot:content>
                                    <x-admin::categories.dynamic-fields
                                        :fields="$rightCategoryFields"
                                        :fieldValues="$category->additional_data"
                                    >
                                    </x-admin::categories.dynamic-fields>
                                </x-slot>
                            </x-admin::accordion>

                            {!! view_render_event('unopim.admin.catalog.categories.edit.card.accordion.settings.after', ['category' => $category]) !!}
                    @endif
                </div>
            @endIf
        </div>

        {!! view_render_event('unopim.admin.catalog.categories.edit.edit_form_controls.after', ['category' => $category]) !!}

    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.categories.edit.after') !!}

</x-admin::layouts.with-history>
