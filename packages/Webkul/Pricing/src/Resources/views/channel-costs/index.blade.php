<x-admin::layouts>
    <x-slot:title>
        @lang('pricing::app.channel-costs.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('pricing::app.channel-costs.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            @if (bouncer()->hasPermission('pricing.channel-costs.create'))
                <button
                    type="button"
                    class="primary-button"
                    @click="$refs.channelCostModal.toggle()"
                >
                    @lang('pricing::app.channel-costs.index.create-btn')
                </button>
            @endif
        </div>
    </div>

    {!! view_render_event('unopim.admin.pricing.channel_costs.list.before') !!}

    <v-channel-costs-index></v-channel-costs-index>

    {!! view_render_event('unopim.admin.pricing.channel_costs.list.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-channel-costs-index-template">
            <div>
                <x-admin::datagrid :src="route('admin.pricing.channel-costs.index')" />

                <!-- Create/Edit Modal -->
                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                    ref="modalForm"
                >
                    <form @submit.prevent="handleSubmit($event, save)">
                        <x-admin::modal ref="channelCostModal">
                            <x-slot:header>
                                <p class="text-lg text-gray-800 dark:text-white font-bold">
                                    @{{ formTitle }}
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <!-- Channel Select -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('pricing::app.channel-costs.create.channel')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $channels = app('Webkul\Core\Repositories\ChannelRepository')->all();
                                        $channelOptions = $channels->map(fn($ch) => ['id' => $ch->id, 'label' => $ch->code])->toArray();
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="channel_id"
                                        rules="required"
                                        :label="trans('pricing::app.channel-costs.create.channel')"
                                        :options="json_encode($channelOptions)"
                                        track-by="id"
                                        label-by="label"
                                        ::disabled="editMode"
                                    />

                                    <x-admin::form.control-group.error control-name="channel_id" />
                                </x-admin::form.control-group>

                                <!-- Commission Percentage -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('pricing::app.channel-costs.create.commission-percentage')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="commission_percentage"
                                        rules="required|decimal:2"
                                        :label="trans('pricing::app.channel-costs.create.commission-percentage')"
                                        :placeholder="trans('pricing::app.channel-costs.create.commission-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="commission_percentage" />
                                </x-admin::form.control-group>

                                <!-- Transaction Fee Percentage -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('pricing::app.channel-costs.create.transaction-fee')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="transaction_fee_percentage"
                                        rules="decimal:2"
                                        :label="trans('pricing::app.channel-costs.create.transaction-fee')"
                                        :placeholder="trans('pricing::app.channel-costs.create.commission-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="transaction_fee_percentage" />
                                </x-admin::form.control-group>

                                <!-- Listing Fee -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('pricing::app.channel-costs.create.listing-fee')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="listing_fee"
                                        rules="decimal:4"
                                        :label="trans('pricing::app.channel-costs.create.listing-fee')"
                                        :placeholder="trans('pricing::app.channel-costs.create.fee-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="listing_fee" />
                                </x-admin::form.control-group>

                                <!-- Monthly Subscription Fee -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('pricing::app.channel-costs.create.monthly-subscription')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="monthly_subscription_fee"
                                        rules="decimal:4"
                                        :label="trans('pricing::app.channel-costs.create.monthly-subscription')"
                                        :placeholder="trans('pricing::app.channel-costs.create.fee-placeholder')"
                                    />

                                    <x-admin::form.control-group.error control-name="monthly_subscription_fee" />
                                </x-admin::form.control-group>

                                <!-- Effective From -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('pricing::app.channel-costs.create.effective-from')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="effective_from"
                                        rules="required"
                                        :label="trans('pricing::app.channel-costs.create.effective-from')"
                                        ::disabled="editMode"
                                    />

                                    <x-admin::form.control-group.error control-name="effective_from" />
                                </x-admin::form.control-group>

                                <!-- Effective To -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('pricing::app.channel-costs.create.effective-to')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="date"
                                        name="effective_to"
                                        :label="trans('pricing::app.channel-costs.create.effective-to')"
                                    />

                                    <x-admin::form.control-group.error control-name="effective_to" />
                                </x-admin::form.control-group>
                            </x-slot>

                            <x-slot:footer>
                                <button type="submit" class="primary-button">
                                    @lang('pricing::app.channel-costs.create.save-btn')
                                </button>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-channel-costs-index', {
                template: '#v-channel-costs-index-template',

                data() {
                    return {
                        editMode: false,
                        editId: null
                    };
                },

                computed: {
                    formTitle() {
                        return this.editMode
                            ? '@lang('pricing::app.channel-costs.edit.title')'
                            : '@lang('pricing::app.channel-costs.create.title')';
                    }
                },

                methods: {
                    save(params) {
                        const url = this.editMode
                            ? `{{ route('admin.pricing.channel-costs.index') }}/${this.editId}`
                            : '{{ route('admin.pricing.channel-costs.store') }}';

                        const method = this.editMode ? 'put' : 'post';

                        this.$axios[method](url, params)
                            .then(response => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                                this.$refs.channelCostModal.toggle();
                                window.location.reload();
                            })
                            .catch(error => {
                                if (error.response?.status === 422) {
                                    this.$refs.modalForm.setErrors(error.response.data.errors);
                                }
                            });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
