<div data-control-group {{ $attributes->merge(['class' => 'mb-4']) }}>
    {{ $slot }}
</div>

@php(pull_form_label_id())
