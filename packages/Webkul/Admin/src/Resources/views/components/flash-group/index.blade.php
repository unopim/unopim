<v-flash-group ref='flashes'></v-flash-group>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-flash-group-template"
    >
        <transition-group
            tag='div'
            name="flash-group"
            move-class="transition-transform duration-200"
            enter-from-class="ltr:translate-x-[120%] rtl:-translate-x-[120%] opacity-0"
            enter-active-class="transform transition ease-out duration-300"
            enter-to-class="translate-x-0 opacity-100"
            leave-from-class="translate-x-0 opacity-100"
            leave-active-class="transform transition ease-in duration-200 absolute"
            leave-to-class="ltr:translate-x-[120%] rtl:-translate-x-[120%] opacity-0"
            class='flex flex-col gap-2.5 fixed top-20 ltr:right-5 rtl:left-5 z-[10060] ltr:items-end rtl:items-start'
        >
            <x-admin::flash-group.item />
        </transition-group>
    </script>

    <script type="module">
        app.component('v-flash-group', {
            template: '#v-flash-group-template',

            data() {
                return {
                    uid: 0,

                    flashes: []
                }
            },

            created() {
                @foreach (['success', 'warning', 'error', 'info'] as $key)
                    @if (session()->has($key))
                        this.flashes.push({'type': '{{ $key }}', 'message': @json(session($key)), 'uid':  this.uid++});
                    @endif
                @endforeach

                this.registerGlobalEvents();
            },

            methods: {
                add(flash) {
                    flash.uid = this.uid++;

                    this.flashes.push(flash);
                },

                remove(flash) {
                    let index = this.flashes.indexOf(flash);

                    this.flashes.splice(index, 1);
                },

                registerGlobalEvents() {
                    this.$emitter.on('add-flash', this.add);
                },
            }
        });
    </script>
@endpushOnce
