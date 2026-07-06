<x-admin::layouts.anonymous>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.users.sessions.title')
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
                    @lang('admin::app.users.sessions.title')
                </h1>

                <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">
                    @lang('admin::app.users.sessions.subtitle')
                </p>

                @if ($isMicrosoftSsoConfigured ?? false)
                    <!-- SSO (primary when configured) -->
                    <a
                        href="{{ route('admin.session.microsoft.redirect') }}"
                        class="flex justify-center items-center gap-2 w-full px-4 py-2.5 mb-4 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-cherry-700 transition-colors"
                    >
                        @lang('admin::app.users.sessions.sso-sign-in-with-microsoft')
                    </a>

                    <div class="relative my-4">
                        <div class="absolute inset-0 flex items-center">
                            <span class="w-full border-t border-gray-200 dark:border-gray-700"></span>
                        </div>
                        <div class="relative flex justify-center text-xs uppercase">
                            <span class="px-2 bg-white dark:bg-cherry-800 text-gray-400">
                                @lang('admin::app.users.sessions.sso-divider')
                            </span>
                        </div>
                    </div>
                @endif

                <!-- Login Form -->
                <x-admin::form :action="route('admin.session.store')" :track-dirty="false" ajax="true">
                    <!-- Email -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.users.sessions.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="email"
                            class="w-full"
                            id="email"
                            name="email"
                            rules="required|email"
                            :value="old('email')"
                            :label="trans('admin::app.users.sessions.email')"
                            :placeholder="trans('admin::app.users.sessions.email')"
                            autocomplete="username"
                            autofocus
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <!-- Password -->
                    <x-admin::form.control-group class="relative w-full">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.users.sessions.password')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            class="w-full ltr:pr-10 rtl:pl-10"
                            id="password"
                            name="password"
                            rules="required"
                            :label="trans('admin::app.users.sessions.password')"
                            :placeholder="trans('admin::app.users.sessions.password')"
                            autocomplete="current-password"
                        />

                        <span
                            class="text-2xl cursor-pointer absolute top-[42px] -translate-y-2/4 ltr:right-2 rtl:left-2 icon-view text-gray-500"
                            onclick="switchVisibility()"
                            id="visibilityIcon"
                            role="button"
                            tabindex="0"
                            aria-label="{{ trans('admin::app.users.sessions.toggle-password') }}"
                        >
                        </span>

                        <x-admin::form.control-group.error control-name="password" />
                    </x-admin::form.control-group>

                    <!-- Remember me + Forgot -->
                    <div class="flex justify-between items-center mt-1 mb-5">
                        <x-admin::form.control-group class="flex gap-2 items-center !mb-0">
                            <x-admin::form.control-group.control
                                type="checkbox"
                                id="remember"
                                name="remember"
                                value="1"
                                for="remember"
                            />

                            <label
                                class="text-sm text-gray-700 dark:text-gray-300 font-medium cursor-pointer select-none"
                                for="remember"
                            >
                                @lang('admin::app.users.sessions.remember-me')
                            </label>
                        </x-admin::form.control-group>

                        <a
                            class="text-sm text-violet-700 dark:text-violet-400 font-semibold hover:underline"
                            href="{{ route('admin.forget_password.create') }}"
                        >
                            @lang('admin::app.users.sessions.forget-password-link')
                        </a>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        class="primary-button w-full justify-center py-2.5"
                        aria-label="{{ trans('admin::app.users.sessions.submit-btn') }}"
                    >
                        @lang('admin::app.users.sessions.submit-btn')
                    </button>
                </x-admin::form>
            </div>

            <!-- Powered By -->
            <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
                <div>
                    @lang('admin::app.users.sessions.powered-by', [
                        'unopim' => '<a class="text-violet-700 dark:text-violet-400 hover:underline" href="https://unopim.com/" target="_blank">Unopim</a>',
                    ])
                </div>
                <div>
                    @lang('admin::app.users.sessions.open-source-project-by', [
                        'webkul' => '<a class="text-violet-700 dark:text-violet-400 hover:underline" href="https://webkul.com/" target="_blank">Webkul</a>',
                    ])
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function switchVisibility() {
                let passwordField = document.getElementById("password");
                let visibilityIcon = document.getElementById("visibilityIcon");

                passwordField.type = passwordField.type === "password" ? "text" : "password";

                visibilityIcon.classList.toggle("icon-view");
                visibilityIcon.classList.toggle("icon-view-close");
            }
        </script>
    @endpush
</x-admin::layouts.anonymous>
