<x-admin::layouts>
    <x-slot:title>
        {{ trans('admin::app.help.index.title') }}
    </x-slot>

    <div class="max-w-[1240px]">
        <x-admin::heading
            :title="trans('admin::app.help.index.title')"
            :subtitle="trans('admin::app.help.index.subtitle')"
            as="h1"
            size="xl"
            class="mb-5"
        />

        @foreach (config('help.sections') as $section)
            <p class="{{ $loop->first ? 'mt-0' : 'mt-[34px]' }} mb-[14px] text-[12px] font-bold uppercase tracking-[0.08em] text-gray-500">
                {{ trans($section['title']) }}
            </p>

            <div class="grid grid-cols-1 gap-[18px] md:grid-cols-2 xl:grid-cols-3">
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

        <div class="flex flex-wrap items-center gap-[18px] mt-8 px-[26px] py-[22px] rounded-2xl text-white bg-gradient-to-r from-primary-700 to-primary-500">
            <span class="flex items-center justify-center w-[46px] h-[46px] rounded-xl bg-white/[0.16] shrink-0">
                {!! $cta['icon'] !!}
            </span>

            <div class="flex-1 min-w-0">
                <p class="m-0 text-[16px] font-bold">
                    {{ trans($cta['title']) }}
                </p>

                <p class="m-0 mt-[3px] text-[13.5px] !leading-[1.45] text-white/85">
                    {{ trans($cta['sub']) }}
                </p>
            </div>

            <a
                href="{{ $cta['url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="shrink-0 inline-flex items-center gap-2 h-[42px] px-5 rounded-[10px] bg-white text-primary-700 text-[14px] font-bold no-underline transition-all hover:-translate-y-px hover:shadow-lg"
            >
                {{ trans($cta['label']) }}

                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14 M13 6l6 6-6 6"></path>
                </svg>
            </a>
        </div>
    </div>
</x-admin::layouts>
