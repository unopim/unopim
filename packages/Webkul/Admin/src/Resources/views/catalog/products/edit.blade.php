<x-admin::layouts.with-history>
    <x-slot:entityName>
        product
    </x-slot>
    <x-slot:title>
        @lang('admin::app.catalog.products.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        {!! view_render_event('unopim.admin.catalog.product.edit.actions.before', ['product' => $product]) !!}

        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.catalog.products.edit.title') . ' | SKU: ' . $product->sku"
            :back-url="route('admin.catalog.products.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :sticky="false"
        >
            <x-slot:beforeActions>
                {!! view_render_event('unopim.pdf.product.edit.actions.before', ['product' => $product]) !!}
            </x-slot>
        </x-admin::layouts.edit-page-header>
    </x-slot>

    {!! view_render_event('unopim.admin.catalog.product.edit.before', ['product' => $product]) !!}
    <x-admin::graphs.radial-progress />

    <x-admin::form
        method="PUT"
        enctype="multipart/form-data"
        ajax
    >
        <input type="hidden" name="sku" value="{{ $product->sku }}">

        @php
            $channels = core()->getAllChannels();

            $currentChannel = core()->getRequestedChannel() ?? core()->getDefaultChannel();

            $currentLocale = core()->getRequestedLocale();

            $score = $scores[$currentLocale->id] ?? '';

            $currentLocale = $currentChannel->locales->contains($currentLocale) ? $currentLocale : $currentChannel->locales->first();
        @endphp

        <div class="flex  gap-4 justify-between items-center mt-7 max-md:flex-wrap">
            <div class="flex gap-x-1 items-center">
                <x-admin::dropdown>
                    <x-slot:toggle>
                        <button
                        type="button"
                            class="
                            flex gap-x-1 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-primary-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50"
                        >
                            <span class="icon-channel   text-2xl"></span>

                            {{ ! empty($currentChannel->name) ? $currentChannel->name : '[' . $currentChannel->code . ']' }}

                            <input type="hidden" name="channel" value="{{ $currentChannel->code }}"/>

                            <span class="icon-chevron-down   text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:content class="!p-0">
                        @foreach ($channels as $channel)
                            <a
                                href="?{{ Arr::query(['channel' => $channel->code, 'locale' => $currentLocale?->code]) }}"
                                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 dark:text-white"
                            >
                            {{ ! empty($channel->name) ? $channel->name : '[' . $channel->code . ']' }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>

                <x-admin::dropdown>
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="flex gap-x-1 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-primary-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50 "
                        >
                            <span class="icon-language text-2xl"></span>

                            {{ $currentLocale?->name }}

                            <input type="hidden" name="locale" value="{{ $currentLocale?->code }}"/>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:content class="!p-0">
                        @foreach ($currentChannel->locales->sortBy('name') as $locale)
                            <a
                                href="?{{ Arr::query(['channel' => $currentChannel->code, 'locale' => $locale->code]) }}"
                                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale->code == $currentLocale?->code ? 'bg-gray-100 dark:bg-cherry-800' : ''}}"
                            >
                                {{ $locale->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>

                @if (isset($score['score']))
                    <x-admin::dropdown>
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="flex gap-x-2 items-center px-1 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer appearance-none transition-all hover:bg-primary-50 dark:hover:bg-cherry-900 text-gray-600 dark:text-slate-50"
                            >
                                <span class="icon-activity text-2xl"></span>

                                <div class="font-semibold text-gray-600 dark:text-white">
                                    <span class="text-xl">% </span> @lang('completeness::app.catalog.products.edit.completeness.title')
                                </div>

                                <span class="inline-flex items-center px-2 py-1 rounded-md text-sm font-semibold bg-primary-700 text-white">
                                    {{ $score['score'].'%' }}
                                </span>
                            </button>
                        </x-slot>

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

        {!! view_render_event('unopim.admin.catalog.product.edit.form.before', ['product' => $product]) !!}

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="left-column flex flex-col gap-2 flex-1 max-xl:flex-auto">
                @foreach ($product->attribute_family->familyGroups()->orderBy('position')->get() as $group)
                    {!! view_render_event('unopim.admin.catalog.product.edit.form.column_before', ['product' => $product]) !!}

                        @php
                            $customAttributes = $product->getEditableAttributes($group);

                            $groupLabel = $group->name;
                            $groupLabel = empty($groupLabel) ? "[{$group->code}]" : $groupLabel;
                        @endphp

                        @if (count($customAttributes))
                            <div class="flex flex-col gap-2">

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
                            </div>
                        @endif

                    {!! view_render_event('unopim.admin.catalog.product.edit.form.column_after', ['product' => $product]) !!}
                @endforeach
            </div>
            <div class="right-column flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                @include('admin::catalog.products.edit.product-info')

                @include('admin::catalog.products.edit.categories', ['currentLocaleCode' => $currentLocale?->code, 'productCategories' => $product->values['categories'] ?? []])

                @includeIf('admin::catalog.products.edit.types.' . $product->type)

                <!-- Related, Cross Sells, Up Sells View Blade File -->
                @include('admin::catalog.products.edit.links', ['linkedProducts' => $linkedProducts])

                @foreach ($product->getTypeInstance()->getAdditionalViews() as $view)
                    @includeIf($view)
                @endforeach
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.product.edit.form.after', ['product' => $product]) !!}
    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.product.edit.after', ['product' => $product]) !!}

    @pushOnce('scripts')
        {{-- Give the sticky edit header a solid white background once the page is scrolled
             (Vue has no `.window` event modifier, so this is done with a plain listener). --}}
        <script>
            (function () {
                // Query the header on each call — it's rendered by Vue (inside <v-form>),
                // so it may not exist yet when this script first runs.
                const update = () => {
                    const header = document.querySelector('.js-sticky-header');

                    if (! header) {
                        return;
                    }

                    const scrolled = window.scrollY > 0;

                    header.classList.toggle('bg-white', scrolled);
                    header.classList.toggle('shadow-md', scrolled);
                };

                // The SPA re-runs pushed scripts on every ajax visit, so drop any
                // previous listener before adding a new one and clean up on navigate —
                // otherwise scroll handlers accumulate across visits.
                if (window.__stickyProductHeader) {
                    window.removeEventListener('scroll', window.__stickyProductHeader);
                }

                window.__stickyProductHeader = update;

                window.addEventListener('scroll', update, { passive: true });

                document.addEventListener('unopim:navigate:before', function cleanup() {
                    window.removeEventListener('scroll', window.__stickyProductHeader);

                    window.__stickyProductHeader = null;

                    document.removeEventListener('unopim:navigate:before', cleanup);
                });

                setTimeout(update, 300);
            })();
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
