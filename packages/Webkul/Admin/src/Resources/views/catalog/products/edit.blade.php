<x-admin::layouts.with-history>
    <x-slot:entityName>
        product
    </x-slot>
    <x-slot:title>
        @lang('admin::app.catalog.products.edit.title')
    </x-slot>

    {!! view_render_event('unopim.admin.catalog.product.edit.before', ['product' => $product]) !!}
    <x-admin::graphs.radial-progress />

    <x-admin::form
        method="PUT"
        enctype="multipart/form-data"
    >
        {!! view_render_event('unopim.admin.catalog.product.edit.actions.before', ['product' => $product]) !!}

        <input type="hidden" name="sku" value="{{ $product->sku }}">

        <!-- Page Header -->
        <div class="grid gap-2.5">
            <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
                <div class="grid gap-1.5">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold leading-6">
                        @lang('admin::app.catalog.products.edit.title') | SKU: {{ $product->sku }}
                    </p>
                </div>

                <div class="flex gap-x-2.5 items-center">
                    {!! view_render_event('unopim.pdf.product.edit.actions.before', ['product' => $product]) !!}

                    <!-- Back Button -->
                    <a
                        href="{{ route('admin.catalog.products.index') }}"
                        class="transparent-button"
                    >
                        @lang('admin::app.account.edit.back-btn')
                    </a>

                    <!-- Save Button -->
                    <button class="primary-button">
                        @lang('admin::app.catalog.products.edit.save-btn')
                    </button>
                </div>
            </div>
        </div>

        @php
            $channels = core()->getAllChannels();

            $currentChannel = core()->getRequestedChannel() ?? core()->getDefaultChannel();

            $currentLocale = core()->getRequestedLocale();

            $score = $scores[$currentLocale->id] ?? '';

            $currentLocale = $currentChannel->locales->contains($currentLocale) ? $currentLocale : $currentChannel->locales->first();
        @endphp

        <div class="flex  gap-4 justify-between items-center mt-7 max-md:flex-wrap">
            <div class="flex gap-x-1 items-center">
                <!-- Channel Switcher -->
                <x-admin::dropdown>
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <button
                        type="button"
                            class="
                            flex gap-x-1 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-violet-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50"
                        >
                            <span class="icon-channel   text-2xl"></span>

                            {{ ! empty($currentChannel->name) ? $currentChannel->name : '[' . $currentChannel->code . ']' }}

                            <input type="hidden" name="channel" value="{{ $currentChannel->code }}"/>

                            <span class="icon-chevron-down   text-2xl"></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:content class="!p-0">
                        @foreach ($channels as $channel)
                            <a
                                href="?{{ Arr::query(['channel' => $channel->code, 'locale' => $currentLocale?->code]) }}"
                                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 dark:text-white"
                            >
                            {{ ! empty($channel->name) ? $channel->name : '[' . $channel->code . ']' }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>

                <!-- Locale Switcher -->
                <x-admin::dropdown>
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="flex gap-x-1 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-violet-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50 "
                        >
                            <span class="icon-language text-2xl"></span>

                            {{ $currentLocale?->name }}

                            <input type="hidden" name="locale" value="{{ $currentLocale?->code }}"/>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:content class="!p-0">
                        @foreach ($currentChannel->locales->sortBy('name') as $locale)
                            <a
                                href="?{{ Arr::query(['channel' => $currentChannel->code, 'locale' => $locale->code]) }}"
                                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale->code == $currentLocale?->code ? 'bg-gray-100 dark:bg-cherry-800' : ''}}"
                            >
                                {{ $locale->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>

                @if (isset($score['score']))
                    <!-- Completeness Dropdown -->
                    <x-admin::dropdown>
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="flex gap-x-2 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer appearance-none transition-all hover:bg-violet-50 dark:hover:bg-cherry-900 text-gray-600 dark:text-slate-50"
                            >
                                <span class="icon-activity text-2xl"></span>

                                <div class="font-semibold text-gray-600 dark:text-white">
                                    <span class="text-xl">% </span> @lang('completeness::app.catalog.products.edit.completeness.title')
                                </div>

                                <span class="inline-flex items-center px-2 py-1 rounded-md text-sm font-semibold bg-violet-700 text-white">
                                    {{ $score['score'].'%' }}
                                </span>
                            </button>
                        </x-slot>

                        <!-- Dropdown Content -->
                        <x-slot:content class="!p-0">
                            <div class="p-2">
                                <div class="p-2 font-semibold border-b dark:border-cherry-800">
                                    <v-radial-progress
                                        :score="{{ $averageScore }}"
                                        label="{{ trans('completeness::app.catalog.products.edit.completeness.title') }}"
                                        sub-title="{{ trans('completeness::app.catalog.products.edit.completeness.subtitle') }}"
                                        :radius="16"
                                    />
                                </div>

                                {{-- Per-locale completeness --}}
                                @foreach ($currentChannel->locales->sortBy('name') as $locale)
                                    @php
                                        $localeScore = $scores[$locale->id] ?? null;
                                    @endphp

                                    <div class="p-2 text-sm dark:border-cherry-800">
                                        @if (! is_null($localeScore))
                                            <v-radial-progress :score="{{ $localeScore['score'] }}" :label="'{{ $locale->name }}'" :radius="14" />
                                        @else
                                            <div class="text-sm text-gray-400 italic">{{ $locale->name }}: N/A</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </x-slot>
                    </x-admin::dropdown>
                @endif

                @if ($score['missing_count'] ?? false)
                    <div class="text-gray-600 dark:text-white ltr:ml-2.5">
                        <span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span>
                        {{ $score['missing_count'] }} @lang('completeness::app.catalog.products.edit.required-attributes')
                    </div>
                @endif
            </div>

            @include('admin::catalog.products.edit.more-actions.index')
        </div>

        {!! view_render_event('unopim.admin.catalog.product.edit.actions.after', ['product' => $product]) !!}

        <!-- body content -->
        {!! view_render_event('unopim.admin.catalog.product.edit.form.before', ['product' => $product]) !!}

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="left-column flex flex-col gap-2 flex-1 max-xl:flex-auto">
                @foreach ($product->attribute_family->familyGroups()->orderBy('position')->get() as $group)
                    {!! view_render_event('unopim.admin.catalog.product.edit.form.column_before', ['product' => $product]) !!}

                    <div class="flex flex-col gap-2">
                        @php
                            $customAttributes = $product->getEditableAttributes($group);

                            $groupLabel = $group->name;
                            $groupLabel = empty($groupLabel) ? "[{$group->code}]" : $groupLabel;
                        @endphp

                        @if (count($customAttributes))
                            {!! view_render_event('unopim.admin.catalog.product.edit.form.' . $group->code . '.before', ['product' => $product]) !!}

                            <div class="relative p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                                <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                                    {{ $groupLabel }}
                                </p>

                                <x-admin::products.dynamic-attribute-fields
                                    :fields="$customAttributes"
                                    :fieldValues="$product->values"
                                    :currentLocaleCode="$currentLocale->code"
                                    :currentChannelCode="$currentChannel->code"
                                    :channelCurrencies="$currentChannel->currencies"
                                    :variantFields="$product?->parent ? $product->parent->super_attributes->pluck('code')->toArray() : []"
                                    :completeness-attributes="$requiredAttributes"
                                    fieldsWrapper="values"
                                >
                                </x-admin::products.dynamic-attribute-fields>

                            </div>

                            {!! view_render_event('unopim.admin.catalog.product.edit.form.' . $group->code . '.after', ['product' => $product]) !!}
                        @endif

                        <!-- Product Type View Blade File -->
                    </div>

                    {!! view_render_event('unopim.admin.catalog.product.edit.form.column_after', ['product' => $product]) !!}
                @endforeach
            </div>
            <div class="right-column flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                <!-- Product Info View Blade File -->
                @include('admin::catalog.products.edit.product-info')

                <!-- Categories View Blade File -->
                @include('admin::catalog.products.edit.categories', ['currentLocaleCode' => $currentLocale?->code, 'productCategories' => $product->values['categories'] ?? []])

                @includeIf('admin::catalog.products.edit.types.' . $product->type)

                <!-- Related, Cross Sells, Up Sells View Blade File -->
                @include('admin::catalog.products.edit.links', [
                    'upSellAssociations'    => $product->values['associations']['up_sells'] ?? [],
                    'crossSellAssociations' => $product->values['associations']['cross_sells'] ?? [],
                    'relatedAssociations'   => $product->values['associations']['related_products'] ?? [],
                ])

                <!-- Include Product Type Additional Blade Files If Any -->
                @foreach ($product->getTypeInstance()->getAdditionalViews() as $view)
                    @includeIf($view)
                @endforeach
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.product.edit.form.after', ['product' => $product]) !!}
    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.product.edit.after', ['product' => $product]) !!}
</x-admin::layouts.with-history>
