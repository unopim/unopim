@props(['groupPage', 'activeGroup', 'productId'])

<div class="attribute-groups-nav flex flex-col gap-2 w-[280px] shrink-0 max-xl:w-full">
    <v-product-attribute-groups
        :initial="{{ json_encode($groupPage) }}"
        :active-code="{{ json_encode($activeGroup['code'] ?? null) }}"
        endpoint="{{ route('admin.catalog.products.attribute_groups', $productId) }}"
        base-url="{{ route('admin.catalog.products.edit', $productId) }}"
    ></v-product-attribute-groups>
</div>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-attribute-groups-template">
        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow flex flex-col gap-3">
            <p class="text-base text-gray-800 dark:text-white font-semibold">
                @lang('admin::app.catalog.products.edit.attribute-groups.title')
            </p>

            <x-admin::form.control-group class="!mb-0">
                <x-admin::form.control-group.control
                    type="text"
                    name="attribute_group_search"
                    ::value="search"
                    :placeholder="trans('admin::app.catalog.products.edit.attribute-groups.search-placeholder')"
                    @input="onSearch($event)"
                />
            </x-admin::form.control-group>

            <ul class="flex flex-col gap-1 max-h-[60vh] overflow-y-auto">
                <li v-for="group in groups" :key="group.id">
                    <button
                        type="button"
                        class="w-full text-left px-3 py-2 rounded flex items-center justify-between gap-2 hover:bg-gray-100 dark:hover:bg-cherry-800"
                        :class="group.code === currentCode ? 'bg-gray-100 dark:bg-cherry-800 font-semibold' : ''"
                        @click="select(group)"
                    >
                        <span class="truncate">@{{ group.name }}</span>

                        <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">@{{ group.attributesCount }}</span>
                    </button>
                </li>
            </ul>

            <p
                v-if="! groups.length && ! loading"
                class="text-sm text-gray-500 dark:text-gray-400"
            >
                @lang('admin::app.catalog.products.edit.attribute-groups.empty')
            </p>

            <button
                v-if="page < lastPage"
                type="button"
                class="secondary-button"
                :disabled="loading"
                @click="loadMore"
            >
                <span v-if="loading">@lang('admin::app.catalog.products.edit.attribute-groups.loading')</span>

                <span v-else>@lang('admin::app.catalog.products.edit.attribute-groups.load-more')</span>
            </button>
        </div>
    </script>

    <script type="module">
        app.component('v-product-attribute-groups', {
            template: '#v-product-attribute-groups-template',

            props: {
                initial: {
                    type: Object,
                    required: true,
                },

                activeCode: {
                    type: String,
                    default: null,
                },

                endpoint: {
                    type: String,
                    required: true,
                },

                baseUrl: {
                    type: String,
                    required: true,
                },
            },

            data() {
                return {
                    groups: this.initial.groups || [],
                    page: 1,
                    lastPage: this.initial.lastPage || 1,
                    search: '',
                    loading: false,
                    currentCode: this.activeCode,
                    debounceTimer: null,
                };
            },

            methods: {
                onSearch(event) {
                    this.search = event.target.value;

                    clearTimeout(this.debounceTimer);

                    this.debounceTimer = setTimeout(() => this.fetch(1, true), 300);
                },

                loadMore() {
                    this.fetch(this.page + 1, false);
                },

                fetch(page, replace) {
                    this.loading = true;

                    this.$axios
                        .get(this.endpoint, { params: { page, query: this.search } })
                        .then(({ data }) => {
                            this.groups = replace ? data.groups : this.groups.concat(data.groups);
                            this.page = page;
                            this.lastPage = data.lastPage;
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                select(group) {
                    if (group.code === this.currentCode) {
                        return;
                    }

                    this.go(group);
                },

                go(group) {
                    this.currentCode = group.code;

                    const url = this.urlFor(group.code);

                    if (window.unopim?.visit) {
                        window.unopim.visit(url);
                    } else {
                        window.location.href = url;
                    }
                },

                urlFor(code) {
                    const url = new URL(this.baseUrl, window.location.origin);

                    new URLSearchParams(window.location.search).forEach((value, key) => {
                        if (key !== 'group') {
                            url.searchParams.set(key, value);
                        }
                    });

                    url.searchParams.set('group', code);

                    return url.toString();
                },
            },
        });
    </script>
@endPushOnce
