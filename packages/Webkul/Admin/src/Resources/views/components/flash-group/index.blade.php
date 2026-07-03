<v-flash-group ref='flashes'></v-flash-group>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-flash-group-template"
    >
        <transition-group
            tag='div'
            name="flash-group"
            enter-from-class="translate-y-full opacity-0"
            enter-active-class="transform transition ease-in-out duration-200"
            enter-to-class="translate-y-0 opacity-100"
            leave-from-class="translate-y-0 opacity-100"
            leave-active-class="transform transition ease-in-out duration-200"
            leave-to-class="translate-y-full opacity-0"
            class='grid gap-2.5 fixed bottom-5 left-1/2 -translate-x-1/2 z-[10060] justify-items-center'
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
                        this.flashes.push({'type': '{{ $key }}', 'message': "{{ session($key) }}", 'uid':  this.uid++});
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
