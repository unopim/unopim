@component('admin::emails.layout')
    <div>
        <p>@lang('completeness::app.notifications.email-greeting')</p>

        @if (! empty($templateData['familyId']))
            @php
                $family = \Illuminate\Support\Facades\DB::table('attribute_families')->find($templateData['familyId']);
            @endphp

            <p>
                @lang('completeness::app.notifications.email-body-family', [
                    'count'  => $templateData['totalProducts'],
                    'family' => $family->code ?? $templateData['familyId'],
                ])
            </p>
        @else
            <p>
                @lang('completeness::app.notifications.email-body', [
                    'count' => $templateData['totalProducts'],
                ])
            </p>
        @endif

        <p>@lang('completeness::app.notifications.email-footer')</p>
    </div>
@endcomponent
