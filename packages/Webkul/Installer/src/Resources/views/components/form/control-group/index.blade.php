<div {{ $attributes->merge(['class' => 'mb-2.5']) }}>
    {{ $slot }}
</div>

@php(pull_form_label_id())
