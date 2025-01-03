<?php

namespace Webkul\Installer\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class DefaultUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:user:create
        {--name= : The name of the user}
        {--email= : The email address of the user}
        {--password= : The password for the user}
        {--ui_locale= : The UI locale (e.g., en_US) of the user}
        {--timezone= : The timezone of the user}
        {--admin : Specify if the user is an admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command allows you to create a new user with a name, email, password, UI locale, timezone, and optionally specify whether the user is an admin.';

    /**
     * Locales list.
     *
     * @var array
     */
    protected $locales = [
        'ar_AE' => 'Arabic (United Arab Emirates)',
        'ca_ES' => 'Catalan (Spain)',
        'da_DK' => 'Danish (Denmark)',
        'de_DE' => 'German (Germany)',
        'en_AU' => 'English (Australia)',
        'en_GB' => 'English (United Kingdom)',
        'en_NZ' => 'English (New Zealand)',
        'en_US' => 'English (United States)',
        'es_ES' => 'Spanish (Spain)',
        'es_VE' => 'Spanish (Venezuela)',
        'fi_FI' => 'Finnish (Finland)',
        'fr_FR' => 'French (France)',
        'hi_IN' => 'Hindi (India)',
        'hr_HR' => 'Croatian (Croatia)',
        'it_IT' => 'Italian (Italy)',
        'ja_JP' => 'Japanese (Japan)',
        'ko_KR' => 'Korean (South Korea)',
        'nl_NL' => 'Dutch (Netherlands)',
        'no_NO' => 'Norwegian (Norway)',
        'pl_PL' => 'Polish (Poland)',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_PT' => 'Portuguese (Portugal)',
        'ro_RO' => 'Romanian (Romania)',
        'ru_RU' => 'Russian (Russia)',
        'sv_SE' => 'Swedish (Sweden)',
        'tl_PH' => 'Tagalog (Philippines)',
        'tr_TR' => 'Turkish (Turkey)',
        'uk_UA' => 'Ukrainian (Ukraine)',
        'vi_VN' => 'Vietnamese (Vietnam)',
        'zh_CN' => 'Chinese (China)',
        'zh_TW' => 'Chinese (Taiwan)',
    ];

    /**
     * Local codes.
     */
    protected $localCodes = [
        'af_ZA',
        'am_ET',
        'ar_AE',
        'ar_BH',
        'ar_DZ',
        'ar_EG',
        'ar_IQ',
        'ar_JO',
        'ar_KW',
        'ar_LB',
        'ar_LY',
        'ar_MA',
        'arn_CL',
        'ar_OM',
        'ar_QA',
        'ar_SA',
        'ar_SY',
        'ar_TN',
        'ar_YE',
        'as_IN',
        'az_Cyrl_AZ',
        'az_Latn_AZ',
        'ba_RU',
        'be_BY',
        'bg_BG',
        'bn_BD',
        'bn_IN',
        'bo_CN',
        'br_FR',
        'bs_Cyrl_BA',
        'bs_Latn_BA',
        'ca_ES',
        'co_FR',
        'cs_CZ',
        'cy_GB',
        'da_DK',
        'de_AT',
        'de_CH',
        'de_DE',
        'de_LI',
        'de_LU',
        'dsb_DE',
        'dv_MV',
        'el_GR',
        'en_029',
        'en_AU',
        'en_BZ',
        'en_CA',
        'en_GB',
        'en_IE',
        'en_IN',
        'en_JM',
        'en_MY',
        'en_NZ',
        'en_PH',
        'en_SG',
        'en_TT',
        'en_US',
        'en_ZA',
        'en_ZW',
        'es_AR',
        'es_BO',
        'es_CL',
        'es_CO',
        'es_CR',
        'es_DO',
        'es_EC',
        'es_ES',
        'es_GT',
        'es_HN',
        'es_MX',
        'es_NI',
        'es_PA',
        'es_PE',
        'es_PR',
        'es_PY',
        'es_SV',
        'es_US',
        'es_UY',
        'es_VE',
        'et_EE',
        'eu_ES',
        'fa_IR',
        'fi_FI',
        'fil_PH',
        'fo_FO',
        'fr_BE',
        'fr_CA',
        'fr_CH',
        'fr_FR',
        'fr_LU',
        'fr_MC',
        'fy_NL',
        'ga_IE',
        'gd_GB',
        'gl_ES',
        'gsw_FR',
        'gu_IN',
        'ha_Latn_NG',
        'he_IL',
        'hi_IN',
        'hr_BA',
        'hr_HR',
        'hsb_DE',
        'hu_HU',
        'hy_AM',
        'id_ID',
        'ig_NG',
        'ii_CN',
        'is_IS',
        'it_CH',
        'it_IT',
        'iu_Cans_CA',
        'iu_Latn_CA',
        'ja_JP',
        'ka_GE',
        'kk_KZ',
        'kl_GL',
        'km_KH',
        'kn_IN',
        'kok_IN',
        'ko_KR',
        'ky_KG',
        'lb_LU',
        'lo_LA',
        'lt_LT',
        'lv_LV',
        'mi_NZ',
        'mk_MK',
        'ml_IN',
        'mn_MN',
        'mn_Mong_CN',
        'moh_CA',
        'mr_IN',
        'ms_BN',
        'ms_MY',
        'mt_MT',
        'nb_NO',
        'ne_NP',
        'nl_BE',
        'nl_NL',
        'nn_NO',
        'no_NO',
        'nso_ZA',
        'oc_FR',
        'or_IN',
        'pa_IN',
        'pl_PL',
        'prs_AF',
        'ps_AF',
        'pt_BR',
        'pt_PT',
        'qut_GT',
        'quz_BO',
        'quz_EC',
        'quz_PE',
        'rm_CH',
        'ro_RO',
        'ru_RU',
        'rw_RW',
        'sah_RU',
        'sa_IN',
        'se_FI',
        'se_NO',
        'se_SE',
        'si_LK',
        'sk_SK',
        'sl_SI',
        'sma_NO',
        'sma_SE',
        'smj_NO',
        'smj_SE',
        'smn_FI',
        'sms_FI',
        'sq_AL',
        'sr_Cyrl_BA',
        'sr_Cyrl_CS',
        'sr_Cyrl_ME',
        'sr_Cyrl_RS',
        'sr_Latn_BA',
        'sr_Latn_CS',
        'sr_Latn_ME',
        'sr_Latn_RS',
        'sv_FI',
        'sv_SE',
        'sw_KE',
        'syr_SY',
        'ta_IN',
        'te_IN',
        'tg_Cyrl_TJ',
        'th_TH',
        'tk_TM',
        'tl_PH',
        'tn_ZA',
        'tr_TR',
        'tt_RU',
        'tzm_Latn_DZ',
        'ug_CN',
        'uk_UA',
        'ur_PK',
        'uz_Cyrl_UZ',
        'uz_Latn_UZ',
        'vi_VN',
        'wo_SN',
        'xh_ZA',
        'yo_NG',
        'zh_CN',
        'zh_HK',
        'zh_MO',
        'zh_SG',
        'zh_TW',
        'zu_ZA',
    ];

    /**
     * Create UnoPim user.
     */
    public function handle()
    {
        Log::info('User create command has started');

        $isAdmin = $this->option('admin');
        $userName = $this->getUserName($isAdmin);
        $userEmail = $this->getUserEmail($isAdmin);
        $userPassword = $this->getUserPassword($isAdmin);
        $timezone = $this->getSelectedTimeZone();
        $defaultLocale = $this->getSelectedUiLocale();
        $password = password_hash($userPassword, PASSWORD_BCRYPT, ['cost' => 10]);

        $localeId = DB::table('locales')->where('code', $defaultLocale)->where('status', 1)->first()?->id ?? 58;
        $role = $isAdmin ? DB::table('roles')->where('permission_type', 'all')->first()?->id : DB::table('roles')->where('permission_type', 'custom')->first()?->id;

        if (! $role) {
            DB::table('roles')->updateOrInsert(
                [
                    'name'            => $isAdmin ? 'Admin' : 'User',
                    'description'     => $isAdmin ? 'This role users will have all the access' : 'This role users have limited access',
                    'permission_type' => $isAdmin ? 'all' : 'custom',
                    'permissions'     => ! $isAdmin ? json_encode(['dashboard']) : null,
                ]
            );
        }

        $role = $isAdmin ? DB::table('roles')->where('permission_type', 'all')->first()?->id : DB::table('roles')->where('permission_type', 'custom')->first()?->id;

        try {
            DB::table('admins')->updateOrInsert(
                [
                    'api_token'    => Str::random(80),
                    'created_at'   => date('Y-m-d H:i:s'),
                    'name'         => $userName,
                    'email'        => $userEmail,
                    'password'     => $password,
                    'role_id'      => $role,
                    'status'       => 1,
                    'timezone'     => $timezone,
                    'ui_locale_id' => $localeId,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]
            );

            $this->info('-----------------------------');
            $this->info('Congratulations! The User has been created successfully.');
            $this->info('Please navigate to: '.env('APP_URL').'/admin'.' and use the following credentials for authentication:');
            $this->info('Email: '.$userEmail);
            $this->info('Password: '.$userPassword);
            $this->info('Cheers!');
            Log::info('Congratulations! The User has been created successfully');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            Log::error($e->getMessage());
        }
    }

    /**
     * Method for asking default choice based on the list of options.
     */
    protected function askForDefaultValues(string $key, string $question, array $choices): string
    {
        $choice = select(
            label: $question,
            options: $choices,
            default: env($key)
        );

        return $choice;
    }

    /**
     * Get All timezones list with offset in name
     */
    public function getTimeZones(): array
    {
        $timezones = \DateTimeZone::listIdentifiers();

        $formattedTimezones = [];

        foreach ($timezones as $index => $timezone) {
            $now = Carbon::now($timezone);

            $offset = $now->offset / 60;

            $formattedName = sprintf('%s (%+03d:%02d)', $timezone, $offset / 60, abs($offset % 60));

            $formattedTimezones[$timezone] = $formattedName;
        }

        return $formattedTimezones;
    }

    /**
     * Generates and logs warnings based on the provided message.
     *
     * @param  string  $message  The warning message to be logged and displayed.
     */
    protected function generateWarnings(string $message): void
    {
        $this->warn($message);
        Log::warning($message);
    }

    /**
     * Retrieves the username, validating input to ensure it meets criteria.
     *
     * @param  bool  $isAdmin  Indicates whether the user is an admin.
     */
    protected function getUserName(bool $isAdmin): string
    {
        $userName = $this->option('name');

        if ($userName && ! preg_match('/^[a-zA-Z0-9\s]+$/', $userName)) {
            $this->generateWarnings('The name can only accept alphanumeric characters and spaces.');
            $userName = null;
        }

        $userName = $userName ?: text(
            label: 'Set the Name for User',
            default: $isAdmin ? 'Admin' : 'User',
            required: true,
            validate: fn (string $value) => match (true) {
                ! preg_match('/^[a-zA-Z0-9\s]+$/', $value) => 'The name can only accept alphanumeric characters and spaces.',
                default                                    => null
            }
        );

        return $userName;
    }

    /**
     * Retrieves the user email, ensuring it is valid and not already in use.
     *
     * @param  bool  $isAdmin  Indicates whether the user is an admin.
     */
    protected function getUserEmail(bool $isAdmin): string
    {
        $userEmail = $this->option('email');
        $existingUserEmails = DB::table('admins')->pluck('email')->toArray();

        if ($userEmail && ! filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $this->generateWarnings('The provided email is invalid, kindly enter a valid email address.');
        }

        if ($userEmail && in_array($userEmail, $existingUserEmails)) {
            $this->generateWarnings('User with email '.$userEmail.' already exists.');
            $userEmail = null;
        }

        if (! $userEmail || ! filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $userEmail = text(
                label: 'Provide Email of User',
                default: $isAdmin ? 'admin@example.com' : 'user@example.com',
                validate: fn (string $value) => match (true) {
                    ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The provided email is invalid, kindly enter a valid email address.',
                    in_array($value, $existingUserEmails)       => 'User with email '.$value.' already exists.',
                    default                                     => null
                }
            );
        }

        return $userEmail;
    }

    /**
     * Prompts for and validates the user's password.
     *
     * @param  bool  $isAdmin  Indicates whether the user is an admin.
     */
    protected function getUserPassword(bool $isAdmin): string
    {
        $userPassword = $this->option('password') ?: text(
            label: 'Input a Secure Password for User',
            default: $isAdmin ? 'admin@123' : 'user@123',
            required: true
        );

        while (strlen($userPassword) < 6) {
            $this->generateWarnings('Password must be at least 6 characters.');

            $userPassword = text(
                label: 'Input a Secure Password for User',
                default: $isAdmin ? 'admin@123' : 'user@123',
                required: true
            );
        }

        return $userPassword;
    }

    /**
     * Retrieves the selected timezone, validating it against a list of available timezones.
     */
    protected function getSelectedTimeZone(): string
    {
        $timezone = $this->option('timezone');

        if ($timezone && ! array_key_exists($timezone, $this->getTimeZones())) {
            $this->generateWarnings("The specified timezone '$timezone' is not valid. Please select a valid timezone.");
            $timezone = null;
        }

        $timezone = $timezone ?: $this->askForDefaultValues(
            'APP_LOCALE',
            'Please select the default timezone',
            $this->getTimeZones()
        );

        return $timezone;
    }

    /**
     * Retrieves the selected UI locale, validating it against a list of available locales.
     */
    protected function getSelectedUiLocale(): string
    {
        $uiLocale = $this->option('ui_locale');

        if ($uiLocale && ! in_array($uiLocale, $this->localCodes)) {
            $this->generateWarnings("The specified locale code '$uiLocale' is not valid. Please select a valid locale.");
            $uiLocale = null;
        }

        $defaultLocale = $uiLocale ?: $this->askForDefaultValues(
            'APP_LOCALE',
            'Please select the default application locale',
            $this->locales
        );

        return $defaultLocale;
    }
}
