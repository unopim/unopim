@props([
    'values'         => [],
    'exporterConfig' => '',
    'entityType'     => 'categories',
    'only'           => '',
    'except'         => '',
    'gridClass'      => '',
])

@php
    ['key' => $setsKey, 'sets' => $entitySets, 'types' => $allTypes] =
        app(\Webkul\Admin\Fields\FieldConfig::class)->payload($exporterConfig);

    $entityTypeIsBound = $attributes->has(':entity-type');
@endphp

<x-admin::form.fields.load :types="$allTypes" />

@pushOnce('scripts', 'field-sets-'.$setsKey)
    <script type="module">
        window.unopim = window.unopim || {};
        window.unopim.fieldSets = window.unopim.fieldSets || {};
        window.unopim.fieldSets['{{ $setsKey }}'] = @json($entitySets);
    </script>
@endPushOnce

<v-filter-fields
    @unless ($entityTypeIsBound)
        entity-type="{{ $entityType }}"
    @endunless
    sets-key="{{ $setsKey }}"
    :saved-values='@json((object) $values)'
    :old-values='@json((object) old())'
    only="{{ $only }}"
    except="{{ $except }}"
    grid-class="{{ $gridClass }}"
    {{ $attributes }}
></v-filter-fields>

@pushOnce('scripts')
    <script type="text/x-template" id="v-filter-fields-template">
        <v-field-set
            :fields="fields"
            :initial-values="initialValues"
            name-prefix="filters"
            :grid-class="gridClass"
            :only="only"
            :except="except"
        />
    </script>

    <script type="module">
        app.component('v-filter-fields', {
            template: '#v-filter-fields-template',

            inheritAttrs: false,

            props: {
                entityType:  { type: [String, Object], default: '' },
                entitySets:  { type: Object, default: () => ({}) },
                setsKey:     { type: String, default: '' },
                savedValues: { type: Object, default: () => ({}) },
                oldValues:   { type: Object, default: () => ({}) },
                only:        { type: String, default: '' },
                except:      { type: String, default: '' },
                gridClass:   { type: String, default: '' },
            },

            data() {
                return {
                    entity: this.resolveEntity(this.entityType),
                };
            },

            mounted() {
                this.$emitter.on('entity-type-changed', this.changeEntityType);
            },

            beforeUnmount() {
                this.$emitter.off('entity-type-changed', this.changeEntityType);
            },

            computed: {
                sets() {
                    if (Object.keys(this.entitySets).length) {
                        return this.entitySets;
                    }

                    return (window.unopim?.fieldSets ?? {})[this.setsKey] ?? {};
                },

                fields() {
                    return this.sets[this.entity] ?? [];
                },

                initialValues() {
                    return { ...this.savedValues, ...(this.oldValues?.filters ?? {}) };
                },
            },

            methods: {
                resolveEntity(value) {
                    if (value && typeof value === 'object') {
                        return value.id ?? '';
                    }

                    try {
                        const parsed = JSON.parse(value);

                        return (parsed && typeof parsed === 'object' && parsed.id) ? parsed.id : value;
                    } catch (exception) {
                        return value;
                    }
                },

                changeEntityType(value) {
                    this.entity = this.resolveEntity(value);
                },
            },
        });
    </script>
@endPushOnce
