@props([
    'title',
    'description' => null,
    'searching'   => null,
])

<div class="mb-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-gray-600 dark:text-gray-300 font-semibold leading-6">
                {{ $title }}
            </p>

            @if ($description)
                <p class="text-xs text-gray-800 dark:text-white font-medium">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if ($searching)
            <button
                type="button"
                class="icon-search flex cursor-pointer items-center text-2xl"
                v-if="! {{ $searching }}"
                @click="{{ $searching }} = true"
            >
            </button>
        @endif
    </div>

    @if ($searching)
        <div
            class="mt-2"
            v-if="{{ $searching }}"
        >
            {{ $slot }}
        </div>
    @endif
</div>
