<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.costs.create.title')
    </x-slot>

    <v-create-product-cost></v-create-product-cost>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-create-product-cost-template">
            {!! view_render_event('unopim.admin.pricing.costs.create.before') !!}

            <x-admin::form
                :action="route('admin.pricing.costs.store')"
                enctype="multipart/form-data"
            >
                {!! view_render_event('unopim.admin.pricing.costs.create.form_controls.before') !!}

                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('pricing::app.costs.create.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <a
                            href="{{ route('admin.pricing.costs.index') }}"
                            class="transparent-button"
                        >
                            @lang('pricing::app.costs.create.back-btn')
                        </a>

                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('pricing::app.costs.create.save-btn')
                        </button>
                    </div>
                </div>

                <div class="flex gap-2.5 mt-3.5">
                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('pricing::app.costs.create.general')
                            </p>

                            <!-- Product Search -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.create.product')
                                </x-admin::form.control-group.label>

                                <x-admin::products.search
                                    name="product_id"
                                    rules="required"
                                    :label="trans('pricing::app.costs.create.product')"
                                />

                                <x-admin::form.control-group.error control-name="product_id" />
                            </x-admin::form.control-group>

                            <!-- Cost Type -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.create.cost-type')
                                </x-admin::form.control-group.label>

                                @php
                                    $costTypes = [
                                        ['id' => 'cogs', 'label' => trans('pricing::app.costs.create.types.cogs')],
                                        ['id' => 'operational', 'label' => trans('pricing::app.costs.create.types.operational')],
                                        ['id' => 'shipping', 'label' => trans('pricing::app.costs.create.types.shipping')],
                                        ['id' => 'overhead', 'label' => trans('pricing::app.costs.create.types.overhead')],
                                        ['id' => 'marketing', 'label' => trans('pricing::app.costs.create.types.marketing')],
                                    ];
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="cost_type"
                                    rules="required"
                                    :value="old('cost_type')"
                                    :label="trans('pricing::app.costs.create.cost-type')"
                                    :options="json_encode($costTypes)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="cost_type" />
                            </x-admin::form.control-group>

                            <!-- Amount -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.create.amount')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="amount"
                                    rules="required|decimal:4"
                                    :value="old('amount')"
                                    :label="trans('pricing::app.costs.create.amount')"
                                    :placeholder="trans('pricing::app.costs.create.amount-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="amount" />
                            </x-admin::form.control-group>

                            <!-- Currency -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.create.currency')
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
                                    :value="old('currency_code', 'USD')"
                                    :label="trans('pricing::app.costs.create.currency')"
                                    :options="json_encode($currencies)"
                                    track-by="id"
                                    label-by="label"
                                />

                                <x-admin::form.control-group.error control-name="currency_code" />
                            </x-admin::form.control-group>

                            <!-- Effective From -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('pricing::app.costs.create.effective-from')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="date"
                                    name="effective_from"
                                    rules="required"
                                    :value="old('effective_from', now()->format('Y-m-d'))"
                                    :label="trans('pricing::app.costs.create.effective-from')"
                                />

                                <x-admin::form.control-group.error control-name="effective_from" />
                            </x-admin::form.control-group>

                            <!-- Effective To -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('pricing::app.costs.create.effective-to')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="date"
                                    name="effective_to"
                                    :value="old('effective_to')"
                                    :label="trans('pricing::app.costs.create.effective-to')"
                                />

                                <x-admin::form.control-group.error control-name="effective_to" />
                            </x-admin::form.control-group>

                            <!-- Notes -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('pricing::app.costs.create.notes')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="textarea"
                                    name="notes"
                                    :value="old('notes')"
                                    :label="trans('pricing::app.costs.create.notes')"
                                    :placeholder="trans('pricing::app.costs.create.notes-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="notes" />
                            </x-admin::form.control-group>
                        </div>
                    </div>
                </div>

                {!! view_render_event('unopim.admin.pricing.costs.create.form_controls.after') !!}
            </x-admin::form>

            {!! view_render_event('unopim.admin.pricing.costs.create.after') !!}
        </script>

        <script type="module">
            app.component('v-create-product-cost', {
                template: '#v-create-product-cost-template'
            });
        </script>
    @endPushOnce
</x-admin::layouts>
