<x-admin::layouts.anonymous>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.users.forget-password.create.page-title')
    </x-slot>

    <div class="flex min-h-screen w-full items-center justify-center p-6 bg-gradient-to-b from-violet-50 to-gray-50 dark:from-cherry-900 dark:to-cherry-900">
        <div class="w-full max-w-[400px]">
            <!-- Logo -->
            <div class="mb-8 flex justify-center">
                @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                    <img
                        class="h-10"
                        src="{{ Storage::url($logo) }}"
                        alt="{{ config('app.name') }}"
                    />
                @else
                    {{-- Default UnoPim logo — swaps with the theme. --}}
                    <img
                        class="h-10 w-max dark:hidden"
                        src="{{ unopim_asset('images/logo.svg') }}"
                        alt="{{ config('app.name') }}"
                    />

                    <img
                        class="h-10 w-max hidden dark:block"
                        src="{{ unopim_asset('images/dark_logo.svg') }}"
                        alt="{{ config('app.name') }}"
                    />
                @endif
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-cherry-700 bg-white dark:bg-cherry-800 shadow-[0_8px_30px_rgba(0,0,0,0.08)] p-6 sm:p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    @lang('admin::app.users.forget-password.create.title')
                </h1>

                <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">
                    @lang('admin::app.users.forget-password.create.subtitle')
                </p>

                <!-- Forget Password Form -->
                <x-admin::form :action="route('admin.forget_password.store')" :track-dirty="false" ajax="true">
                    <!-- Registered Email -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.users.forget-password.create.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="email"
                            class="w-full"
                            id="email"
                            name="email"
                            rules="required|email"
                            :value="old('email')"
                            :label="trans('admin::app.users.forget-password.create.email')"
                            :placeholder="trans('admin::app.users.forget-password.create.email')"
                            autocomplete="username"
                            autofocus
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <!-- Submit -->
                    <button
                        type="submit"
                        class="primary-button w-full justify-center py-2.5 mt-2"
                        aria-label="{{ trans('admin::app.users.forget-password.create.submit-btn') }}"
                    >
                        @lang('admin::app.users.forget-password.create.submit-btn')
                    </button>

                    <!-- Back to Sign In -->
                    <a
                        class="mt-4 block text-center text-sm text-violet-700 dark:text-violet-400 font-semibold hover:underline"
                        href="{{ route('admin.session.create') }}"
                    >
                        @lang('admin::app.users.forget-password.create.sign-in-link')
                    </a>
                </x-admin::form>
            </div>

            <!-- Powered By -->
            <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
                <div>
                    @lang('admin::app.users.forget-password.create.powered-by', [
                        'unopim' => '<a class="text-violet-700 dark:text-violet-400 hover:underline" href="https://unopim.com/" target="_blank">Unopim</a>',
                    ])
                </div>
                <div>
                    @lang('admin::app.users.forget-password.create.open-source-project-by', [
                        'webkul' => '<a class="text-violet-700 dark:text-violet-400 hover:underline" href="https://webkul.com/" target="_blank">Webkul</a>',
                    ])
                </div>
            </div>
        </div>
    </div>
</x-admin::layouts.anonymous>
