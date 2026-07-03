<!--
    If a component has the `as` attribute, it indicates that it uses
    the ajaxified form or some customized slot form.
-->
@if ($attributes->has('as'))
    <v-form {{ $attributes }}>
        {{ $slot }}
    </v-form>

<!--
    Otherwise, a traditional form will be provided with a minimal
    set of configurations.
-->
@else
    @props([
        'method'              => 'POST',
        'trackDirty'          => true,
        'hideSaveWhenTracked' => true,
        'ajax'                => false,
    ])

    @php
        $method = strtoupper($method);

        $shouldTrack = $trackDirty && ! in_array($method, ['GET', 'HEAD', 'OPTIONS']);

        $isAjax = filter_var($ajax, FILTER_VALIDATE_BOOLEAN);
    @endphp

    @if ($shouldTrack)
        @include('admin::components.form.unsaved-changes')

        <v-unsaved-changes :hide-save-when-tracked="{{ $hideSaveWhenTracked ? 'true' : 'false' }}">
    @endif

    <v-form
        method="{{ $method === 'GET' ? 'GET' : 'POST' }}"
        :initial-errors="{{ json_encode($errors->getMessages()) }}"
        v-slot="{ meta, errors, setValues }"
        @invalid-submit="onInvalidSubmit"
        @if ($isAjax)
            @submit="onAjaxSubmit"
            data-ajax-form="true"
            data-ajax-error-message="{{ trans('admin::app.components.form.ajax-error') }}"
        @endif
        {{ $attributes }}
    >
        @unless(in_array($method, ['HEAD', 'GET', 'OPTIONS']))
            @csrf
        @endunless

        @if (! in_array($method, ['GET', 'POST']))
            @method($method)
        @endif

        {{ $slot }}
    </v-form>

    @if ($shouldTrack)
        </v-unsaved-changes>
    @endif
@endif