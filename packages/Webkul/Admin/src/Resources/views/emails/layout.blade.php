@php
    $locale = app()->getLocale();
    $direction = str_starts_with($locale, 'ar') || str_starts_with($locale, 'he') || str_starts_with($locale, 'fa') ? 'rtl' : 'ltr';

    $adminEmail = core()->getAdminEmailDetails();
    $supportEmail = $adminEmail['email'] ?: core()->getSenderEmailDetails()['email'];

    $logo = core()->getConfigData('general.design.admin_logo.logo_image');
    $logoUrl = $logo ? url(Storage::url($logo)) : unopim_asset('images/logo.svg');
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="color-scheme" content="light" />
        <meta name="supported-color-schemes" content="light" />
        <meta name="x-apple-disable-message-reformatting" />

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head>

    <body dir="{{ $direction }}" style="margin: 0; padding: 0; background-color: #F1F2F9; font-family: 'Inter', Arial, sans-serif;">
        @isset($preheader)
            <div style="display: none; max-height: 0; overflow: hidden; mso-hide: all; font-size: 1px; line-height: 1px; color: #F1F2F9;">
                {{ $preheader }}
            </div>
        @endisset

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F1F2F9;">
            <tr>
                <td align="center" style="padding: 30px 15px;">
                    <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width: 640px; width: 100%; background-color: #FFFFFF; border-radius: 8px; overflow: hidden;">
                        <!-- Email Header -->
                        <tr>
                            <td align="center" style="padding: 32px 30px; background-color: #FFFFFF; border-bottom: 1px solid #E5E7EB;">
                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ config('app.name') }}"
                                    height="40"
                                    style="height: 40px; max-height: 40px; width: auto;"
                                />
                            </td>
                        </tr>

                        <!-- Email Content -->
                        <tr>
                            <td style="padding: 40px 40px 0 40px;">
                                {{ $slot }}
                            </td>
                        </tr>

                        <!-- Email Footer -->
                        <tr>
                            <td style="padding: 0 40px 40px 40px;">
                                <p style="font-size: 16px; color: #202B3C; line-height: 24px; margin: 0 0 24px 0;">
                                    @lang('admin::app.emails.thanks', [
                                        'link'  => 'mailto:' . $supportEmail,
                                        'email' => $supportEmail,
                                        'style' => 'color: #2969FF; text-decoration: none;',
                                    ])
                                </p>

                                <hr style="border: none; border-top: 1px solid #E5E7EB; margin: 0 0 20px 0;" />

                                <p style="font-size: 13px; color: #6B7280; line-height: 20px; margin: 0;">
                                    @lang('admin::app.footer.copy-right')
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
