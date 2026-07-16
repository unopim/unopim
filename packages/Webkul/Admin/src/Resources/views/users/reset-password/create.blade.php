<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('admin::app.users.reset-password.title')
    </x-slot>

    <div class="flex min-h-screen w-full items-center justify-center p-6 bg-gradient-to-b from-primary-50 to-gray-50 dark:from-cherry-900 dark:to-cherry-900">
        <div class="w-full max-w-[400px]">
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
                    @lang('admin::app.users.reset-password.title')
                </h1>

                <p class="mt-1 mb-6 text-sm text-gray-500 dark:text-gray-400">
                    @lang('admin::app.users.reset-password.subtitle')
                </p>

                <x-admin::form :action="route('admin.reset_password.store')" :track-dirty="false" ajax="true">
                    <x-admin::form.control-group.control
                        type="hidden"
                        name="token"
                        :value="$token"
                    />

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.users.reset-password.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="email"
                            class="w-full"
                            id="email"
                            name="email"
                            rules="required|email"
                            :value="request('email')"
                            :label="trans('admin::app.users.reset-password.email')"
                            :placeholder="trans('admin::app.users.reset-password.email')"
                            autocomplete="username"
                            autofocus
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="relative w-full">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.users.reset-password.password')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            class="w-full ltr:pr-10 rtl:pl-10"
                            id="password"
                            name="password"
                            rules="required|min:{{ config('admin.auth.password_min') }}"
                            :label="trans('admin::app.users.reset-password.password')"
                            :placeholder="trans('admin::app.users.reset-password.password')"
                            autocomplete="new-password"
                            ref="password"
                        />

                        <span
                            class="text-2xl cursor-pointer absolute top-[42px] -translate-y-2/4 ltr:right-2 rtl:left-2 icon-view text-gray-500"
                            onclick="switchVisibility('password', 'passwordIcon')"
                            id="passwordIcon"
                            role="button"
                            tabindex="0"
                            aria-label="{{ trans('admin::app.users.sessions.toggle-password') }}"
                        >
                        </span>

                        <x-admin::form.control-group.error control-name="password" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="relative w-full">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.users.reset-password.confirm-password')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            class="w-full ltr:pr-10 rtl:pl-10"
                            id="password_confirmation"
                            name="password_confirmation"
                            rules="confirmed:@password"
                            :label="trans('admin::app.users.reset-password.confirm-password')"
                            :placeholder="trans('admin::app.users.reset-password.confirm-password')"
                            autocomplete="new-password"
                        />

                        <span
                            class="text-2xl cursor-pointer absolute top-[42px] -translate-y-2/4 ltr:right-2 rtl:left-2 icon-view text-gray-500"
                            onclick="switchVisibility('password_confirmation', 'passwordConfirmationIcon')"
                            id="passwordConfirmationIcon"
                            role="button"
                            tabindex="0"
                            aria-label="{{ trans('admin::app.users.sessions.toggle-password') }}"
                        >
                        </span>

                        <x-admin::form.control-group.error control-name="password_confirmation" />
                    </x-admin::form.control-group>

                    <button
                        type="submit"
                        class="primary-button w-full justify-center py-2.5 mt-2"
                        aria-label="{{ trans('admin::app.users.reset-password.submit-btn') }}"
                    >
                        @lang('admin::app.users.reset-password.submit-btn')
                    </button>

                    <a
                        class="mt-4 block text-center text-sm text-primary-700 dark:text-primary-400 font-semibold hover:underline"
                        href="{{ route('admin.session.create') }}"
                    >
                        @lang('admin::app.users.reset-password.back-link-title')
                    </a>
                </x-admin::form>
            </div>

            <div class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
                <div>
                    @lang('admin::app.users.reset-password.powered-by', [
                        'unopim' => '<a class="text-primary-700 dark:text-primary-400 hover:underline" href="https://unopim.com/" target="_blank">Unopim</a>',
                    ])
                </div>
                <div>
                    @lang('admin::app.users.reset-password.open-source-project-by', [
                        'webkul' => '<a class="text-primary-700 dark:text-primary-400 hover:underline" href="https://webkul.com/" target="_blank">Webkul</a>',
                    ])
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function switchVisibility(fieldId, iconId) {
                let passwordField = document.getElementById(fieldId);
                let visibilityIcon = document.getElementById(iconId);

                passwordField.type = passwordField.type === "password" ? "text" : "password";

                visibilityIcon.classList.toggle("icon-view");
                visibilityIcon.classList.toggle("icon-view-close");
            }
        </script>
    @endpush
</x-admin::layouts.anonymous>
