<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.costs.edit.title')
    </x-slot>

    <v-edit-product-cost :cost="{{ json_encode($cost) }}"></v-edit-product-cost>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-edit-product-cost-template">
            {!! view_render_event('unopim.admin.pricing.costs.edit.before') !!}

            <x-admin::form
                :action="route('admin.pricing.costs.update', $cost->id)"
                method="PUT"
            >
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('pricing::app.costs.edit.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <a
                            href="{{ route('admin.pricing.costs.index') }}"
                            class="transparent-button"
                        >
                            @lang('pricing::app.costs.edit.back-btn')
                        </a>

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('pricing::app.costs.edit.save-btn')
                        </button>
                    </div>
                </div>

                <div class="flex gap-2.5 mt-3.5">
                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('pricing::app.costs.edit.general')
                            </p>

                            <!-- Product (readonly) -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.edit.product')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    :value="$cost->product->sku ?? 'N/A'"
                                    readonly
                                    class="cursor-not-allowed"
                                />
                            </x-admin::form.control-group>

                            <!-- Cost Type (readonly) -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.edit.cost-type')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    :value="trans('pricing::app.costs.edit.types.' . $cost->cost_type)"
                                    readonly
                                    class="cursor-not-allowed"
                                />
                            </x-admin::form.control-group>

                            <!-- Amount -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.edit.amount')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="amount"
                                    rules="required|decimal:4"
                                    :value="old('amount', $cost->amount)"
                                    :label="trans('pricing::app.costs.edit.amount')"
                                />

                                <x-admin::form.control-group.error control-name="amount" />
                            </x-admin::form.control-group>

                            <!-- Currency -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.edit.currency')
                                </x-admin::form.control-group.label>

                                @php
                                    $currencies = [
                                        ['id' => 'USD', 'label' => 'USD'],
                                        ['id' => 'EUR', 'label' => 'EUR'],
                                        ['id' => 'GBP', 'label' => 'GBP'],
                                        ['id' => 'SAR', 'label' => 'SAR'],
                                        ['id' => 'AED', 'label' => 'AED'],
                                    ];
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="currency_code"
                                    rules="required"
                                    :value="old('currency_code', $cost->currency_code)"
                                    :label="trans('pricing::app.costs.edit.currency')"
                                    :options="json_encode($currencies)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="currency_code" />
                            </x-admin::form.control-group>

                            <!-- Effective From (readonly) -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.edit.effective-from')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="date"
                                    :value="$cost->effective_from->format('Y-m-d')"
                                    readonly
                                    class="cursor-not-allowed"
                                />
                            </x-admin::form.control-group>

                            <!-- Effective To -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('pricing::app.costs.edit.effective-to')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="date"
                                    name="effective_to"
                                    :value="old('effective_to', $cost->effective_to?->format('Y-m-d'))"
                                    :label="trans('pricing::app.costs.edit.effective-to')"
                                />

                                <x-admin::form.control-group.error control-name="effective_to" />
                            </x-admin::form.control-group>

                            <!-- Notes -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('pricing::app.costs.edit.notes')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="textarea"
                                    name="notes"
                                    :value="old('notes', $cost->notes)"
                                    :label="trans('pricing::app.costs.edit.notes')"
                                />

                                <x-admin::form.control-group.error control-name="notes" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>
            </x-admin::form>

            {!! view_render_event('unopim.admin.pricing.costs.edit.after') !!}
        </script>

        <script type="module">
            app.component('v-edit-product-cost', {
                template: '#v-edit-product-cost-template',
                props: ['cost']
            });
        </script>
    @endPushOnce
</x-admin::layouts>
