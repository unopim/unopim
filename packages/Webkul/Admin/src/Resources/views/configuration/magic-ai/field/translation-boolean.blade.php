@inject('coreConfigRepository', 'Webkul\Core\Repositories\CoreConfigRepository')
@inject('magicAI', 'Webkul\MagicAI\MagicAI')

@php
$nameKey = $item['key'] . '.' . $field['name'];
$name = $coreConfigRepository->getNameField($nameKey);
$value = core()->getConfigData($nameKey);
@endphp

<v-translation-boolean
    label="@lang($field['title'])"
    name="{{ $name }}"
    :value='@json($value)'>
</v-translation-boolean>

@pushOnce('scripts')
<script type="text/x-template" id="v-translation-boolean-template">
    <div class="grid gap-2.5 content-start">
        <div>

            <x-admin::form.control-group class="mb-4" >
                <x-admin::form.control-group.label>
                    @{{ label }}
                </x-admin::form.control-group.label>

                <input
                    type="hidden"
                    :name="name"
                    :value="0"
                />

                <label class="relative inline-flex cursor-pointer items-center">
                    <input
                        type="checkbox"
                        :name="name"
                        :value="1"
                        :id="name"
                        class="peer sr-only"
                        :checked="parseInt(localValue || 0)"
                        :disabled="isDisabled"
                    >

                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-700"></div>
                </label>
            </x-admin::form.control-group>

        </div>
    </div>

</script>
<script type="module">
    app.component('v-translation-boolean', {
        template: '#v-translation-boolean-template',
        props: [
            'label',
            'name',
            'validations',
            'value',
        ],
        data: function() {
            return {
                localValue: this.value,
                isDisabled: false,
            }
        },

        mounted() {
            this.$emitter.on('config-value-changed', (data) => {

                if (data.fieldName == 'general[magic_ai][settings][enabled]') {
                    const isEnabled = parseInt(data.value || 0) === 1;
                    this.localValue = isEnabled ? 1 : 0;
                    this.isDisabled = !isEnabled;
                }
            })
        },
    });
</script>
@endPushOnce
