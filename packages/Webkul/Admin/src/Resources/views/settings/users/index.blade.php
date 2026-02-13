<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.settings.users.index.title')
    </x-slot>

    <v-users>
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.settings.users.index.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Create User Button -->
                @if (bouncer()->hasPermission('settings.users.users.create'))
                    <button
                        type="button"
                        class="primary-button"
                    >
                        @lang('admin::app.settings.users.index.create.title')
                    </button>
                @endif
            </div>
        </div>

        <!-- DataGrid Shimmer -->
        <x-admin::shimmer.datagrid />
    </v-users>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-users-template"
        >
            <div class="flex justify-between items-center">
                <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                    @lang('admin::app.settings.users.index.title')
                </p>

                <div class="flex gap-x-2.5 items-center">
                    <!-- User Create Button -->
                    @if (bouncer()->hasPermission('settings.users.users.create'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="resetForm();$refs.userUpdateOrCreateModal.open()"
                        >
                            @lang('admin::app.settings.users.index.create.title')
                        </button>
                    @endif
                </div>
            </div>

            <!-- Datagrid -->
            <x-admin::datagrid
                src="{{ route('admin.settings.users.index') }}"
                ref="datagrid"
            >
                @php
                    $hasPermission = bouncer()->hasPermission('settings.users.users.edit') || bouncer()->hasPermission('settings.users.users.delete');
                @endphp
                <!-- DataGrid Header -->
                <template #header="{columns, records, sortPage, applied}">
                    <div class="row grid {{ $hasPermission ? 'grid-cols-6' : 'grid-cols-5' }} grid-rows-1 gap-2.5 items-center px-4 py-2.5 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-cherry-800 font-semibold">
                        <div
                            class="flex gap-2.5 cursor-pointer"
                            v-for="(columnGroup, index) in ['user_id', 'user_name', 'status', 'email', 'role_name']"
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

                                <!-- Filter Arrow Icon -->
                                <i
                                    class="ltr:ml-1.5 rtl:mr-1.5 text-base  text-gray-800 dark:text-white align-text-bottom"
                                    :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                    v-if="columnGroup.includes(applied.sort.column)"
                                ></i>
                            </p>
                        </div>

                        <!-- Actions -->
                        @if ($hasPermission)
                            <p class="flex gap-2.5 justify-end">
                                @lang('admin::app.components.datagrid.table.actions')
                            </p>
                        @endif
                    </div>
                </template>

                <!-- DataGrid Body -->
                <template #body="{ columns, records, performAction }">
                    <div
                        v-for="record in records"
                        class="row grid gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                        :style="'grid-template-columns: repeat(' + (record.actions.length ? 6 : 5) + ', minmax(0, 1fr));'"
                    >
                        <!-- Id -->
                        <p v-text="record.user_id"></p>

                        <!-- User Profile -->
                        <p>
                            <div class="flex gap-2.5 items-center">
                                <div
                                    class="inline-block w-9 h-9 rounded-full border-3 border-gray-800 align-middle text-center mr-2 overflow-hidden"
                                    v-if="record.user_img"
                                >
                                    <img
                                        class="h-9 object-cover"
                                        :src="record.user_img"
                                        alt="record.user_name"
                                    />
                                </div>

                                <div
                                    class="profile-info-icon"
                                    v-else
                                >
                                    <button
                                        class="flex justify-center items-center w-9 h-9 bg-violet-400 rounded-full text-sm text-white font-semibold cursor-pointer leading-6 transition-all hover:bg-violet-700 focus:bg-violet-700"
                                        v-text="record.user_name[0]?.toUpperCase()"
                                    >
                                    </button>
                                </div>

                                <div
                                    class="text-sm"
                                    v-text="record.user_name"
                                >
                                </div>
                            </div>
                        </p>

                        <!-- Status -->
                        <p v-html="record.status"></p>

                        <!-- Email -->
                        <p class="break-words" v-text="record.email"></p>

                        <!-- Role -->
                        <p v-text="record.role_name"></p>

                        <!-- Actions -->
                        <div class="flex justify-end">
                            <a @click="id=1; editModal(record.actions.find(action => action.index === 'edit')?.url)">
                                <span
                                    :class="record.actions.find(action => action.index === 'edit')?.icon"
                                    title="@lang('admin::app.settings.users.index.datagrid.edit')"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                >
                                </span>
                            </a>

                            <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                <span
                                    :class="record.actions.find(action => action.index === 'delete')?.icon"
                                    title="@lang('admin::app.settings.users.index.datagrid.delete')"
                                    class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800 max-sm:place-self-center"
                                >
                                </span>
                            </a>
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            <!-- Modal Form -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, updateOrCreate)"
                    ref="userCreateForm"
                >
                    <!-- User Create Modal -->
                    <x-admin::modal ref="userUpdateOrCreateModal">
                        <!-- Modal Header -->
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

                        <!-- Modal Content -->
                        <x-slot:content>
                            <!-- Name -->
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

                            <!-- Email -->
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
                                <!-- Password -->
                                <x-admin::form.control-group class="flex-1 mb-2.5">
                                    <x-admin::form.control-group.label ::class="isUpdating ? '' : 'required'">
                                        @lang('admin::app.settings.users.index.create.password')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="password"
                                        id="password"
                                        name="password"
                                        ::rules="isUpdating ? 'min:6' : 'required|min:6'"
                                        v-model="data.user.password"
                                        :label="trans('admin::app.settings.users.index.create.password')"
                                        :placeholder="trans('admin::app.settings.users.index.create.password')"
                                        ref="password"
                                    />

                                    <x-admin::form.control-group.error control-name="password" />
                                </x-admin::form.control-group>

                                <!-- Confirm Password -->
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

                            <x-admin::form.control-group class="mb-4">
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.channels.edit.ui-locale')
                                </x-admin::form.control-group.label>

                                @php
                                    $locales = core()->getAllActiveLocales();
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

                            <!-- Role -->
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

                            <!-- Tenant (platform operator only) -->
                            @if (! auth()->guard('admin')->user()->tenant_id)
                                <x-admin::form.control-group class="flex-1 w-full">
                                    <x-admin::form.control-group.label>
                                        @lang('Tenant')
                                    </x-admin::form.control-group.label>

                                    @php
                                        $tenants = json_encode(
                                            \Webkul\Tenant\Models\Tenant::where('status', 'active')
                                                ->select('id', 'name')
                                                ->get()
                                                ->toArray()
                                        );
                                    @endphp

                                    <x-admin::form.control-group.control
                                        type="select"
                                        name="tenant_id"
                                        :multiple="false"
                                        v-model="data.user.tenant_id"
                                        :label="trans('Tenant')"
                                        placeholder="Platform (No Tenant)"
                                        :options="$tenants"
                                        track-by="id"
                                        label-by="name"
                                    >
                                    </x-admin::form.control-group.control>

                                    <p class="mt-1 text-xs text-gray-500">
                                        @lang('Leave empty to create a platform operator.')
                                    </p>

                                    <x-admin::form.control-group.error control-name="tenant_id" />
                                </x-admin::form.control-group>
                            @endif

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

                            <x-admin::form.control-group>
                                <div class="hidden">
                                    <x-admin::media.images
                                        name="image"
                                        ::uploaded-images='data.images'
                                    />
                                </div>

                                <v-media-images
                                    name="image"
                                    :uploaded-images='data.images'
                                >
                                </v-media-images>

                                <x-admin::form.control-group.error control-name="image" />

                                <p class="required my-3 text-sm text-gray-400">
                                    @lang('admin::app.settings.users.index.create.upload-image-info')
                                </p>
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
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

            <!-- User Delete Password Form -->
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form
                    @submit="handleSubmit($event, UserConfirmModal)"
                    ref="confirmPassword"
                >
                    <x-admin::modal ref="confirmPasswordModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('Confirm Password Before DELETE')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            <!-- Password -->
                            <x-admin::form.control-group class="mb-2.5">
                                <x-admin::form.control-group.label class="required">
                                    @lang('Enter Current Password')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="password"
                                    id="password"
                                    name="password"
                                    rules="required"
                                    :label="trans('Password')"
                                    :placeholder="trans('Password')"
                                />

                                <x-admin::form.control-group.error control-name="password" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <div class="flex gap-x-2.5 items-center">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('Confirm Delete This Account')
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

                                this.resetForm();
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },

                    editModal(url) {
                        this.isUpdating = true;

                        this.$axios.get(url)
                            .then((response) => {
                                this.data = {
                                    ...response.data,
                                        images: response.data.user.image_url
                                        ? [{ id: 'image', url: response.data.user.image_url, value: response.data.user.image }]
                                        : [],
                                        user: {
                                            ...response.data.user,
                                            password:'',
                                            password_confirmation:'',
                                        },
                                };

                                this.$refs.modalForm.setValues(response.data.user);

                                this.$refs.userUpdateOrCreateModal.toggle();
                            })
                            .catch(error => this.$emitter.emit('add-flash', { 
                                type: 'error', message: error.response.data.message 
                            }));
                    },

                    UserConfirmModal() {
                        let formData = new FormData(this.$refs.confirmPassword);

                        formData.append('_method', 'put');

                        this.$axios.post("{{ route('admin.settings.users.destroy')}}", formData)
                            .then((response) => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                window.location.href = response.data.redirectUrl;
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
