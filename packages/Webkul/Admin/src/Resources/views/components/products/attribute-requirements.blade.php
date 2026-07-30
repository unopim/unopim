@props(['requirements' => []])

@foreach ($requirements as $requirement)
    <x-admin::badge
        :variant="$requirement['variant']"
        :title="$requirement['title']"
        :data-requirement="$requirement['key']"
    >
        {{ $requirement['label'] }}
    </x-admin::badge>
@endforeach
