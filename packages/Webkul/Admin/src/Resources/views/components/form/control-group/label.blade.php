<label {{ $attributes->merge(['class' => 'flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium']) }}>
    {{ $slot }}

@if(('true' == $attributes->get('localizable') || 1 == $attributes->get('localizable')))
    <span class="px-1 py-0.5 bg-gray-100 border border-gray-200 rounded text-[10px] text-gray-600 font-semibold leading-normal uppercase">
        {{ $attributes->get('currentLocaleCode') ?? core()->getRequestedLocaleCode() }}
    </span>
@endif
</label>
