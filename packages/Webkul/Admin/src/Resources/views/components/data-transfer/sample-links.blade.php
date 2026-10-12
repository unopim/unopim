@props([
    'configFile' => 'importers',
    'routeName'  => 'admin.settings.data_transfer.imports.download_sample',
    'zipRoute'   => 'admin.settings.data_transfer.imports.download_sample_zip',
    'selection'  => '',
])

@php
    $sampleService = app(\Webkul\DataTransfer\Services\SampleFiles::class);

    $samples = [];

    foreach (array_keys(config($configFile, [])) as $entityType) {
        $links = [];

        foreach ($sampleService->all($configFile, $entityType) as $key => $sample) {
            if (! $sampleService->path($configFile, $entityType, $key)) {
                continue;
            }

            $links[] = [
                'label' => $key === \Webkul\DataTransfer\Services\SampleFiles::DEFAULT_KEY
                    ? trans('admin::app.settings.data-transfer.imports.create.download-sample', ['resource' => Str::headline($entityType)])
                    : trans($sample['label']),
                'url'   => route($routeName, [$entityType, $key]),
            ];
        }

        if ($sampleService->path($configFile, $entityType, images: true)) {
            $links[] = [
                'label' => trans('data_transfer::app.samples.with-images'),
                'url'   => route($zipRoute, [$entityType]),
            ];
        }

        if ($links !== []) {
            $samples[$entityType] = $links;
        }
    }
@endphp

@foreach ($samples as $entityType => $links)
    <template v-if="{!! $selection !!} && {!! $selection !!}.selectedOption === '{{ $entityType }}'">
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
            @foreach ($links as $link)
                <a
                    href="{{ $link['url'] }}"
                    target="_blank"
                    class="text-sm text-primary-700 dark:text-sky-500 cursor-pointer transition-all hover:underline"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </template>
@endforeach
