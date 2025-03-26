@php
    $admin = auth()->guard('admin')->user();
@endphp

<header class="flex justify-between items-center px-4 py-2.5 bg-white dark:bg-cherry-700  border-b dark:border-cherry-800 sticky top-0 z-[10001]">
    <div class="flex gap-1.5 items-center">
        <!-- Hamburger Menu -->
        <i
            class="hidden icon-menu text-2xl p-1.5 rounded-md cursor-pointer hover:bg-violet-50 dark:hover:bg-cherry-800 max-lg:block"
            @click="$refs.sidebarMenuDrawer.open()"
        >
        </i>

        <!-- Logo -->
        <a href="{{ route('admin.dashboard.index') }}">
            @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                <img
                    class="h-10"
                    src="{{ Storage::url($logo) }}"
                    alt="{{ config('app.name') }}"
                />
            @else
                <img
                    src="{{ request()->cookie('dark_mode') ? unopim_asset('images/dark_logo.svg') : unopim_asset('images/logo.svg') }}"
                    id="logo-image"
                    alt="{{ config('app.name') }}"
                />
            @endif
        </a>

    </div>

    <div class="flex gap-2.5 items-center">
        <!-- Dark mode Switcher -->
        <v-dark>
            <div class="flex">
                <span
                    class="{{ request()->cookie('dark_mode') ? 'icon-light' : 'icon-dark' }} p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                ></span>
            </div>
        </v-dark>

        <!-- Notification Component -->
        <v-notifications {{ $attributes }}>
            <span class="flex relative">
                <span 
                    class="icon-notification p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-950" 
                    title="@lang('admin::app.components.layouts.header.notifications')"
                >
                </span>
            </span>
        </v-notifications>
   
        <!-- Admin profile -->
        <x-admin::dropdown position="bottom-right">
            <x-slot:toggle>
                @if ($admin->image)
                    <button class="flex w-9 h-9 overflow-hidden rounded-full cursor-pointer hover:opacity-80 focus:opacity-80">
                        <img
                            src="{{ $admin->image_url }}"
                            class="w-full h-full object-cover object-top"
                            alt="{{ $admin->image_url }}"
                        />
                    </button>
                @else
                    <button class="flex justify-center items-center w-9 h-9 bg-violet-400 rounded-full text-sm text-white font-semibold cursor-pointer leading-6 transition-all hover:bg-violet-500 focus:bg-violet-500">
                        {{ substr($admin->name, 0, 1) }}
                    </button>
                @endif
                  
            </x-slot>

            <!-- Admin Dropdown -->
            <x-slot:content class="!p-0">
                <div class="flex gap-1.5 items-center px-5 py-2.5 border border-b-gray-300 dark:border-gray-800">
                    <img
                        src="{{ url('cache/logo/unopim.png') }}"
                        width="24"
                        height="24"
                    />

                    <!-- Version -->
                    <p class="text-gray-400">
                        @lang('admin::app.components.layouts.header.app-version', ['version' => 'v' . core()->version()])
                    </p>
                </div>

                <div class="grid gap-1 pb-2.5">
                    <a
                        class="px-5 py-2 text-base  text-gray-800 dark:text-white hover:bg-violet-50 dark:hover:bg-cherry-800 cursor-pointer"
                        href="{{ route('admin.account.edit') }}"
                    >
                        @lang('admin::app.components.layouts.header.my-account')
                    </a>

                    <!--Admin logout-->
                    <x-admin::form
                        method="DELETE"
                        action="{{ route('admin.session.destroy') }}"
                        id="adminLogout"
                    >
                    </x-admin::form>

                    <a
                        class="px-5 py-2 text-base  text-gray-800 dark:text-white hover:bg-violet-50 dark:hover:bg-cherry-800 cursor-pointer"
                        href="{{ route('admin.session.destroy') }}"
                        onclick="event.preventDefault(); document.getElementById('adminLogout').submit();"
                    >
                        @lang('admin::app.components.layouts.header.logout')
                    </a>
                </div>
            </x-slot>
        </x-admin::dropdown>
    </div>
</header>

<!-- Menu Sidebar Drawer -->
<x-admin::drawer
    position="left"
    width="270px"
    ref="sidebarMenuDrawer"
>
    <!-- Drawer Header -->
    <x-slot:header>
        <div class="flex justify-between items-center">
            @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                <img
                    class="h-10"
                    src="{{ Storage::url($logo) }}"
                    alt="{{ config('app.name') }}"
                />
            @else
                <img
                    src="{{ request()->cookie('dark_mode') ? unopim_asset('images/dark_logo.svg') : unopim_asset('images/logo.svg') }}"
                    id="logo-image"
                    alt="{{ config('app.name') }}"
                />
            @endif
        </div>
    </x-slot>

    <!-- Drawer Content -->
    <x-slot:content class="p-4">
        <div class="h-[calc(100vh-100px)] overflow-auto journal-scroll">
            <nav class="grid gap-2 w-full">
                <!-- Navigation Menu -->
                @foreach ($menu->items as $menuItem)
                    <div class="relative group/item">
                        <a
                            href="{{ $menuItem['url'] }}"
                            class="flex gap-2.5 p-1.5 items-center cursor-pointer {{ $menu->getActive($menuItem) == 'active' ? 'bg-violet-100 rounded-lg' : ' hover:bg-violet-50 dark:hover:bg-cherry-800 ' }} peer"
                        >
                            <span class="{{ $menuItem['icon'] }} text-2xl {{ $menu->getActive($menuItem) ? 'text-violet-700' : ''}}"></span>
                            
                            <p class="text-gray-600 dark:text-gray-300 font-semibold whitespace-nowrap {{ $menu->getActive($menuItem) ? 'text-violet-700' : 'text-gray-600'}}">
                                @lang($menuItem['name'])
                            </p>
                        </a>

                        @if (count($menuItem['children']))
                            <div class="{{ $menu->getActive($menuItem) ? '!grid' : '' }} hidden min-w-[180px] ltr:pl-10 rtl:pr-10 pb-2 rounded-b-lg z-[100]">
                                @foreach ($menuItem['children'] as $subMenuItem)
                                    <a
                                        href="{{ $subMenuItem['url'] }}"
                                        class="text-sm {{ $menu->getActive($subMenuItem) ? 'text-violet-700 dark:text-violet-700':'text-gray-600 dark:text-gray-300' }} whitespace-nowrap py-1  hover:text-violet-700 hover:bg-gray-950"
                                    >
                                        @lang($subMenuItem['name'])
                                    </a> 
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>
        </div>
    </x-slot>
</x-admin::drawer>

@pushOnce('scripts')

    <script type="text/x-template" id="v-notifications-template">
        <x-admin::dropdown position="bottom-right">
            <!-- Notification Toggle -->
            <x-slot:toggle>
                <span class="flex relative">
                    <span
                        class="icon-notification p-1.5 rounded-md text-2xl text-red cursor-pointer transition-all hover:bg-gray-100 dark:hover:bg-gray-950" 
                        title="@lang('admin::app.components.layouts.header.notifications')"
                    >
                    </span>
                
                    <span
                        class="flex justify-center items-center min-w-5 h-5 absolute -top-2 p-1.5 ltr:left-5 rtl:right-5 bg-violet-400 rounded-full text-white text-[10px] font-semibold leading-[9px] cursor-pointer"
                        v-text="totalUnRead"
                        v-if="totalUnRead"
                    >
                    </span>
                </span>
            </x-slot>

            <!-- Notification Content -->
            <x-slot:content class="p-5 w-[360px] max-w-[360px] max-h-[calc(100vh-130px)] overflow-auto journal-scroll !p-0">
                <!-- Header -->
                <div class="text-base  p-3 text-gray-800 dark:text-gray-300 font-bold border-b dark:border-gray-800">
                    @lang('admin::app.notifications.title')
                </div>

                <!-- Content -->
                <div class="grid">
                    <div v-for="userNotification in userNotifications">
                        <a
                            v-if="userNotification.notification?.id"
                            class="flex gap-1.5 items-start p-3 border-b dark:border-gray-800 last:border-b-0"
                            :key="userNotification.notification.id"
                            :href="'{{ route('admin.notification.viewed_notification', ':id') }}'.replace(':id', userNotification.notification.id)"
                            :class="{
                                'bg-gray-100 dark:bg-cherry-800': userNotification.read === 1,
                                'hover:bg-gray-50 dark:hover:bg-gray-950': true,
                            }"
                        >
                            <div class="grid gap-3">
                                <p 
                                    class="text-sm text-gray-800 dark:text-slate-50 font-semibold"
                                    v-text="userNotification.notification.title"
                                ></p>
    
                                <p 
                                    class="text-sm text-gray-600 dark:text-gray-300"
                                    v-html="userNotification.notification.description"
                                ></p>
                                
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    @{{ userNotification.notification.created_at_human }}
                                </p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex gap-1.5 justify-between h-[47px] py-4 px-6 border-t dark:border-gray-800">
                    <a
                        href="{{ route('admin.notification.index') }}"
                        class="text-sm text-violet-700 font-semibold cursor-pointer transition-all hover:underline"
                    >
                        @lang('admin::app.notifications.view-all')
                    </a>

                    <a
                        class="text-sm text-violet-700 font-semibold cursor-pointer transition-all hover:underline"
                        v-if="userNotifications?.length"
                        @click="readAll()"
                    >
                        @lang('admin::app.notifications.read-all')
                    </a>
                </div>
            </x-slot>
        </x-admin::dropdown>
    </script>

    <script type="module">
        app.component('v-notifications', {
            template: '#v-notifications-template',

                props: [
                    'getReadAllUrl',
                    'readAllTitle',
                ],

                data() {
                    return {
                        userNotifications: [],

                        totalUnRead: 0,
                    }
                },

                mounted() {
                    this.getNotification();
                    this.userNotifications = setInterval(this.getNotification, 15000);
                },

                methods: {
                    getNotification() {
                        this.$axios.get('{{ route('admin.notification.get_notification') }}', {
                                params: {
                                    limit: 5
                                }
                            })
                            .then((response) => {
                                this.userNotifications = response.data.search_results.data;

                                this.totalUnRead =   response.data.total_unread;
                            })
                            .catch(error => console.log(error))
                    },

                    readAll() {
                        this.$axios.post('{{ route('admin.notification.read_all') }}')
                            .then((response) => {
                                this.notifications = response.data.search_results.data;

                                this.totalUnRead = response.data.total_unread;

                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.success_message });
                        })
                        .catch((error) => {});
                },
            },
        });
    </script>

    <script type="text/x-template" id="v-dark-template">
        <div class="flex">
            <span
                class="p-1.5 rounded-md text-2xl cursor-pointer transition-all hover:bg-violet-50 dark:hover:bg-cherry-800"
                :class="[isDarkMode ? 'icon-light' : 'icon-dark']"
                @click="toggle"
            ></span>
        </div>
    </script>

    <script type="module">
        app.component('v-dark', {
            template: '#v-dark-template',

            data() {
                return {
                    isDarkMode: {{ request()->cookie('dark_mode') ?? 0 }},

                    logo: "{{ unopim_asset('images/logo.svg') }}",

                    dark_logo: "{{ unopim_asset('images/dark_logo.svg') }}",
                };
            },

            methods: {
                toggle() {
                    this.isDarkMode = parseInt(this.isDarkModeCookie()) ? 0 : 1;

                    var expiryDate = new Date();

                    expiryDate.setMonth(expiryDate.getMonth() + 1);

                    document.cookie = 'dark_mode=' + this.isDarkMode + '; path=/; expires=' + expiryDate.toGMTString();

                    document.documentElement.classList.toggle('dark', this.isDarkMode === 1);

                    if (this.isDarkMode) {
                        this.$emitter.emit('change-theme', 'dark');

                        document.getElementById('logo-image').src = this.dark_logo;
                    } else {
                        this.$emitter.emit('change-theme', 'light');

                        document.getElementById('logo-image').src = this.logo;
                    }
                },

                isDarkModeCookie() {
                    const cookies = document.cookie.split(';');

                    for (const cookie of cookies) {
                        const [name, value] = cookie.trim().split('=');

                        if (name === 'dark_mode') {
                            return value;
                        }
                    }

                    return 0;
                },
            },
        });
    </script>
@endpushOnce
