<!DOCTYPE html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ in_array(app()->getLocale(), ['ar_AE']) ? 'rtl' : 'ltr' }}"
>
    <head>
        <title>
            @lang('installer::app.installer.index.title')
        </title>

        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="base-url" content="{{ url()->to('/') }}">

        @stack('meta')

        @unoPimVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'], 'installer')

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        />

        <link
            type="image/x-icon"
            href="{{ unopim_asset('images/favicon.svg') }}"
            rel="shortcut icon"
            sizes="16x16"
        />

        @stack('styles')
    </head>

    @php
        $locales = [
            'ar_AE',
            'ca_ES',
            'da_DK',
            'de_DE',
            'en_AU',
            'en_GB',
            'en_NZ',
            'en_US',
            'es_ES',
            'es_VE',
            'fi_FI',
            'fr_FR',
            'hi_IN',
            'hr_HR',
            'it_IT',
            'ja_JP',
            'ko_KR',
            'nl_NL',
            'no_NO',
            'pl_PL',
            'pt_BR',
            'pt_PT',
            'ro_RO',
            'ru_RU',
            'sv_SE',
            'tl_PH',
            'tr_TR',
            'uk_UA',
            'vi_VN',
            'zh_CN',
            'zh_TW',
        ];

        $currencies = [
            'AED' => 'dirham',
            'AFN' => 'israeli',
            'CNY' => 'chinese-yuan',
            'EUR' => 'euro',
            'GBP' => 'pound',
            'INR' => 'rupee',
            'IRR' => 'iranian',
            'JPY' => 'japanese-yen',
            'RUB' => 'russian-ruble',
            'SAR' => 'saudi',
            'TRY' => 'turkish-lira',
            'UAH' => 'ukrainian-hryvnia',
            'USD' => 'usd',
        ];

        $addOnIcons = [
            'dam'     => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>',
            'shopify' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><path d="M3 6h18 M16 10a4 4 0 0 1-8 0"></path></svg>',
            'bagisto' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path></svg>',
        ];

        // Config first, install last. Order MUST match the Vue `steps` navigation order.
        $stepperItems = [
            'start'                 => 'installer::app.installer.index.start.main',
            'systemRequirements'    => 'installer::app.installer.index.server-requirements.title',
            'envDatabase'           => 'installer::app.installer.index.environment-configuration.title',
            'envConfiguration'      => 'installer::app.installer.index.environment-configuration.step-title',
            'createAdmin'           => 'installer::app.installer.index.create-administrator.title',
            'addOns'                => 'installer::app.installer.index.add-ons.title-step',
            'readyForInstallation'  => 'installer::app.installer.index.ready-for-installation.title',
            'installationCompleted' => 'installer::app.installer.index.installation-completed.title',
        ];
    @endphp

    <body class="bg-gray-50">
        <div id="app">
            <v-server-requirements
                :optional-packages='@json($optionalPackages)'
                cloud-hosting-url="{{ $cloudHostingUrl }}"
            ></v-server-requirements>
        </div>

        @pushOnce('scripts')
            <script type="text/x-template" id="v-server-requirements-template">
                <div class="min-h-screen flex flex-col bg-gray-50 font-inter">
                    <!-- Persistent Cloud Hosting Top Bar (every step) -->
                    <div class="sticky top-0 z-[10050] w-full">
                        <div class="flex items-center gap-4 px-5 h-12 text-[13.5px] bg-gradient-to-r from-[#5B41D6] to-[#8367F0] text-white">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <span class="inline-flex shrink-0">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.5 19a4.5 4.5 0 0 0 .5-8.97A6 6 0 0 0 6.2 9.1 4 4 0 0 0 6.5 19z"></path>
                                    </svg>
                                </span>

                                <span class="inline-flex items-center h-5 px-[9px] rounded-full text-[10px] font-extrabold tracking-[0.06em] uppercase shrink-0 bg-white/[0.18] text-white">
                                    @lang('installer::app.installer.index.cloud-bar.tag')
                                </span>

                                <span class="min-w-0 truncate font-medium">
                                    @lang('installer::app.installer.index.cloud-bar.message')
                                </span>
                            </div>

                            <select
                                class="shrink-0 h-[30px] rounded-lg border-0 bg-white/[0.18] text-white text-[12.5px] font-semibold px-2 cursor-pointer focus:outline-none [&>option]:text-gray-800"
                                onchange="window.location.href='/install?locale=' + this.value"
                                aria-label="@lang('installer::app.installer.index.wizard-language')"
                            >
                                @foreach ($locales as $value)
                                    <option value="{{ $value }}" @selected(app()->getLocale() === $value)>
                                        {{ Locale::getDisplayName($value, app()->getLocale()) }}
                                    </option>
                                @endforeach
                            </select>

                            <a
                                href="{{ $cloudHostingUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="shrink-0 inline-flex items-center gap-1.5 h-[30px] px-[14px] rounded-lg text-[12.5px] font-bold no-underline whitespace-nowrap transition-all bg-white text-[#5B41D6] hover:-translate-y-px hover:shadow-[0_6px_16px_rgba(0,0,0,0.18)]"
                            >
                                @lang('installer::app.installer.index.cloud-bar.cta')

                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14 M13 6l6 6-6 6"></path>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Two-column shell -->
                    <div class="flex-1 w-full flex items-center justify-center px-6 py-10">
                        <div class="w-full max-w-[1180px] flex flex-col gap-8 lg:flex-row lg:gap-12 lg:items-center">
                            <!-- LEFT: brand + vertical stepper -->
                            <aside class="w-full lg:w-[320px] shrink-0">
                                <div class="lg:sticky lg:top-[88px]">
                                    <img
                                        class="h-9 mb-8"
                                        src="{{ unopim_asset('images/logo.svg') }}"
                                        alt="@lang('installer::app.installer.index.unopim-logo')"
                                    >

                                    <div class="grid gap-1.5 mb-8">
                                        <p class="text-gray-800 text-[20px] font-bold !leading-snug">
                                            @lang('installer::app.installer.index.installation-title')
                                        </p>

                                        <p class="text-gray-600 text-[14px] !leading-normal">
                                            @lang('installer::app.installer.index.installation-info')
                                        </p>
                                    </div>

                                    <!-- Vertical Stepper -->
                                    <ol class="grid gap-1">
                                        @foreach ($stepperItems as $stepKey => $stepLabel)
                                            <li class="flex items-center gap-3 py-2">
                                                <span
                                                    class="flex items-center justify-center w-7 h-7 rounded-full text-[12px] font-bold shrink-0 transition-colors"
                                                    :class="stepStates.{{ $stepKey }} === 'complete'
                                                        ? 'bg-violet-700 text-white'
                                                        : (stepStates.{{ $stepKey }} === 'active'
                                                            ? 'bg-violet-100 text-violet-700 ring-2 ring-violet-700'
                                                            : 'bg-gray-100 text-gray-400')"
                                                >
                                                    <svg
                                                        v-if="stepStates.{{ $stepKey }} === 'complete'"
                                                        width="15" height="15" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="3"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                    >
                                                        <path d="M20 6 9 17l-5-5"></path>
                                                    </svg>

                                                    <span v-else>{{ $loop->iteration }}</span>
                                                </span>

                                                <span
                                                    class="text-[14px] transition-colors"
                                                    :class="stepStates.{{ $stepKey }} === 'active'
                                                        ? 'font-bold text-gray-800'
                                                        : (stepStates.{{ $stepKey }} === 'complete' ? 'font-semibold text-gray-700' : 'text-gray-500')"
                                                >
                                                    @lang($stepLabel)
                                                </span>
                                            </li>
                                        @endforeach
                                    </ol>
                                </div>
                            </aside>

                            <!-- RIGHT: card panels -->
                            <main class="flex-1 min-w-0">
                                <!-- Start -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'start'"
                                >
                                    <x-installer::form
                                        v-slot="{ meta, errors, handleSubmit }"
                                        as="div"
                                        ref="start"
                                    >
                                        <form
                                            @submit.prevent="handleSubmit($event, setLocale)"
                                            enctype="multipart/form-data"
                                            ref="multiLocaleForm"
                                        >
                                            <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                                <p class="text-[18px] text-gray-800 font-bold">
                                                    @lang('installer::app.installer.index.start.welcome-title',['version' => core()->version()])
                                                </p>
                                            </div>

                                            <div class="px-6 py-6">
                                                <p class="text-gray-600 text-[14px] !leading-normal mb-6">
                                                    @lang('installer::app.installer.index.installation-description')
                                                </p>

                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label>
                                                        @lang('installer::app.installer.index.wizard-language')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="select"
                                                        name="locale"
                                                        rules="required"
                                                        :value="app()->getLocale()"
                                                        :label="trans('installer::app.installer.index.start.locale')"
                                                        @change="$refs.multiLocaleForm.submit();"
                                                    >
                                                        <option
                                                            value=""
                                                            disabled
                                                        >
                                                            @lang('installer::app.installer.index.start.select-locale')
                                                        </option>

                                                        @foreach ($locales as $value)
                                                            <option value="{{ $value }}">
                                                                {{ Locale::getDisplayName($value, app()->getLocale()) }}
                                                            </option>
                                                        @endforeach
                                                    </x-installer::form.control-group.control>

                                                    <x-installer::form.control-group.error control-name="locale" />
                                                </x-installer::form.control-group>
                                            </div>

                                            <div class="flex px-6 py-4 justify-end items-center border-t border-gray-200">
                                                <button
                                                    type="button"
                                                    class="px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                                    tabindex="0"
                                                    @click="nextForm"
                                                >
                                                    @lang('installer::app.installer.index.continue')
                                                </button>
                                            </div>
                                        </form>
                                    </x-installer::form>
                                </div>

                                <!-- System Requirements -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'systemRequirements'"
                                >
                                    <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                        <p class="text-[18px] text-gray-800 font-bold">
                                            @lang('installer::app.installer.index.server-requirements.title')
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-x-8 gap-y-3 px-6 py-6 border-b border-gray-200 max-sm:grid-cols-1">
                                        <div class="flex gap-1.5 items-center">
                                            <span class="{{ $phpVersion['supported'] ? 'icon-tick text-[20px] text-green-500' : 'icon-cancel text-[20px] text-red-500' }}"></span>

                                            <p class="text-[14px] text-gray-700 font-semibold">
                                                @lang('installer::app.installer.index.server-requirements.php') <span class="font-normal text-gray-600">(@lang('installer::app.installer.index.server-requirements.php-version'))</span>
                                            </p>
                                        </div>

                                        @foreach ($requirements['requirements'] as $requirement)
                                            @foreach ($requirement as $key => $item)
                                                <div class="flex gap-1.5 items-center">
                                                    <span class="{{ $item ? 'icon-tick text-green-500' : 'icon-cancel text-red-500' }} text-[20px]"></span>

                                                    <p class="text-[14px] text-gray-700 font-semibold">
                                                        @lang('installer::app.installer.index.server-requirements.' . $key)
                                                    </p>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>

                                    @php
                                        $hasRequirement = false;

                                        foreach ($requirements['requirements']['php'] as $value) {
                                            if (!$value) {
                                                $hasRequirement = true;
                                                break;
                                            }
                                        }
                                    @endphp

                                    <div class="flex px-6 py-4 justify-between items-center">
                                        <div
                                            class="inline-flex items-center px-4 py-2 rounded-lg border border-violet-700 text-violet-700 text-[14px] font-semibold cursor-pointer hover:bg-violet-50 transition-all"
                                            role="button"
                                            aria-label="@lang('installer::app.installer.index.back')"
                                            tabindex="0"
                                            @click="back"
                                        >
                                            @lang('installer::app.installer.index.back')
                                        </div>

                                        <div
                                            class="{{ $hasRequirement ? 'opacity-50 cursor-not-allowed' : ''}} px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer {{ $hasRequirement ?: 'hover:opacity-90' }}"
                                            @click="nextForm"
                                            tabindex="0"
                                        >
                                            @lang('installer::app.installer.index.continue')
                                        </div>
                                    </div>
                                </div>

                                <!-- Environment Configuration Database -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'envDatabase'"
                                >
                                    <x-installer::form
                                        v-slot="{ meta, errors, handleSubmit }"
                                        as="div"
                                        ref="envDatabase"
                                    >
                                        <form
                                            @submit.prevent="handleSubmit($event, FormSubmit)"
                                            enctype="multipart/form-data"
                                        >
                                            <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                                <p class="text-[18px] text-gray-800 font-bold">
                                                    @lang('installer::app.installer.index.environment-configuration.title')
                                                </p>
                                            </div>

                                            <div class="grid grid-cols-2 gap-x-6 gap-y-1 px-6 py-6 border-b border-gray-200 max-sm:grid-cols-1">
                                                <!-- Database Connection-->
                                                <x-installer::form.control-group class="mb-2.5 col-span-2 max-sm:col-span-1">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.database-connection')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="select"
                                                        name="db_connection"
                                                        ::value="envData.db_connection ?? 'mysql'"
                                                        rules="required"
                                                        :label="trans('installer::app.installer.index.environment-configuration.database-connection')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.database-connection')"
                                                    >
                                                        <option
                                                            value="mysql"
                                                            selected
                                                        >
                                                            @lang('installer::app.installer.index.environment-configuration.mysql')
                                                        </option>

                                                        <option value="pgsql">
                                                            @lang('installer::app.installer.index.environment-configuration.pgsql')
                                                        </option>
                                                    </x-installer::form.control-group.control>

                                                    <x-installer::form.control-group.error control-name="db_connection" />
                                                </x-installer::form.control-group>

                                                <!-- Database Hostname-->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.database-hostname')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="db_hostname"
                                                        ::value="envData.db_hostname ?? '127.0.0.1'"
                                                        rules="required"
                                                        :label="trans('installer::app.installer.index.environment-configuration.database-hostname')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.database-hostname')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="db_hostname" />
                                                </x-installer::form.control-group>

                                                <!-- Database Port-->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.database-port')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="db_port"
                                                        ::value="envData.db_port ?? '3306'"
                                                        rules="required"
                                                        :label="trans('installer::app.installer.index.environment-configuration.database-port')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.database-port')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="db_port" />
                                                </x-installer::form.control-group>

                                                <!-- Database name-->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.database-name')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="db_name"
                                                        ::value="envData.db_name"
                                                        rules="required"
                                                        :label="trans('installer::app.installer.index.environment-configuration.database-name')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.database-name')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="db_name" />
                                                </x-installer::form.control-group>

                                                <!-- Database Prefix-->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label>
                                                        @lang('installer::app.installer.index.environment-configuration.database-prefix')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="db_prefix"
                                                        ::value="envData.db_prefix"
                                                        rules="max:4"
                                                        :label="trans('installer::app.installer.index.environment-configuration.database-prefix')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.database-prefix')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="db_prefix" />
                                                </x-installer::form.control-group>

                                                <!-- Database Username-->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.database-username')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="db_username"
                                                        ::value="envData.db_username"
                                                        rules="required"
                                                        :label="trans('installer::app.installer.index.environment-configuration.database-username')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.database-username')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="db_username" />
                                                </x-installer::form.control-group>

                                                <!-- Database Password-->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label>
                                                        @lang('installer::app.installer.index.environment-configuration.database-password')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="password"
                                                        name="db_password"
                                                        ::value="envData.db_password"
                                                        :label="trans('installer::app.installer.index.environment-configuration.database-password')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.database-password')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="db_password" />
                                                </x-installer::form.control-group>
                                            </div>

                                            <div class="flex px-6 py-4 justify-between items-center">
                                                <div
                                                    class="inline-flex items-center px-4 py-2 rounded-lg border border-violet-700 text-violet-700 text-[14px] font-semibold cursor-pointer hover:bg-violet-50 transition-all"
                                                    role="button"
                                                    :aria-label="@lang('installer::app.installer.index.back')"
                                                    tabindex="0"
                                                    @click="back"
                                                >
                                                    @lang('installer::app.installer.index.back')
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                                    tabindex="0"
                                                >
                                                    @lang('installer::app.installer.index.continue')
                                                </button>
                                            </div>
                                        </form>
                                    </x-installer::form>
                                </div>

                                <!-- Installation Processing (live terminal) -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'installProgress'"
                                >
                                    <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                        <p class="text-[18px] text-gray-800 font-bold">
                                            @lang('installer::app.installer.index.installation-processing.title')
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-3 px-6 py-6">
                                        <div class="flex items-center gap-2.5">
                                            <img
                                                v-if="installing"
                                                class="animate-spin h-5 w-5"
                                                src="{{ unopim_asset('images/installer/spinner.svg', 'installer') }}"
                                                alt="Loading"
                                            />

                                            <p class="text-[15px] text-gray-800 font-bold">
                                                @lang('installer::app.installer.index.terminal.title')
                                            </p>
                                        </div>

                                        <!-- Read-only terminal -->
                                        <div
                                            ref="terminal"
                                            class="bg-gray-900 text-gray-100 font-mono text-[12.5px] rounded-lg p-4 max-h-[460px] overflow-y-auto"
                                            role="log"
                                            aria-live="polite"
                                        >
                                            <pre
                                                v-for="(line, index) in terminalLines"
                                                :key="index"
                                                class="whitespace-pre-wrap break-words leading-relaxed"
                                            >@{{ line }}</pre>
                                        </div>
                                    </div>
                                </div>

                                <!-- Environment Configuration .ENV -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'envConfiguration'"
                                >
                                    <x-installer::form
                                        v-slot="{ meta, errors, handleSubmit }"
                                        as="div"
                                        ref="envSetup"
                                    >
                                        <form
                                            @submit.prevent="handleSubmit($event, nextForm)"
                                            enctype="multipart/form-data"
                                        >
                                            <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                                <p class="text-[18px] text-gray-800 font-bold">
                                                    @lang('installer::app.installer.index.environment-configuration.step-title')
                                                </p>
                                            </div>

                                            <div class="flex flex-col gap-3 px-6 py-6 border-b border-gray-200 max-h-[484px] overflow-y-auto">
                                                <!-- Application Name -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.application-name')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="app_name"
                                                        ::value="envData.app_name ?? 'UnoPim'"
                                                        rules="required"
                                                        :label="trans('installer::app.installer.index.environment-configuration.application-name')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.unopim')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="app_name" />
                                                </x-installer::form.control-group>

                                                <!-- Application Default URL -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.default-url')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="app_url"
                                                        ::value="envConfigData.app_url ?? defaultAppUrl"
                                                        rules="required"
                                                        :label="trans('installer::app.installer.index.environment-configuration.default-url')"
                                                        :placeholder="trans('installer::app.installer.index.environment-configuration.default-url-link')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="app_url" />
                                                </x-installer::form.control-group>

                                                <!-- Application Default Timezone -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.default-timezone')
                                                    </x-installer::form.control-group.label>

                                                    @php
                                                        date_default_timezone_set('UTC');

                                                        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

                                                        $current = date_default_timezone_get();
                                                    @endphp

                                                    <x-installer::form.control-group.control
                                                        type="select"
                                                        name="app_timezone"
                                                        ::value="envData.app_timezone ?? $current ?? 'UTC'"
                                                        rules="required"
                                                        :aria-label="trans('installer::app.installer.index.environment-configuration.default-timezone')"
                                                        :label="trans('installer::app.installer.index.environment-configuration.default-timezone')"
                                                    >
                                                        <option
                                                            value=""
                                                            disabled
                                                        >
                                                            @lang('installer::app.installer.index.environment-configuration.select-timezone')
                                                        </option>

                                                        @foreach($tzlist as $key => $value)
                                                            <option
                                                                value="{{ $value }}"
                                                                {{ $value === $current ? 'selected' : '' }}
                                                            >
                                                                {{ $value }}
                                                            </option>
                                                        @endforeach
                                                    </x-installer::form.control-group.control>

                                                    <x-installer::form.control-group.error control-name="app_timezone" />
                                                </x-installer::form.control-group>

                                                <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-100 text-gray-800 text-[13px]">
                                                    <i class="icon-limited !text-black"></i>

                                                    @lang('installer::app.installer.index.environment-configuration.warning-message')
                                                </div>

                                                <div class="flex gap-2.5">
                                                    <!-- Application Default Locale -->
                                                    <x-installer::form.control-group class="w-full">
                                                        <x-installer::form.control-group.label class="required">
                                                            @lang('installer::app.installer.index.environment-configuration.default-locale')
                                                        </x-installer::form.control-group.label>

                                                        <x-installer::form.control-group.control
                                                            type="select"
                                                            name="app_locale"
                                                            value="{{ app()->getLocale() }}"
                                                            rules="required"
                                                            :aria-label="trans('installer::app.installer.index.environment-configuration.default-locale')"
                                                            :label="trans('installer::app.installer.index.environment-configuration.default-locale')"
                                                        >
                                                            @foreach ($locales as $value)
                                                                <option value="{{ $value }}">
                                                                {{ Locale::getDisplayName($value, app()->getLocale()) }}
                                                                </option>
                                                            @endforeach
                                                        </x-installer::form.control-group.control>

                                                        <x-installer::form.control-group.error control-name="app_locale" />
                                                    </x-installer::form.control-group>

                                                    <!-- Application Default Currency -->
                                                    <x-installer::form.control-group class="w-full">
                                                        <x-installer::form.control-group.label class="required">
                                                            @lang('installer::app.installer.index.environment-configuration.default-currency')
                                                        </x-installer::form.control-group.label>

                                                        <x-installer::form.control-group.control
                                                            type="select"
                                                            name="app_currency"
                                                            ::value="envData.app_currency ?? 'USD'"
                                                            :aria-label="trans('installer::app.installer.index.environment-configuration.default-currency')"
                                                            rules="required"
                                                            :label="trans('installer::app.installer.index.environment-configuration.default-currency')"
                                                        >
                                                            <option value="" disabled>@lang('installer::app.installer.index.environment-configuration.default-currency')</option>

                                                            @foreach ($currencies as $value => $label)
                                                                <option value="{{ $value }}" @if($value == 'USD') selected @endif>
                                                                    @lang("installer::app.installer.index.environment-configuration.$label")
                                                                </option>
                                                            @endforeach
                                                        </x-installer::form.control-group.control>

                                                        <x-installer::form.control-group.error control-name="app_currency" />
                                                    </x-installer::form.control-group>
                                                </div>

                                                <div class="flex flex-col gap-4">
                                                    <x-installer::form.control-group class="w-full">
                                                        <x-installer::form.control-group.label class="required">
                                                            @lang('installer::app.installer.index.environment-configuration.allowed-locales')
                                                        </x-installer::form.control-group.label>

                                                        <!-- Allowed Locales -->
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 max-h-[176px] overflow-y-auto rounded-lg border border-gray-200 p-1.5 mt-1">
                                                        @foreach ($locales as $key)
                                                            <x-installer::form.control-group class="flex gap-2 w-full min-w-0 !mb-0 p-1 cursor-pointer select-none">
                                                                @php
                                                                    $selectedOption = ($key == config('app.locale'));
                                                                @endphp

                                                                <x-installer::form.control-group.control
                                                                    type="hidden"
                                                                    :name="$key"
                                                                    :value="(bool) $selectedOption"
                                                                />

                                                                <x-installer::form.control-group.control
                                                                    type="checkbox"
                                                                    :id="'allowed_locale[' . $key . ']'"
                                                                    :name="$key"
                                                                    :for="'allowed_locale[' . $key . ']'"
                                                                    value="1"
                                                                    :checked="(bool) $selectedOption"
                                                                    :disabled="(bool) $selectedOption"
                                                                    @change="pushAllowedLocales"
                                                                />

                                                                <x-installer::form.control-group.label
                                                                    for="allowed_locale[{{ $key }}]"
                                                                    class="!text-[14px] !font-semibold cursor-pointer"
                                                                >
                                                                    {{ Locale::getDisplayName($key, app()->getLocale()) }}
                                                                </x-installer::form.control-group.label>
                                                            </x-installer::form.control-group>
                                                        @endforeach
                                                        </div>
                                                    </x-installer::form.control-group>

                                                    <x-installer::form.control-group class="w-full">
                                                        <x-installer::form.control-group.label class="required">
                                                            @lang('installer::app.installer.index.environment-configuration.allowed-currencies')
                                                        </x-installer::form.control-group.label>

                                                        <!-- Allowed Currencies -->
                                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-3 max-h-[176px] overflow-y-auto rounded-lg border border-gray-200 p-1.5 mt-1">
                                                        @foreach ($currencies as $key => $currency)
                                                            <x-installer::form.control-group class="flex gap-2 w-full min-w-0 !mb-0 p-1 cursor-pointer select-none">
                                                                @php
                                                                    $selectedOption = $key == config('app.currency');
                                                                @endphp

                                                                <x-installer::form.control-group.control
                                                                    type="hidden"
                                                                    :name="$key"
                                                                    :value="(bool) $selectedOption"
                                                                />

                                                                <x-installer::form.control-group.control
                                                                    type="checkbox"
                                                                    :id="'currency[' . $key . ']'"
                                                                    :name="$key"
                                                                    value="1"
                                                                    :for="'currency[' . $key . ']'"
                                                                    :checked="(bool) $selectedOption"
                                                                    :disabled="(bool) $selectedOption"
                                                                    @change="pushAllowedCurrency"
                                                                />

                                                                <x-installer::form.control-group.label
                                                                    for="currency[{{ $key }}]"
                                                                    class="!text-[14px] !font-semibold cursor-pointer"
                                                                >
                                                                    @lang("installer::app.installer.index.environment-configuration.$currency")
                                                                </x-installer::form.control-group.label>
                                                            </x-installer::form.control-group>
                                                        @endforeach
                                                        </div>
                                                    </x-installer::form.control-group>
                                                </div>

                                                <!-- Elasticsearch Configuration -->
                                                <div class="grid gap-3 pt-3 mt-1 border-t border-gray-100">
                                                    <p class="text-[15px] font-bold text-gray-800">
                                                        @lang('installer::app.installer.index.environment-configuration.elasticsearch.title')
                                                    </p>

                                                    <p class="text-[13px] text-gray-600 !leading-normal">
                                                        @lang('installer::app.installer.index.environment-configuration.elasticsearch.info')
                                                    </p>

                                                    <!-- Enable Elasticsearch -->
                                                    <x-installer::form.control-group class="mb-2.5">
                                                        <x-installer::form.control-group.label>
                                                            @lang('installer::app.installer.index.environment-configuration.elasticsearch.enable')
                                                        </x-installer::form.control-group.label>

                                                        <x-installer::form.control-group.control
                                                            type="select"
                                                            name="elasticsearch_enabled"
                                                            v-model="elasticsearch.enabled"
                                                            :aria-label="trans('installer::app.installer.index.environment-configuration.elasticsearch.enable')"
                                                            :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.enable')"
                                                        >
                                                            <option value="no">@lang('installer::app.installer.index.environment-configuration.elasticsearch.no')</option>

                                                            <option value="yes">@lang('installer::app.installer.index.environment-configuration.elasticsearch.yes')</option>
                                                        </x-installer::form.control-group.control>

                                                        <x-installer::form.control-group.error control-name="elasticsearch_enabled" />
                                                    </x-installer::form.control-group>

                                                    <template v-if="elasticsearch.enabled === 'yes'">
                                                        <!-- Connection -->
                                                        <x-installer::form.control-group class="mb-2.5">
                                                            <x-installer::form.control-group.label>
                                                                @lang('installer::app.installer.index.environment-configuration.elasticsearch.connection')
                                                            </x-installer::form.control-group.label>

                                                            <x-installer::form.control-group.control
                                                                type="select"
                                                                name="elasticsearch_connection"
                                                                v-model="elasticsearch.connection"
                                                                :aria-label="trans('installer::app.installer.index.environment-configuration.elasticsearch.connection')"
                                                                :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.connection')"
                                                            >
                                                                <option value="default">@lang('installer::app.installer.index.environment-configuration.elasticsearch.connection-default')</option>

                                                                <option value="api">@lang('installer::app.installer.index.environment-configuration.elasticsearch.connection-api')</option>

                                                                <option value="cloud">@lang('installer::app.installer.index.environment-configuration.elasticsearch.connection-cloud')</option>
                                                            </x-installer::form.control-group.control>

                                                            <x-installer::form.control-group.error control-name="elasticsearch_connection" />
                                                        </x-installer::form.control-group>

                                                        <!-- Cloud ID -->
                                                        <x-installer::form.control-group
                                                            class="mb-2.5"
                                                            v-if="elasticsearch.connection === 'cloud'"
                                                        >
                                                            <x-installer::form.control-group.label>
                                                                @lang('installer::app.installer.index.environment-configuration.elasticsearch.cloud-id')
                                                            </x-installer::form.control-group.label>

                                                            <x-installer::form.control-group.control
                                                                type="text"
                                                                name="elasticsearch_cloud_id"
                                                                v-model="elasticsearch.cloud_id"
                                                                :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.cloud-id')"
                                                                :placeholder="trans('installer::app.installer.index.environment-configuration.elasticsearch.cloud-id')"
                                                            />

                                                            <x-installer::form.control-group.error control-name="elasticsearch_cloud_id" />
                                                        </x-installer::form.control-group>

                                                        <template v-else>
                                                            <!-- Host -->
                                                            <x-installer::form.control-group class="mb-2.5">
                                                                <x-installer::form.control-group.label>
                                                                    @lang('installer::app.installer.index.environment-configuration.elasticsearch.host')
                                                                </x-installer::form.control-group.label>

                                                                <x-installer::form.control-group.control
                                                                    type="text"
                                                                    name="elasticsearch_host"
                                                                    v-model="elasticsearch.host"
                                                                    :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.host')"
                                                                    :placeholder="trans('installer::app.installer.index.environment-configuration.elasticsearch.host-placeholder')"
                                                                />

                                                                <x-installer::form.control-group.error control-name="elasticsearch_host" />
                                                            </x-installer::form.control-group>

                                                            <!-- User -->
                                                            <x-installer::form.control-group class="mb-2.5">
                                                                <x-installer::form.control-group.label>
                                                                    @lang('installer::app.installer.index.environment-configuration.elasticsearch.user')
                                                                </x-installer::form.control-group.label>

                                                                <x-installer::form.control-group.control
                                                                    type="text"
                                                                    name="elasticsearch_user"
                                                                    v-model="elasticsearch.user"
                                                                    :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.user')"
                                                                    :placeholder="trans('installer::app.installer.index.environment-configuration.elasticsearch.user')"
                                                                />

                                                                <x-installer::form.control-group.error control-name="elasticsearch_user" />
                                                            </x-installer::form.control-group>

                                                            <!-- Password -->
                                                            <x-installer::form.control-group class="mb-2.5">
                                                                <x-installer::form.control-group.label>
                                                                    @lang('installer::app.installer.index.environment-configuration.elasticsearch.password')
                                                                </x-installer::form.control-group.label>

                                                                <x-installer::form.control-group.control
                                                                    type="password"
                                                                    name="elasticsearch_pass"
                                                                    v-model="elasticsearch.pass"
                                                                    :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.password')"
                                                                    :placeholder="trans('installer::app.installer.index.environment-configuration.elasticsearch.password')"
                                                                />

                                                                <x-installer::form.control-group.error control-name="elasticsearch_pass" />
                                                            </x-installer::form.control-group>

                                                            <!-- API Key -->
                                                            <x-installer::form.control-group
                                                                class="mb-2.5"
                                                                v-if="elasticsearch.connection === 'api'"
                                                            >
                                                                <x-installer::form.control-group.label>
                                                                    @lang('installer::app.installer.index.environment-configuration.elasticsearch.api-key')
                                                                </x-installer::form.control-group.label>

                                                                <x-installer::form.control-group.control
                                                                    type="text"
                                                                    name="elasticsearch_api_key"
                                                                    v-model="elasticsearch.api_key"
                                                                    :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.api-key')"
                                                                    :placeholder="trans('installer::app.installer.index.environment-configuration.elasticsearch.api-key')"
                                                                />

                                                                <x-installer::form.control-group.error control-name="elasticsearch_api_key" />
                                                            </x-installer::form.control-group>
                                                        </template>

                                                        <!-- Index Prefix -->
                                                        <x-installer::form.control-group class="mb-2.5">
                                                            <x-installer::form.control-group.label>
                                                                @lang('installer::app.installer.index.environment-configuration.elasticsearch.index-prefix')
                                                            </x-installer::form.control-group.label>

                                                            <x-installer::form.control-group.control
                                                                type="text"
                                                                name="elasticsearch_index_prefix"
                                                                v-model="elasticsearch.index_prefix"
                                                                :label="trans('installer::app.installer.index.environment-configuration.elasticsearch.index-prefix')"
                                                                :placeholder="trans('installer::app.installer.index.environment-configuration.elasticsearch.index-prefix')"
                                                            />

                                                            <x-installer::form.control-group.error control-name="elasticsearch_index_prefix" />
                                                        </x-installer::form.control-group>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="flex px-6 py-4 justify-end items-center">
                                                <button
                                                    type="submit"
                                                    class="px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                                    tabindex="0"
                                                >
                                                    @lang('installer::app.installer.index.continue')
                                                </button>
                                            </div>
                                        </form>
                                    </x-installer::form>
                                </div>

                                <!-- Create Administrator -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'createAdmin'"
                                >
                                    <x-installer::form
                                        v-slot="{ meta, errors, handleSubmit }"
                                        as="div"
                                        ref="createAdmin"
                                    >
                                        <form
                                            @submit.prevent="handleSubmit($event, FormSubmit)"
                                            enctype="multipart/form-data"
                                        >
                                            <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                                <p class="text-[18px] text-gray-800 font-bold">
                                                    @lang('installer::app.installer.index.create-administrator.title')
                                                </p>
                                            </div>

                                            <div class="flex flex-col gap-3 px-6 py-6 border-b border-gray-200 max-h-[484px] overflow-y-auto">
                                                <!-- Admin -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.create-administrator.admin')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="admin"
                                                        rules="required"
                                                        value="Admin"
                                                        :label="trans('installer::app.installer.index.create-administrator.admin')"
                                                        :placeholder="trans('installer::app.installer.index.create-administrator.unopim')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="admin" />
                                                </x-installer::form.control-group>

                                                <!-- Email -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.create-administrator.email')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="text"
                                                        name="email"
                                                        rules="required"
                                                        value="admin@example.com"
                                                        :label="trans('installer::app.installer.index.create-administrator.email')"
                                                        :placeholder="trans('installer::app.installer.index.create-administrator.email-address')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="email" />
                                                </x-installer::form.control-group>

                                                <!-- Password -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.create-administrator.password')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="password"
                                                        name="password"
                                                        rules="required|min:6"
                                                        :value="old('password')"
                                                        :label="trans('installer::app.installer.index.create-administrator.password')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="password" />
                                                </x-installer::form.control-group>

                                                <!-- Confirm Password -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.create-administrator.confirm-password')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="password"
                                                        name="confirm_password"
                                                        rules="required|confirmed:@password"
                                                        :value="old('confirm_password')"
                                                        :label="trans('installer::app.installer.index.create-administrator.confirm-password')"
                                                    />

                                                    <x-installer::form.control-group.error control-name="confirm_password" />
                                                </x-installer::form.control-group>

                                                <!-- User Default Timezone -->
                                                <x-installer::form.control-group class="mb-2.5">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.default-timezone')
                                                    </x-installer::form.control-group.label>

                                                    @php
                                                        date_default_timezone_set('UTC');

                                                        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

                                                        $current = date_default_timezone_get();
                                                    @endphp

                                                    <x-installer::form.control-group.control
                                                        type="select"
                                                        name="timezone"
                                                        ::value="envData.app_timezone ?? $current ?? 'UTC'"
                                                        rules="required"
                                                        :aria-label="trans('installer::app.installer.index.environment-configuration.default-timezone')"
                                                        :label="trans('installer::app.installer.index.environment-configuration.default-timezone')"
                                                    >
                                                        <option
                                                            value=""
                                                            disabled
                                                        >
                                                            @lang('installer::app.installer.index.environment-configuration.select-timezone')
                                                        </option>

                                                        @foreach($tzlist as $key => $value)
                                                            <option
                                                                value="{{ $value }}"
                                                                {{ $value === $current ? 'selected' : '' }}
                                                            >
                                                                {{ $value }}
                                                            </option>
                                                        @endforeach
                                                    </x-installer::form.control-group.control>

                                                    <x-installer::form.control-group.error control-name="timezone" />
                                                </x-installer::form.control-group>

                                                <!-- User's Default Locale -->
                                                <x-installer::form.control-group class="w-full">
                                                    <x-installer::form.control-group.label class="required">
                                                        @lang('installer::app.installer.index.environment-configuration.default-locale')
                                                    </x-installer::form.control-group.label>

                                                    <x-installer::form.control-group.control
                                                        type="select"
                                                        name="locale"
                                                        value="{{ app()->getLocale() }}"
                                                        rules="required"
                                                        :aria-label="trans('installer::app.installer.index.environment-configuration.default-locale')"
                                                        :label="trans('installer::app.installer.index.environment-configuration.default-locale')"
                                                    >
                                                        @foreach ($locales as $value)
                                                            <option value="{{ $value }}">
                                                                {{ Locale::getDisplayName($value, app()->getLocale()) }}
                                                            </option>
                                                        @endforeach
                                                    </x-installer::form.control-group.control>

                                                    <x-installer::form.control-group.error control-name="locale" />
                                                </x-installer::form.control-group>
                                            </div>

                                            <div class="flex px-6 py-4 justify-between items-center">
                                                <div
                                                    class="inline-flex items-center px-4 py-2 rounded-lg border border-violet-700 text-violet-700 text-[14px] font-semibold cursor-pointer hover:bg-violet-50 transition-all"
                                                    role="button"
                                                    :aria-label="@lang('installer::app.installer.index.back')"
                                                    tabindex="0"
                                                    @click="back"
                                                >
                                                    @lang('installer::app.installer.index.back')
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                                    tabindex="0"
                                                >
                                                    @lang('installer::app.installer.index.continue')
                                                </button>
                                            </div>
                                        </form>
                                    </x-installer::form>
                                </div>

                                <!-- Add-ons + Sample Data -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'addOns'"
                                >
                                    <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                        <p class="text-[18px] text-gray-800 font-bold">
                                            @lang('installer::app.installer.index.add-ons.title')
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-5 px-6 py-6 border-b border-gray-200 max-h-[484px] overflow-y-auto">
                                        <p class="text-[14px] text-gray-600 !leading-normal">
                                            @lang('installer::app.installer.index.add-ons.info')
                                        </p>

                                        <!-- Optional package cards -->
                                        <div class="grid gap-3">
                                            <label
                                                v-for="(pkg, key) in optionalPackages"
                                                :key="key"
                                                class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer select-none transition-all"
                                                :class="selectedPackages.includes(key)
                                                    ? 'border-violet-700 bg-violet-50 ring-1 ring-violet-700'
                                                    : 'border-gray-200 hover:border-violet-300'"
                                            >
                                                <input
                                                    type="checkbox"
                                                    class="mt-1 h-4 w-4 accent-violet-700 cursor-pointer"
                                                    :value="key"
                                                    :checked="selectedPackages.includes(key)"
                                                    @change="togglePackage(key)"
                                                />

                                                <span
                                                    class="flex items-center justify-center w-10 h-10 rounded-lg bg-violet-100 text-violet-700 shrink-0"
                                                    v-html="addOnIcons[key]"
                                                ></span>

                                                <span class="min-w-0">
                                                    <span class="block text-[14px] font-bold text-gray-800">
                                                        @{{ pkg.label }}
                                                    </span>

                                                    <span class="block text-[13px] text-gray-600 !leading-normal mt-0.5">
                                                        @{{ addOnDescriptions[key] }}
                                                    </span>
                                                </span>
                                            </label>
                                        </div>

                                        <!-- Sample data toggle -->
                                        <div class="grid gap-1.5 pt-2 border-t border-gray-100">
                                            <p class="text-[14px] font-bold text-gray-800 mt-3">
                                                @lang('installer::app.installer.index.add-ons.sample-data-title')
                                            </p>

                                            <p class="text-[13px] text-gray-600 !leading-normal">
                                                @lang('installer::app.installer.index.add-ons.sample-data-info')
                                            </p>

                                            <label class="flex items-center gap-2.5 mt-2 cursor-pointer select-none">
                                                <input
                                                    type="checkbox"
                                                    class="h-4 w-4 accent-violet-700 cursor-pointer"
                                                    :checked="seedSampleData"
                                                    @change="seedSampleData = $event.target.checked"
                                                />

                                                <span class="text-[14px] font-semibold text-gray-700">
                                                    @lang('installer::app.installer.index.add-ons.sample-data-label')
                                                </span>
                                            </label>

                                            <p
                                                class="text-[12px] text-gray-600 mt-1"
                                                v-if="seedSampleDataMessage"
                                            >
                                                @{{ seedSampleDataMessage }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex px-6 py-4 justify-between items-center">
                                        <div
                                            class="inline-flex items-center px-4 py-2 rounded-lg border border-violet-700 text-violet-700 text-[14px] font-semibold cursor-pointer hover:bg-violet-50 transition-all"
                                            role="button"
                                            aria-label="@lang('installer::app.installer.index.back')"
                                            tabindex="0"
                                            @click="back"
                                        >
                                            @lang('installer::app.installer.index.back')
                                        </div>

                                        <button
                                            type="button"
                                            class="px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                            tabindex="0"
                                            @click="nextForm"
                                        >
                                            @lang('installer::app.installer.index.continue')
                                        </button>
                                    </div>
                                </div>

                                <!-- Ready For Installation -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'readyForInstallation'"
                                >
                                    <x-installer::form
                                        v-slot="{ meta, errors, handleSubmit }"
                                        as="div"
                                        ref="readyForInstallation"
                                    >
                                        <form
                                            @submit.prevent="handleSubmit($event, FormSubmit)"
                                            enctype="multipart/form-data"
                                        >
                                            <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                                <p class="text-[18px] text-gray-800 font-bold">
                                                    @lang('installer::app.installer.index.ready-for-installation.install')
                                                </p>
                                            </div>

                                            <div class="flex flex-col gap-[15px] px-6 py-6 border-b border-gray-200">
                                                <p class="text-[16px] text-gray-800 font-semibold">
                                                    @lang('installer::app.installer.index.ready-for-installation.install-info')
                                                </p>

                                                <div class="grid gap-2.5">
                                                    <label class="text-[14px] text-gray-600">
                                                        @lang('installer::app.installer.index.ready-for-installation.install-info-button')
                                                    </label>

                                                    <div class="grid gap-3">
                                                        <div class="flex gap-1.5 items-center text-[14px] text-gray-600">
                                                            <span class="icon-right text-[20px] text-violet-700"></span>

                                                            <p>@lang('installer::app.installer.index.ready-for-installation.create-databsae-table')</p>
                                                        </div>

                                                        <div class="flex gap-1.5 items-center text-[14px] text-gray-600">
                                                            <span class="icon-right text-[20px] text-violet-700"></span>

                                                            <p>@lang('installer::app.installer.index.ready-for-installation.populate-database-table')</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex px-6 py-4 justify-between items-center">
                                                <div
                                                    class="inline-flex items-center px-4 py-2 rounded-lg border border-violet-700 text-violet-700 text-[14px] font-semibold cursor-pointer hover:bg-violet-50 transition-all"
                                                    role="button"
                                                    :aria-label="@lang('installer::app.installer.index.back')"
                                                    tabindex="0"
                                                    @click="back"
                                                >
                                                    @lang('installer::app.installer.index.back')
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                                >
                                                    @lang('installer::app.installer.index.ready-for-installation.start-installation')
                                                </button>
                                            </div>
                                        </form>
                                    </x-installer::form>
                                </div>

                                <!-- Installation Completed -->
                                <div
                                    class="w-full bg-white rounded-xl shadow-[0px_8px_24px_0px_rgba(0,0,0,0.06)] border border-gray-200"
                                    v-if="currentStep == 'installationCompleted'"
                                >
                                    <div class="flex justify-between items-center gap-2.5 px-6 py-4 border-b border-gray-200">
                                        <p class="text-[18px] text-gray-800 font-bold">
                                            @lang('installer::app.installer.index.installation-completed.title')
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-5 px-6 py-6 border-b border-gray-200 max-h-[520px] overflow-y-auto">
                                        <div class="flex flex-col gap-4">
                                            <div class="flex items-center justify-center rounded-full bg-green-100 w-12 h-12">
                                                <span class="icon-tick text-[24px] text-green-600 font-semibold"></span>
                                            </div>

                                            <div class="grid gap-2.5">
                                                <p class="text-[18px] text-gray-800 font-bold">
                                                    @lang('installer::app.installer.index.installation-completed.title')
                                                </p>

                                                <p class="text-[14px] text-gray-600">
                                                    @lang('installer::app.installer.index.installation-completed.title-info')
                                                </p>

                                                <div class="flex gap-4 items-center">
                                                    <a
                                                        href="{{ URL('/admin/login')}}"
                                                        class="px-4 py-2 bg-violet-700 border border-violet-700 rounded-lg text-white text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                                    >
                                                        @lang('installer::app.installer.index.installation-completed.admin-panel')
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Selected add-ons (installed during setup) -->
                                        <div
                                            class="grid gap-2 pt-4 border-t border-gray-100"
                                            v-if="selectedPackages.length"
                                        >
                                            <p class="text-[15px] font-bold text-gray-800">
                                                @lang('installer::app.installer.index.installation-completed.add-ons-title')
                                            </p>

                                            <p class="text-[13px] text-gray-600 !leading-normal">
                                                @lang('installer::app.installer.index.installation-completed.add-ons-info')
                                            </p>

                                            <ul class="grid gap-1 mt-1">
                                                <li
                                                    v-for="key in selectedPackages"
                                                    :key="key"
                                                    class="flex items-center gap-2 text-[13px] font-semibold text-gray-700"
                                                >
                                                    <span class="icon-tick text-[18px] text-green-600"></span>

                                                    @{{ optionalPackages[key].label }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="flex px-6 py-4 justify-between items-center">
                                        <a
                                            href="https://unopim.com/extensions"
                                            class="px-4 py-2 bg-white border border-violet-700 rounded-lg text-violet-700 text-[14px] font-semibold cursor-pointer hover:opacity-90"
                                        >
                                            @lang('installer::app.installer.index.installation-completed.explore-unopim-extensions')
                                        </a>
                                    </div>
                                </div>
                            </main>
                        </div>
                    </div>

                    <!-- Centered footer (like the admin login page) -->
                    <footer class="w-full px-6 py-6 text-center text-[13px] text-gray-600 font-medium flex flex-col items-center">
                        <div>
                            @lang('installer::app.installer.index.powered-by', [
                                'unopim' => '<a class="text-violet-700 hover:underline" href="https://unopim.com/" target="_blank" rel="noopener">Unopim</a>',
                            ])
                        </div>

                        <div>
                            @lang('installer::app.installer.index.open-source-project-by', [
                                'webkul' => '<a class="text-violet-700 hover:underline" href="https://webkul.com/" target="_blank" rel="noopener">Webkul</a>',
                            ])
                        </div>
                    </footer>
                </div>
            </script>

            <script type="module">
                app.component('v-server-requirements', {
                    template: '#v-server-requirements-template',

                    props: {
                        optionalPackages: {
                            type: Object,
                            default: () => ({}),
                        },

                        cloudHostingUrl: {
                            type: String,
                            default: '',
                        },
                    },

                    data() {
                        return {
                            step: '',

                            currentStep: 'start',

                            envData: {},

                            envConfigData: {},

                            defaultAppUrl: window.location.origin,

                            installStage: '',

                            elasticsearch: {
                                enabled: 'no',
                                connection: 'default',
                                host: '127.0.0.1:9200',
                                user: '',
                                pass: '',
                                api_key: '',
                                cloud_id: '',
                                index_prefix: '',
                            },

                            seedSampleData: false,

                            seedSampleDataMessage: '',

                            terminalLines: [],

                            installing: false,

                            adminParams: {},

                            selectedPackages: [],

                            addOnIcons: @json($addOnIcons),

                            addOnDescriptions: {
                                dam: "@lang('installer::app.installer.index.add-ons.packages.dam.description')",
                                shopify: "@lang('installer::app.installer.index.add-ons.packages.shopify.description')",
                                bagisto: "@lang('installer::app.installer.index.add-ons.packages.bagisto.description')",
                            },

                            locales: {
                                allowed: [],
                            },

                            currencies: {
                                allowed: [],
                            },

                            stepStates: {
                                start: 'active',
                                systemRequirements: 'pending',
                                envDatabase: 'pending',
                                envConfiguration: 'pending',
                                createAdmin: 'pending',
                                addOns: 'pending',
                                readyForInstallation: 'pending',
                                installationCompleted: 'pending',
                            },

                            steps: [
                                'start',
                                'systemRequirements',
                                'envDatabase',
                                'envConfiguration',
                                'createAdmin',
                                'addOns',
                                'readyForInstallation',
                                'installProgress',
                                'installationCompleted',
                            ],
                        }
                    },

                    mounted() {
                        const preventUnload = (event) => {
                            event.preventDefault();
                        };

                        window.addEventListener('beforeunload', preventUnload);
                    },

                    methods: {
                        togglePackage(key) {
                            const index = this.selectedPackages.indexOf(key);

                            if (index === -1) {
                                this.selectedPackages.push(key);
                            } else {
                                this.selectedPackages.splice(index, 1);
                            }
                        },

                        FormSubmit(params, { setErrors }) {
                            const stepActions = {
                                // Collect DB credentials, then advance to environment config (no migration yet).
                                envDatabase: (setErrors) => {
                                    if (params.db_connection === 'mysql' || params.db_connection === 'pgsql') {
                                        this.envData = { ...this.envData, ...params };

                                        this.completeStep('envDatabase', 'envConfiguration', 'active', 'complete', setErrors);
                                    } else {
                                        setErrors({ 'db_connection': ["UnoPim currently supports MySQL only."] });
                                    }
                                },

                                // Collect admin credentials, then advance to add-ons (admin is created later).
                                createAdmin: (setErrors) => {
                                    this.adminParams = params;

                                    this.completeStep('createAdmin', 'addOns', 'active', 'complete', setErrors);
                                },

                                // Final step: run the whole install in order.
                                readyForInstallation: (setErrors) => {
                                    this.currentStep = 'installProgress';

                                    this.runInstall(setErrors);
                                },
                            };

                            const index = this.steps.find(step => step === this.currentStep);

                            if (stepActions[index]) {
                                stepActions[index](setErrors);
                            }
                        },

                        nextForm(params) {
                            const stepActions = {
                                start: () => {
                                    this.completeStep('start', 'systemRequirements', 'active', 'complete');
                                },

                                systemRequirements: () => {
                                    this.completeStep('systemRequirements', 'envDatabase', 'active', 'complete');

                                    this.currentStep = 'envDatabase';
                                },

                                // Collect environment config (app + locales/currencies + ES), then advance.
                                envConfiguration: () => {
                                    this.envConfigData = { ...params };

                                    this.completeStep('envConfiguration', 'createAdmin', 'active', 'complete');
                                },

                                // Add-on selection captured via v-model; advance to the install step.
                                addOns: () => {
                                    this.completeStep('addOns', 'readyForInstallation', 'active', 'complete');
                                },
                            };

                            const index = this.steps.find(step => step === this.currentStep);

                            if (stepActions[index]) {
                                stepActions[index]();
                            }
                        },

                        pushAllowedCurrency() {
                            const currencyName = event.target.name;

                            const index = this.currencies.allowed.indexOf(currencyName);

                            if (index === -1) {
                                this.currencies.allowed.push(currencyName);
                            } else {
                                this.currencies.allowed.splice(index, 1);
                            }
                        },

                        pushAllowedLocales() {
                            const localeName = event.target.name;

                            if (! Array.isArray(this.locales.allowed)) {
                            this.locales.allowed = [];
                            }

                            const index = this.locales.allowed.indexOf(localeName);

                            if (index === -1) {
                                this.locales.allowed.push(localeName);
                            } else {
                                this.locales.allowed.splice(index, 1);
                            }
                        },

                        completeStep(fromStep, toStep, toState, nextState, setErrors) {
                            this.stepStates[fromStep] = nextState;

                            this.currentStep = toStep;

                            this.stepStates[toStep] = toState;
                        },

                        pushTerminalLine(line) {
                            this.terminalLines.push(line);

                            this.$nextTick(() => {
                                const el = this.$refs.terminal;

                                if (el) {
                                    el.scrollTop = el.scrollHeight;
                                }
                            });
                        },

                        // Runs the whole installation server-side and streams its output
                        // to the read-only terminal:
                        //   1) write .env (DB credentials),
                        //   2) POST /install/api/prepare to write app/locale/ES env and
                        //      stash admin/sample/packages in the session,
                        //   3) open an EventSource that performs migrate -> seed -> admin
                        //      -> optional sample data -> optional add-on packages, live.
                        // The admin password is sent only over the POST prepare request; the
                        // GET EventSource reads it back from the session — never a query string.
                        runInstall(setErrors) {
                            this.currentStep = 'installProgress';

                            this.installing = true;

                            this.terminalLines = [];

                            const preparePayload = {
                                ...this.envConfigData,
                                allowed_locales: this.locales.allowed,
                                allowed_currencies: this.currencies.allowed,
                                admin: this.adminParams,
                                sample: this.seedSampleData,
                                packages: this.selectedPackages,
                            };

                            this.installStage = 'environment';

                            this.$axios.post("{{ route('installer.env_file_setup', [], false) }}", this.envData)
                                .then(() => {
                                    this.installStage = 'prepare';

                                    return this.$axios.post("{{ route('installer.prepare', [], false) }}", preparePayload);
                                })
                                .then(() => {
                                    this.startStream();
                                })
                                .catch(error => {
                                    const data = (error.response && error.response.data) || {};
                                    const status = error.response && error.response.status;

                                    console.error('[installer] stage=' + this.installStage, status, error);

                                    if (status == 419) {
                                        window.location.reload();

                                        return;
                                    }

                                    const detail = data.error || data.message || (typeof data === 'string' ? data : '') || (error && error.message) || ('HTTP ' + (status || 'error'));

                                    this.installing = false;

                                    alert('Installation failed at the ' + this.installStage + ' step: ' + detail);

                                    // Return to the install step so the user can retry.
                                    this.currentStep = 'readyForInstallation';

                                    if (data.errors && setErrors) {
                                        setErrors(data.errors);
                                    }
                                });
                        },

                        startStream() {
                            const source = new EventSource("{{ route('installer.process', [], false) }}");

                            source.onmessage = (event) => {
                                try {
                                    const payload = JSON.parse(event.data);

                                    if (payload && typeof payload.line === 'string') {
                                        this.pushTerminalLine(payload.line);
                                    }
                                } catch (e) {}
                            };

                            source.addEventListener('done', (event) => {
                                source.close();

                                this.installing = false;

                                this.completeStep('readyForInstallation', 'installationCompleted', 'active', 'complete');
                            });

                            source.addEventListener('error', (event) => {
                                let message = "@lang('installer::app.installer.index.create-administrator.seed-sample-data-failed')";

                                if (event && event.data) {
                                    try {
                                        const payload = JSON.parse(event.data);

                                        if (payload && payload.message) {
                                            message = payload.message;
                                        }
                                    } catch (e) {}
                                }

                                this.pushTerminalLine('✗ ' + message);

                                source.close();

                                this.installing = false;
                            });
                        },

                        runSampleDataSeeder() {
                            this.seedSampleDataMessage = "@lang('installer::app.installer.index.create-administrator.seeding-sample-data')";

                            this.$axios.post("{{ route('installer.seed_sample_data', [], false) }}")
                                .then(() => {
                                    this.seedSampleDataMessage = '';
                                    this.currentStep = 'installationCompleted';
                                })
                                .catch(error => {
                                    this.seedSampleDataMessage = (error.response && error.response.data && error.response.data.error)
                                        ? error.response.data.error
                                        : "@lang('installer::app.installer.index.create-administrator.seed-sample-data-failed')";
                                    this.currentStep = 'installationCompleted';
                                });
                        },

                        setLocale(params) {
                            const newLocale = params.locale;
                            const url = new URL(window.location.href);

                            if (! url.searchParams.has('locale')) {
                                url.searchParams.set('locale', newLocale);
                                window.location.href = url.toString();
                            }
                        },

                        back() {
                            if (this.$refs[this.currentStep] && this.$refs[this.currentStep].setValues) {
                                this.$refs[this.currentStep].setValues(this.envData);
                            }

                            let index = this.steps.indexOf(this.currentStep);

                            if (index > 0) {
                                this.currentStep = this.steps[index - 1];
                            }
                        }
                    },
                });
            </script>
        @endPushOnce

        @stack('scripts')
    </body>
</html>
