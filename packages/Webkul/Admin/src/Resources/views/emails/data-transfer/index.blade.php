@component('admin::emails.layout')
    @php
        $metaData = json_decode($templateData->meta) ?? (object) ['type' => '', 'code' => ''];
    @endphp

    <p style="font-family: 'Inter', Arial, sans-serif;font-weight: 600;font-size: 20px;color: #1F2937;line-height: 24px;margin: 0 0 24px 0;">
        @lang('admin::app.emails.hello')
    </p>

    <p style="font-family: 'Inter', Arial, sans-serif;font-size: 16px;color: #4B5563;line-height: 24px;margin: 0 0 40px 0;">
        @lang('admin::app.emails.data-transfer.body', [
            'type'  => ucfirst($metaData->type ?? ''),
            'code'  => $metaData->code ?? '',
            'state' => $templateData->state,
        ])
    </p>
@endcomponent
