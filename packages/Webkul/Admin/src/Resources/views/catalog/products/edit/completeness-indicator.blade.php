@php
    $completenessLocales = $currentChannel->locales->sortBy('name')->map(fn ($locale): array => [
        'id'   => $locale->id,
        'name' => $locale->name,
    ])->values();

    $completenessState = [
        'score'         => $score['score'] ?? null,
        'missingCount'  => $score['missing_count'] ?? 0,
        'average'       => $averageScore,
        'localeScores'  => collect($scores)->map(fn ($row) => $row['score'])->all(),
    ];
@endphp

<v-product-completeness
    url="{{ route('admin.catalog.products.completeness', $product->id) }}"
    :locales='@json($completenessLocales)'
    :initial='@json($completenessState)'
></v-product-completeness>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-completeness-template">
        <div class="flex gap-x-1 items-center" v-if="state.score !== null">
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
                            @{{ state.score }}%
                        </span>
                    </button>
                </x-slot>

                <x-slot:content class="!p-0">
                    <div class="p-2">
                        <div class="p-2 font-semibold border-b dark:border-cherry-800">
                            <v-radial-progress
                                :score="state.average"
                                label="{{ trans('completeness::app.catalog.products.edit.completeness.title') }}"
                                sub-title="{{ trans('completeness::app.catalog.products.edit.completeness.subtitle') }}"
                                :radius="16"
                            />
                        </div>

                        <div
                            v-for="locale in locales"
                            :key="locale.id"
                            class="p-2 text-sm dark:border-cherry-800"
                        >
                            <v-radial-progress
                                v-if="state.localeScores[locale.id] !== undefined"
                                :score="state.localeScores[locale.id]"
                                :label="locale.name"
                                :radius="14"
                            />

                            <div v-else class="text-sm text-gray-400 italic">@{{ locale.name }}: N/A</div>
                        </div>
                    </div>
                </x-slot>
            </x-admin::dropdown>

            <div v-if="state.missingCount" class="text-gray-600 dark:text-white ltr:ml-2.5">
                <span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span>
                @{{ state.missingCount }} @lang('completeness::app.catalog.products.edit.required-attributes')
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-product-completeness', {
            template: '#v-product-completeness-template',

            props: {
                url: {
                    type: String,
                    required: true,
                },

                locales: {
                    type: Array,
                    default: () => [],
                },

                initial: {
                    type: Object,
                    required: true,
                },
            },

            data() {
                return {
                    state: { ...this.initial },
                };
            },

            mounted() {
                this.refresh = () => this.fetch();

                this.$emitter.on('form-saved', this.refresh);
            },

            beforeUnmount() {
                this.$emitter.off('form-saved', this.refresh);
            },

            methods: {
                fetch() {
                    this.$axios.get(this.url)
                        .then(({ data }) => {
                            if (data && data.state) {
                                this.state = data.state;
                            }
                        })
                        .catch(() => {});
                },
            },
        });
    </script>
@endPushOnce
