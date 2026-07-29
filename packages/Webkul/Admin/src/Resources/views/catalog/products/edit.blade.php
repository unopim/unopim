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
        id="product-edit-form"
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

                @include('admin::catalog.products.edit.completeness-indicator')
            </div>

            @include('admin::catalog.products.edit.more-actions.index')
        </div>

        {!! view_render_event('unopim.admin.catalog.product.edit.actions.after', ['product' => $product]) !!}

        {!! view_render_event('unopim.admin.catalog.product.edit.form.before', ['product' => $product]) !!}

        @php
            $variantAxisCodes = ($variantTree ?? null)
                ? collect($variantTree['attributes'])->where('isAxis', true)->pluck('code')->all()
                : [];

            $variantHiddenCodes = array_merge($variantAxisCodes, ($variantFieldLocks['hidden'] ?? []));
        @endphp
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="left-column flex flex-col gap-2 flex-1 max-xl:flex-auto">
                @php
                    $renderedAttributeCodes = [];
                @endphp

                @foreach ($renderGroups as $group)
                    @php
                        $groupAttributeSet = ($groupAttributes[$group->id] ?? null)
                            ?: $product->getEditableAttributes($group);

                        if (! $groupAttributeSet instanceof \Illuminate\Support\Collection) {
                            $groupAttributeSet = $groupAttributeSet->get();
                        }

                        /**
                         * The same attribute may be assigned to several of the
                         * family's groups; every copy renders the same input name,
                         * so only the first occurrence may be editable.
                         */
                        $groupAttributeSet = $groupAttributeSet
                            ->reject(fn ($attribute) => in_array($attribute->code, $renderedAttributeCodes, true))
                            ->values();

                        $renderedAttributeCodes = array_merge(
                            $renderedAttributeCodes,
                            $groupAttributeSet->pluck('code')->all()
                        );
                    @endphp

                    @include('admin::catalog.products.edit.attribute-group-panel', [
                        'group'              => $group,
                        'customAttributes'   => $groupAttributeSet,
                        'variantHiddenCodes' => $variantHiddenCodes,
                    ])
                @endforeach

                @if ($nextGroupId ?? null)
                    @include('admin::catalog.products.edit.attribute-group-loader', [
                        'productId'   => $product->id,
                        'nextGroupId' => $nextGroupId,
                    ])
                @endif
            </div>
            @include('admin::catalog.products.edit.section-store')
            <x-admin::layouts.side-rail
                :navigation-title="trans('admin::app.catalog.products.edit.navigation')"
                :info-title="trans('admin::app.catalog.products.edit.product-info.title')"
            >
                <x-slot:navigation>
                    @if ($variantTree ?? null)
                        <v-variant-axis-nav></v-variant-axis-nav>
                    @endif

                    @include('admin::catalog.products.edit.categories', ['currentLocaleCode' => $currentLocale?->code, 'productCategories' => $product->resolvedValues()['categories'] ?? []])

                    @if ($variantTree ?? null)
                        {!! view_render_event('unopim.admin.catalog.product.edit.form.types.' . $product->type . '.before', ['product' => $product]) !!}

                        {!! view_render_event('unopim.admin.catalog.product.edit.form.types.' . $product->type . '.after', ['product' => $product]) !!}
                    @else
                        @includeIf('admin::catalog.products.edit.types.' . $product->type)
                    @endif

                    @include('admin::catalog.products.edit.links', [
                        'associationTypes'      => $associationTypes,
                        'upSellAssociations'    => $product->values['associations']['up_sells'] ?? [],
                        'crossSellAssociations' => $product->values['associations']['cross_sells'] ?? [],
                        'relatedAssociations'   => $product->values['associations']['related_products'] ?? [],
                    ])

                    @foreach ($product->getTypeInstance()->getAdditionalViews() as $view)
                        @includeIf($view)
                    @endforeach
                </x-slot>

                <x-slot:info>
                    @include('admin::catalog.products.edit.product-info')
                </x-slot>
            </x-admin::layouts.side-rail>
        </div>

        @if ($variantTree ?? null)
            @include('admin::catalog.products.edit.variant-inheritance-editor', ['variantTree' => $variantTree])
        @endif

        {!! view_render_event('unopim.admin.catalog.product.edit.form.after', ['product' => $product]) !!}
    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.product.edit.after', ['product' => $product]) !!}

    @pushOnce('scripts')
        <script>
            (function () {
                const update = () => {
                    const header = document.querySelector('.js-sticky-header');

                    if (! header) {
                        return;
                    }

                    const scrolled = window.scrollY > 0;

                    header.classList.toggle('bg-white', scrolled);
                    header.classList.toggle('shadow-md', scrolled);
                };

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
