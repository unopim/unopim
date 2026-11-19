@php
    $customCss = app(\Webkul\Admin\Helpers\CustomCss::class)->get();
@endphp

@if ($customCss !== '')
<style id="unopim-custom-css">
{!! $customCss !!}
</style>
@endif
