@component('admin::emails.layout')
    <p style="font-family: 'Inter', Arial, sans-serif;font-weight: 600;font-size: 20px;color: #1F2937;line-height: 24px;margin: 0 0 24px 0;">
        @lang('completeness::app.notifications.email-greeting')
    </p>

    @if (! empty($templateData['familyId']))
        @php
            $family = \Illuminate\Support\Facades\DB::table('attribute_families')->find($templateData['familyId']);
        @endphp

        <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;margin: 0 0 24px 0;">
            @lang('completeness::app.notifications.email-body-family', [
                'count'  => $templateData['totalProducts'],
                'family' => $family->code ?? $templateData['familyId'],
            ])
        </p>
    @else
        <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;margin: 0 0 24px 0;">
            @lang('completeness::app.notifications.email-body', [
                'count' => $templateData['totalProducts'],
            ])
        </p>
    @endif

    <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;margin: 0 0 40px 0;">
        @lang('completeness::app.notifications.email-footer')
    </p>
@endcomponent
