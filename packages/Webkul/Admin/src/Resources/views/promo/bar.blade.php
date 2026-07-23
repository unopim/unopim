@php($banners = app(\Webkul\Admin\Helpers\PromoBanner::class)->visibleBanners())

{{-- Reserve the bar's height (h-12 = 48px) on first paint so the Vue island
     filling in after mount doesn't shift the whole layout down (flash on load). --}}
@if (! empty($banners))
    <div class="shrink-0 min-h-[48px]">
        <x-admin::promo-bar :banners="$banners" />
    </div>
@endif
