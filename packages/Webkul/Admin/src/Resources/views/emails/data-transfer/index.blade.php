@component('admin::emails.layout')
    <div>
        @php
            $metaData = json_decode($templateData->meta);
        @endphp
        <p>Hello,</p>

        <p>{{ ucfirst($metaData->type).' "'.$metaData->code.'" '.$templateData->state }}</p>
    </div>
@endcomponent
