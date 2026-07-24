@props([
    'id'       => '',
    'title'    => '',
    'subtitle' => '',
    'icon'     => '',
    'order'    => 0,
])

<div
    v-show="$productWorkspace?.isActive('{{ $id }}')"
    class="product-workspace-panel"
    data-section-id="{{ $id }}"
    v-cloak
>
    <div class="max-w-5xl mx-auto">
        {{ $slot }}
    </div>
</div>

@pushOnce('scripts', 'product-workspace-panel-' . $id)
    <script type="module">
        (function register() {
            const store = app.config.globalProperties.$productWorkspace;
            if (! store) { window.requestAnimationFrame(register); return; }
            store.register({
                id: @json($id),
                title: @json($title),
                subtitle: @json($subtitle),
                icon: @json($icon),
                order: {{ (int) $order }},
            });
        })();
    </script>
@endPushOnce
