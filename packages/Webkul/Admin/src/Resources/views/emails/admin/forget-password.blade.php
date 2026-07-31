@component('admin::emails.layout')
    <div style="margin-bottom: 34px;">
        <p style="font-family: 'Inter', Arial, sans-serif;font-weight: 600;font-size: 20px;color: #1F2937;line-height: 24px;margin-bottom: 24px">
            @lang('admin::app.emails.dear', ['admin_name' => $userName]), 👋
        </p>

        <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;">
            @lang('admin::app.emails.admin.forgot-password.greeting')
        </p>
    </div>

    <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;margin-bottom: 40px">
        @lang('admin::app.emails.admin.forgot-password.description')
    </p>

    <div style="margin-bottom: 60px;">
        <a
            href="{{ route('admin.reset_password.create', $token) }}"
            style="display: inline-block;font-family: 'Inter', Arial, sans-serif;padding: 12px 32px;border-radius: 6px;background: #7C3AED;border: 1px solid #6D28D9;color: #F9FAFB;text-decoration: none;font-weight: 600;font-size: 14px;line-height: 20px;"
        >
            @lang('admin::app.emails.admin.forgot-password.reset-password')
        </a>
    </div>
@endcomponent
