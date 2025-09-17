<x-slot:title>
    @lang('completeness::app.catalog.families.edit.completeness.title')
</x-slot>

<div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
        @lang('completeness::app.catalog.families.edit.completeness.title')
    </p>
</div>

<v-completeness-required-modal></v-completeness-required-modal>

@pushOnce('scripts')
    <script type="text/x-template" id="v-completeness-required-modal-template">
        <div>
            <x-admin::datagrid 
                ref="completenessAttributeDatagrid"
                :src="route('admin.catalog.families.completeness.edit', $familyId)"
            >
                <!-- Header -->
                <template #header="{ columns, records, sortPage, selectAllRecords, applied, isLoading, actions }">
                    <template v-if="!isLoading">
                        <div
                            class="row grid grid-rows-1 gap-2.5 items-center px-4 py-2.5 border-b bg-violet-50 dark:border-cherry-800 dark:bg-cherry-900 font-semibold"
                            :style="'grid-template-columns: ' + (actions.length ? '2fr ' : '') + 'repeat(' + (actions.length ? columns.length : (columns.length)) + ', 1fr)'"
                        >
                            <div
                                class="flex items-center select-none"
                                v-for="(column, index) in columns"
                                :key="column.index"
                            >
                                <label
                                    v-if="index === 0"
                                    class="flex mr-2 gap-1 items-center w-max cursor-pointer select-none"
                                    for="mass_action_select_all_records"
                                >
                                    <input
                                        type="checkbox"
                                        name="mass_action_select_all_records"
                                        id="mass_action_select_all_records"
                                        class="hidden peer"
                                        :checked="['all', 'partial'].includes(applied.massActions.meta.mode)"
                                        @change="selectAllRecords"
                                    >
                                    <span
                                        class="icon-checkbox-normal cursor-pointer rounded-md text-2xl"
                                        :class="[
                                            applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checkbox-check peer-checked:text-violet-700' : (
                                                applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-violet-700' : ''
                                            ),
                                        ]"
                                    ></span>
                                </label>
                                <p class="text-gray-600 dark:text-gray-300">
                                    <span
                                        :class="{
                                            'text-gray-800 dark:text-white font-medium': applied.sort.column == column.index,
                                            'cursor-pointer hover:text-gray-800 dark:hover:text-white': column.sortable,
                                        }"
                                        @click="column.sortable ? sortPage(column) : {}"
                                    >
                                        @{{ column.label }}
                                    </span>
                                    <i
                                        class="ltr:ml-1.5 rtl:mr-1.5 text-base text-gray-800 dark:text-white align-text-bottom"
                                        :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                        v-if="column.index === applied.sort.column"
                                    ></i>
                                </p>
                            </div>
                            <div v-if="actions.length" class="flex gap-2.5 items-center justify-end select-none">
                                <p class="text-gray-600 dark:text-gray-300">
                                    @lang('admin::app.components.datagrid.table.actions')
                                </p>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <x-admin::shimmer.datagrid.table.head :isMultiRow="false" />
                    </template>
                </template>

                <!-- Body -->
                <template #body="{ columns, records, applied, actions, isLoading }">
                    <template v-if="!isLoading">
                        <div
                            v-for="record in records"
                            :key="record.id"
                            class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                            :style="'grid-template-columns: ' + (actions.length ? '2fr ' : '') + 'repeat(' + (actions.length ? columns.length : (columns.length)) + ', 1fr)'"
                        >
                            <div class="flex items-center gap-2.5 overflow-hidden" >
                                <div class="mass-action-input">
                                    <input
                                        type="checkbox"
                                        :name="`mass_action_select_record_${record.id}`"
                                        :id="`mass_action_select_record_${record.id}`"
                                        :value="record.id"
                                        class="hidden peer"
                                        v-model="applied.massActions.indices"
                                    >
                                    <label
                                        class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-violet-700"
                                        :for="`mass_action_select_record_${record.id}`"
                                    ></label>
                                </div>

                                <p v-text="record.code" class="text-nowrap overflow-hidden text-ellipsis"></p>
                            </div>

                            <div class="overflow-hidden">
                                <p v-text="record.name" class="text-nowrap overflow-hidden text-ellipsis" :title="record.name"></p>
                            </div>

                            <div class="">
                                <x-admin::form.control-group>
                                    <v-multiselect-handler
                                        type="multiselect"
                                        :ref="'channel_requirements_multiselect_' + record.id"
                                        name="channel_requirements"
                                        :options="{{ json_encode($allChannels) }}"
                                        :value="record.channel_required
                                            ? record.channel_required.split(',').map(channel => channel.trim())
                                            : []"
                                        track-by="code"
                                        label="label"
                                        multiple
                                        @input="updated(record.id, $event)"
                                    />
                                </x-admin::form.control-group>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <x-admin::shimmer.datagrid.table.body :isMultiRow="false" />
                    </template>
                </template>
            </x-admin::datagrid>

            <x-admin::form v-slot="{ handleSubmit }" as="div">
                <form @submit="handleSubmit($event, save)" ref="completenessRequireForm">
                    <x-admin::modal ref="completenessModal">
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('completeness::app.catalog.families.edit.completeness.configure')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.control
                                    type="multiselect"
                                    name="channel_requirements"
                                    v-model="selectedChannels"
                                    :options="$allChannels"
                                    track-by="code"
                                ></x-admin::form.control-group.control>
                            </x-admin::form.control-group>
                        </x-slot>

                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="button"
                                    class="transparent-button hover:bg-violet-100 dark:hover:bg-gray-800 dark:text-white"
                                    @click="currentAttributeId = null; $refs.completenessModal.toggle()"
                                >
                                    @lang('completeness::app.catalog.families.edit.completeness.back-btn')
                                </button>

                                <button
                                    type="submit"
                                    class="primary-button"
                                    :disabled="isSaving"
                                >
                                    @lang('completeness::app.catalog.families.edit.completeness.save-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
    app.component('v-completeness-required-modal', {
        template: '#v-completeness-required-modal-template',

        data() {
            return {
                isSaving: false,
                selectedChannels: [],
                currentAttributeId: null,
            }
        },
        mounted() {
            this.$emitter.on('open-completeness-required-modal', () => this.$refs?.completenessModal.open());

            this.$emitter.on('open-attribute-completeness-edit-modal', this.editModal);
        },
        methods: {
            save(params) {
                this.isSaving = true;

                const formData = new FormData(this.$refs.completenessRequireForm);

                for (const key of formData.keys()) {
                    let formValue = formData.getAll(key);
                    params[key] = formValue.pop();
                }

                params.familyId = "{{ $familyId }}";

                if (this.currentAttributeId) {
                    params.attributeId = this.currentAttributeId;

                    this.$axios.post("{{ route('admin.catalog.families.completeness.update') }}", params)
                        .then(response => {
                            this.$refs.completenessModal.toggle();
                            this.currentAttributeId = null;
                        })
                        .catch(console.error)
                        .finally(() => this.isSaving = false);
                } else {
                    params.indices = this.$refs.completenessAttributeDatagrid.applied.massActions.indices;

                    this.$axios.post("{{ route('admin.catalog.families.completeness.mass_update') }}", params)
                        .then(response => {
                            this.$refs.completenessModal.toggle();

                            if (response.data.success) {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            }

                            this.$refs.completenessAttributeDatagrid.get();
                        })
                        .catch(e => {
                            this.$emitter.emit('add-flash', {
                                type: 'error',
                                message: e.message,
                            });
                        })
                        .finally(() => {
                            this.isSaving = false;
                        });
                }
            },

            updated(id, channels) {
                if (typeof InputEvent !== 'undefined' && channels instanceof InputEvent) {
                    return;
                }

                try {
                    channels = JSON.parse(channels)
                } catch (e) {
                    channels = [];
                }

                const channelCodes = Array.isArray(channels)
                    ? channels.map(c => typeof c === 'string' ? c : c.code).join(',')
                    : '';

                const params = {
                    familyId: "{{ $familyId }}",
                    attributeId: id,
                    channel_requirements: channelCodes,
                };

                this.isSaving = true;

                this.$axios.post("{{ route('admin.catalog.families.completeness.update') }}", params)
                    .then(response => {
                        this.$emitter.emit('add-flash', 
                        {
                            type: 'success',
                            message: response.data.message,
                        });
                    })
                    .catch(console.error)
                    .finally(() => {
                        this.isSaving = false;
                    });
            },


        parseJson(value, silent = false) {
                try {
                    return JSON.parse(value);
                } catch (e) {
                    if (! silent) {
                        console.error(e);
                    }

                    return value;
                }
            },

            editModal(action, attributeId) {
                const url = action.url;
                this.currentAttributeId = attributeId;

                this.$axios.get(url)
                    .then(response => {
                        const data = response.data;

                        this.selectedChannels = data.channels ? data.channels.split(',') : [];

                        this.$refs.completenessModal.open();
                    })
                    .catch(console.error);
            }
        }
    });
    </script>
@endPushOnce
