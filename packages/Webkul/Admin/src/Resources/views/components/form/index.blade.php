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
        'method' => 'POST',
    ])

    @php
        $method = strtoupper($method);
    @endphp

    <v-form
        method="{{ $method === 'GET' ? 'GET' : 'POST' }}"
        :initial-errors="{{ json_encode($errors->getMessages()) }}"
        v-slot="{ meta, errors, setValues }"
        @invalid-submit="onInvalidSubmit"
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

    {{--
        When the server returns validation errors (e.g. a duplicate SKU that can only be
        checked server side), scroll to and focus the first invalid field on load, mirroring
        the client side `onInvalidSubmit` behaviour.
    --}}
    @if ($errors->any())
        @push('scripts')
            <script type="text/javascript">
                window.addEventListener('load', () => {
                    const element = document.querySelector('[name="' + @json($errors->keys()[0]) + '"]');

                    if (! element) {
                        return;
                    }

                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    setTimeout(() => element.focus(), 500);
                });
            </script>
        @endpush
    @endif
@endif