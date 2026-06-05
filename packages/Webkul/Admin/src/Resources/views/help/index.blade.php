<x-admin::layouts>
    <x-slot:title>
        {{ trans('admin::app.help.index.title') }}
    </x-slot>

    <div class="flex flex-col gap-1.5 mb-8">
        <p class="text-2xl font-bold text-gray-800 dark:text-white">
            {{ trans('admin::app.help.index.title') }}
        </p>

        <p class="max-w-2xl text-gray-500 dark:text-gray-300 leading-relaxed">
            {{ trans('admin::app.help.index.subtitle') }}
        </p>
    </div>

    @foreach (config('help.sections') as $section)
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-8 mb-3.5">
            {{ trans($section['title']) }}
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($section['items'] as $item)
                <x-admin::card
                    :icon="$item['icon']"
                    :title="trans($item['title'])"
                    :url="$item['url']"
                    :host="$item['host'] ?? ''"
                    :external="$item['external'] ?? false"
                >
                    {{ trans($item['description']) }}
                </x-admin::card>
            @endforeach
        </div>
    @endforeach

    @php($cta = config('help.cta'))

    <div class="flex flex-wrap items-center gap-4 mt-8 p-6 rounded-xl text-white bg-gradient-to-r from-violet-700 to-violet-500">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-white/20 shrink-0">
            {!! $cta['icon'] !!}
        </span>

        <div class="flex-1 min-w-0">
            <p class="text-base font-bold m-0">
                {{ trans($cta['title']) }}
            </p>

            <p class="m-0 mt-0.5 text-sm text-white/85">
                {{ trans($cta['sub']) }}
            </p>
        </div>

        <a
            href="{{ $cta['url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            class="shrink-0 inline-flex items-center gap-2 h-10 px-5 rounded-lg bg-white text-violet-700 text-sm font-bold no-underline transition-all hover:shadow-lg"
        >
            {{ trans($cta['label']) }}

            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14 M13 6l6 6-6 6"></path>
            </svg>
        </a>
    </div>
</x-admin::layouts>
