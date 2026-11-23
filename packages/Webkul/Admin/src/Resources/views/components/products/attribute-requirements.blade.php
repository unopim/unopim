@props(['requirements' => [], 'attributeId' => null])

@foreach ($requirements as $requirement)
    <x-admin::badge
        :variant="$requirement['variant']"
        :title="$requirement['title']"
        :data-requirement="$requirement['key']"
        :data-attribute-id="$attributeId"
    >
        {{ $requirement['label'] }}
    </x-admin::badge>
@endforeach
