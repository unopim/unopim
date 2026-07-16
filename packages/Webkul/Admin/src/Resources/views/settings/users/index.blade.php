<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.users.index.title')
    </x-slot>

    <v-users>
        <x-admin::page-header :title="trans('admin::app.settings.users.index.title')">
            <x-slot:actions>
                @if (bouncer()->hasPermission('settings.users.users.create'))
                    <button
                        type="button"
                        class="primary-button"
                    >
                        @lang('admin::app.settings.users.index.create.title')
                    </button>
                @endif
            </x-slot>
        </x-admin::page-header>

        <x-admin::shimmer.datagrid />
    </v-users>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-users-template"
        >
            <x-admin::page-header :title="trans('admin::app.settings.users.index.title')">
                <x-slot:actions>
                    @if (bouncer()->hasPermission('settings.users.users.create'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="resetForm();$refs.userUpdateOrCreateModal.open()"
                        >
                            @lang('admin::app.settings.users.index.create.title')
                        </button>
                    @endif
                </x-slot>
            </x-admin::page-header>

            <x-admin::datagrid
                src="{{ route('admin.settings.users.index') }}"
                ref="datagrid"
            >
                @php
                    $hasPermission = bouncer()->hasPermission('settings.users.users.edit') || bouncer()->hasPermission('settings.users.users.delete');
                @endphp
                <template #header="{columns, records, sortPage, applied}">
                    <div class="row grid {{ $hasPermission ? 'grid-cols-6' : 'grid-cols-5' }} grid-rows-1 gap-2.5 items-center px-4 py-2.5 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-cherry-800 font-semibold">
                        <div
                            class="flex gap-2.5 cursor-pointer"
                            v-for="(columnGroup, index) in ['user_id', 'user_name', 'status', 'email', 'role_name']"
                            :key="index"
                        >
                            <p class="text-gray-600 dark:text-gray-300">
                                <span class="[&>*]:after:content-['_/_']">
                                    <span
                                        class="after:content-['/'] last:after:content-['']"
                                        :class="{
                                            'text-gray-800 dark:text-white font-medium': applied.sort.column == columnGroup,
                                            'cursor-pointer hover:text-gray-800 dark:hover:text-white': columns.find(columnTemp => columnTemp.index === columnGroup)?.sortable,
                                        }"
                                        @click="
                                            columns.find(columnTemp => columnTemp.index === columnGroup)?.sortable ? sortPage(columns.find(columnTemp => columnTemp.index === columnGroup)): {}
                                        "
                                    >
                                        @{{ columns.find(columnTemp => columnTemp.index === columnGroup)?.label }}
                                    </span>
                                </span>

                                <i
                                    class="ltr:ml-1.5 rtl:mr-1.5 text-base  text-gray-800 dark:text-white align-text-bottom"
                                    :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                    v-if="columnGroup.includes(applied.sort.column)"
                                ></i>
                            </p>
                        </div>

                        @if ($hasPermission)
                            <p class="flex gap-2.5 justify-end">
                                @lang('admin::app.components.datagrid.table.actions')
                            </p>
                        @endif
                    </div>
                </template>

                <template #body="{ columns, records, performAction }">
                    <div
                        v-for="record in records"
                        :key="record.id"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 cursor-pointer transition-all hover:bg-primary-50 hover:bg-opacity-30 dark:hover:bg-cherry-800"
                        :style="'grid-template-columns: repeat(' + (record.actions.length ? 6 : 5) + ', minmax(0, 1fr));'"
                        @click="id=1; editModal(record.actions.find(action => action.index === 'edit')?.url)"
                    >
                        <p v-text="record.user_id"></p>

                        <p>
                            <div class="flex gap-2.5 items-center">
                                <div
                                    class="inline-block w-9 h-9 rounded-full border-3 border-gray-800 align-middle text-center mr-2 overflow-hidden"
                                    v-if="record.user_img"
                                >
                                    <img
                                        class="h-9 object-cover"
                                        :src="record.user_img"
                                        :alt="record.user_name"
                                        v-on:error="record.user_img = null"
                                    />
                                </div>

                                <div
                                    class="profile-info-icon"
                                    v-else
                                >
                                    <button
                                        class="flex justify-center items-center w-9 h-9 bg-primary-400 rounded-full text-sm text-white font-semibold cursor-pointer leading-6 transition-all hover:bg-primary-700 focus:bg-primary-700"
                                        v-text="record.user_name[0]?.toUpperCase()"
                                    >
                                    </button>
                                </div>

                                <div
                                    class="text-sm truncate"
                                    v-text="record.user_name"
                                    :title="record.user_name"
                                >
                                </div>
                            </div>
                        </p>

                        <p v-html="record.status"></p>

                        <p class="truncate" v-text="record.email" :title="record.email"></p>

                        <p v-text="record.role_name" class="truncate" :title="record.role_name"></p>

                        <div class="flex justify-end" @click.stop>
                            <a @click="id=1; editModal(record.actions.find(action => action.index === 'edit')?.url)">
                                <span
                                    :class="record.actions.find(action => action.index === 'edit')?.icon"
                                    title="@lang('admin::app.settings.users.index.datagrid.edit')"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                >
                                </span>
                            </a>

                            <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                <span
                                    :class="record.actions.find(action => action.index === 'delete')?.icon"
                                    title="@lang('admin::app.settings.users.index.datagrid.delete')"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-primary-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                >
                                </span>
                            </a>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, updateOrCreate)"
                    ref="userCreateForm"
                >
                    <x-admin::modal ref="userUpdateOrCreateModal">
                        <x-slot:header>
                            <p
                                class="text-lg text-gray-800 dark:text-white font-bold"
                                v-if="isUpdating"
                            >
                                @lang('admin::app.settings.users.index.edit.title')
                            </p>

                            <p
                                class="text-lg text-gray-800 dark:text-white font-bold"
                                v-else
                            >
                                @lang('admin::app.settings.users.index.create.title')
                            </p>

                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.users.index.create.name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="id"
                                    v-model="data.user.id"
                                />

                                <x-admin::form.control-group.control
                                    type="text"
                                    id="name"
                                    name="name"
                                    rules="required"
                                    v-model="data.user.name"
                                    :label="trans('admin::app.settings.users.index.create.name')"
                                    :placeholder="trans('admin::app.settings.users.index.create.name')"
                                />

                                <x-admin::form.control-group.error control-name="name" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.users.index.create.email')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="email"
                                    id="email"
                                    name="email"
                                    rules="required|email"
                                    v-model="data.user.email"
                                    :label="trans('admin::app.settings.users.index.create.email')"
                                    placeholder="email@example.com"
                                />

                                <x-admin::form.control-group.error control-name="email" />
                            </x-admin::form.control-group>

                            <div class="flex gap-4">
                                <x-admin::form.control-group class="flex-1 mb-2.5">
                                    <x-admin::form.control-group.label ::class="isUpdating ? '' : 'required'">
                                        @lang('admin::app.settings.users.index.create.password')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="password"
                                        id="password"
                                        name="password"
                                        ::rules="isUpdating ? 'min:{{ config('admin.auth.password_min') }}' : 'required|min:{{ config('admin.auth.password_min') }}'"
                                        v-model="data.user.password"
                                        :label="trans('admin::app.settings.users.index.create.password')"
                                        :placeholder="trans('admin::app.settings.users.index.create.password')"
                                        ref="password"
                                    />

                                    <x-admin::form.control-group.error control-name="password" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="flex-1">
                                    <x-admin::form.control-group.label ::class="isUpdating ? '' : 'required'">
                                        @lang('admin::app.settings.users.index.create.confirm-password')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="password"
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        ::rules="isUpdating ? 'confirmed:@password' : 'required|confirmed:@password'"
                                        v-model="data.user.password_confirmation"
                                        :label="trans('admin::app.settings.users.index.create.password')"
                                        :placeholder="trans('admin::app.settings.users.index.create.confirm-password')"
                                    />

                                    <x-admin::form.control-group.error control-name="password_confirmation" />
                                </x-admin::form.control-group>
                            </div>

                            <template v-if="isUpdating">
                            <x-admin::form.control-group class="mb-4">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.channels.edit.ui-locale')
                                </x-admin::form.control-group.label>

                                @php
                                    $locales = core()->getTranslatableLocales();
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="ui_locale_id"
                                    name="ui_locale_id"
                                    rules="required"
                                    v-model="data.user.ui_locale_id"
                                    :label="trans('admin::app.settings.channels.edit.ui-locale')"
                                    :placeholder="trans('admin::app.settings.channels.edit.ui-locale')"
                                    :options="$locales"
                                    track-by="id"
                                    label-by="name"
                                >

                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="ui_locale_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="mb-4">
                                <x-admin::form.control-group.label
                                    :title="trans('admin::app.settings.users.index.create.catalog-locale-info')"
                                >
                                    @lang('admin::app.settings.users.index.create.catalog-locale')

                                    <span class="icon-information text-base align-middle cursor-help"></span>
                                </x-admin::form.control-group.label>

                                @php
                                    $catalogLocales = core()->getAllActiveLocales();
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="catalog_locale_id"
                                    name="catalog_locale_id"
                                    v-model="data.user.catalog_locale_id"
                                    :label="trans('admin::app.settings.users.index.create.catalog-locale')"
                                    :placeholder="trans('admin::app.settings.users.index.create.catalog-locale')"
                                    :options="$catalogLocales"
                                    track-by="id"
                                    label-by="name"
                                >
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="catalog_locale_id" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="mb-4">
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.users.index.create.default-channel')
                                </x-admin::form.control-group.label>

                                @php
                                    $userChannels = core()->getAllChannels()->map(fn ($channel) => [
                                        'id'   => $channel->id,
                                        'name' => $channel->name ?: '['.$channel->code.']',
                                    ])->values()->toJson();
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    id="default_channel_id"
                                    name="default_channel_id"
                                    v-model="data.user.default_channel_id"
                                    :label="trans('admin::app.settings.users.index.create.default-channel')"
                                    :placeholder="trans('admin::app.settings.users.index.create.default-channel')"
                                    :options="$userChannels"
                                    track-by="id"
                                    label-by="name"
                                >
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="default_channel_id" />
                            </x-admin::form.control-group>

                                <!-- TImezone -->
                            <x-admin::form.control-group class="mb-4">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.users.index.create.user-timezone')
                                </x-admin::form.control-group.label>

                                @php
                                    $timezones = json_encode(core()->getTimeZones());
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="timezone"
                                    rules="required"
                                    v-model="data.user.timezone"
                                    :label="trans('admin::app.settings.users.index.create.user-timezone')"
                                    :placeholder="trans('admin::app.settings.users.index.create.user-timezone')"
                                    :options="$timezones"
                                    track-by="id"
                                    label-by="label"
                                >
                                   
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="timezone" />
                            </x-admin::form.control-group>
                            </template>

                            <x-admin::form.control-group class="flex-1 w-full">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.users.index.create.role')
                                </x-admin::form.control-group.label>

                                @php
                                    $roles = json_encode($roles);    
                                @endphp

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="role_id"
                                    :multiple="false"                                    
                                    rules="required"
                                    v-model="data.user.role_id"
                                    :label="trans('admin::app.settings.users.index.create.role')"
                                    :placeholder="trans('admin::app.settings.users.index.create.role')"
                                    :options="$roles"
                                    track-by="id"
                                    label-by="name"
                                >
                                   
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="role_id" />
                            </x-admin::form.control-group>

                            <div class="flex gap-4 mb-4"> 
                                <template v-if="currentUserId != data.user.id">
                                    <x-admin::form.control-group class="w-full flex-1 !mb-0">
                                        <x-admin::form.control-group.label>
                                            @lang('admin::app.settings.users.index.create.status')
                                        </x-admin::form.control-group.label>

                                        <div class="gap-2.5 w-full mt-2.5">
                                            <x-admin::form.control-group.control
                                                type="switch"
                                                name="status"
                                                :value="1"
                                                v-model="data.user.status"
                                                :label="trans('admin::app.settings.users.index.create.status')"
                                                ::checked="data.user.status"
                                            />

                                            <x-admin::form.control-group.error control-name="status" />
                                        </div>
                                    </x-admin::form.control-group>
                                </template>

                                <template v-else>
                                    <input type="hidden" name="status" v-model="data.user.status">
                                </template>
                            </div>

                            <x-admin::form.control-group v-if="isUpdating">
                                <x-admin::media.images
                                    name="image"
                                    ::uploaded-images="data.images"
                                    :show-suggestions="false"
                                />

                                <x-admin::form.control-group.error control-name="image" />

                                <p class="required my-3 text-sm text-gray-400">
                                    @lang('admin::app.settings.users.index.create.upload-image-info')
                                </p>
                            </x-admin::form.control-group>
                        </x-slot>

                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('admin::app.settings.users.index.create.save-btn')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form
                    @submit="handleSubmit($event, UserConfirmModal)"
                    ref="confirmPassword"
                >
                    <x-admin::modal ref="confirmPasswordModal">
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.settings.users.index.confirm-password-before-delete')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group class="mb-2.5">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.users.index.enter-current-password')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    id="password"
                                    name="password"
                                    rules="required"
                                    :label="trans('admin::app.settings.users.index.password')"
                                    :placeholder="trans('admin::app.settings.users.index.password')"
                                />

                                <x-admin::form.control-group.error control-name="password" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('admin::app.settings.users.index.confirm-delete-account')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </script>

        <script type="module">
            app.component('v-users', {
                template: '#v-users-template',

                data() {
                    return {
                        isUpdating: false, 

                        data: {
                            user: {},
                            images: [],
                        },

                        currentUserId: "{{ auth()->guard('admin')->user()->id }}",
                    }
                },

                methods: {
                    updateOrCreate(params, { setErrors }) {
                        let formData = new FormData(this.$refs.userCreateForm);

                        if (params.id) {
                            formData.append('_method', 'put');
                        }

                        this.$axios.post(params.id ? "{{ route('admin.settings.users.update') }}" : "{{ route('admin.settings.users.store') }}", formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data',
                                }
                            })
                            .then((response) => {
                                this.$refs.userUpdateOrCreateModal.close();

                                this.$refs.datagrid.get();

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                if (response.data.redirect_url) {
                                    this.$navigate(response.data.redirect_url);

                                    return;
                                }

                                this.resetForm();
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },

                    editModal(url) {
                        this.$navigate(url);
                    },

                    UserConfirmModal() {
                        let formData = new FormData(this.$refs.confirmPassword);

                        formData.append('_method', 'put');

                        this.$axios.post("{{ route('admin.settings.users.destroy')}}", formData)
                            .then((response) => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                this.$navigate(response.data.redirectUrl);
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });

                                this.$refs.confirmPasswordModal.toggle();
                            });
                    },

                    resetForm() {
                        this.isUpdating = false;

                        this.data = {
                            user: {},
                            images: [],
                        };
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
