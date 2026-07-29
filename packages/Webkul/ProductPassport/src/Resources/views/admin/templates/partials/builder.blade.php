@php
    use Webkul\ProductPassport\Enums\PassportFieldRole;
    use Webkul\ProductPassport\Enums\PassportFieldSource;
    use Webkul\ProductPassport\Enums\PassportFieldTier;

    $currentLocaleCode = core()->getRequestedLocaleCode();

    $localesForJs = collect($locales)->map(fn ($locale): array => [
        'code' => $locale->code,
        'name' => $locale->name ?: $locale->code,
    ])->values();

    $sourceOptions = collect(PassportFieldSource::cases())->map(fn (PassportFieldSource $case): array => [
        'id'    => $case->value,
        'label' => $case->label(),
    ])->values();

    $tierOptions = collect(PassportFieldTier::cases())->map(fn (PassportFieldTier $case): array => [
        'id'    => $case->value,
        'label' => $case->label(),
    ])->values();

    $roleOptions = collect(PassportFieldRole::cases())->map(fn (PassportFieldRole $case): array => [
        'id'    => $case->value,
        'label' => $case->label(),
    ])->values();

    $sectionsForJs = $template->sections->map(fn ($section): array => [
        'code'    => $section->code,
        'locales' => collect($locales)->mapWithKeys(fn ($locale): array => [
            $locale->code => $section->translate($locale->code)->name ?? '',
        ])->all(),
    ])->values();

    $fieldsForJs = $template->fields->map(fn ($field): array => [
        'code'            => $field->code,
        'section'         => $field->section->code ?? '',
        'source_type'     => $field->source_type->value,
        'attribute_id'    => $field->attribute_id ? (string) $field->attribute_id : '',
        'attribute_label' => $field->attribute
            ? ($field->attribute->getTranslatedValueWithFallback('name') ?: $field->attribute->code)
            : '',
        'tier'        => $field->tier->value,
        'is_required' => (bool) $field->is_required,
        'role'        => $field->role?->value ?? '',
        'locales'     => collect($locales)->mapWithKeys(fn ($locale): array => [
            $locale->code => [
                'label'       => $field->translate($locale->code)->label ?? '',
                'fixed_value' => $field->translate($locale->code)->fixed_value ?? '',
            ],
        ])->all(),
    ])->values();

    $familiesForJs = $template->families->map(fn ($family): array => [
        'id'    => (string) $family->id,
        'label' => $family->getTranslatedValueWithFallback('name') ?: $family->code,
    ])->values();
@endphp

<v-passport-template-builder
    :locales='@json($localesForJs)'
    :source-options='@json($sourceOptions)'
    :tier-options='@json($tierOptions)'
    :role-options='@json($roleOptions)'
    :initial-sections='@json($sectionsForJs)'
    :initial-fields='@json($fieldsForJs)'
    :initial-families='@json($familiesForJs)'
    current-locale="{{ $currentLocaleCode }}"
></v-passport-template-builder>

@pushOnce('scripts')
    <script type="text/x-template" id="v-passport-template-builder-template">
        <div class="flex flex-col gap-2">
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    @lang('passport::app.templates.builder.families-heading')
                </p>

                <p class="mt-1 mb-4 text-xs text-gray-500 dark:text-gray-300">
                    @lang('passport::app.templates.builder.families-info')
                </p>

                <v-multiselect
                    :options="familyOptions"
                    track-by="id"
                    label="label"
                    :multiple="true"
                    :searchable="true"
                    :internal-search="false"
                    :loading="loadingFamilies"
                    :close-on-select="false"
                    :hide-selected="true"
                    :placeholder="lang.selectFamilies"
                    v-model="families"
                    @search-change="searchFamilies"
                >
                </v-multiselect>

                <input
                    v-for="family in families"
                    :key="'family-' + family.id"
                    type="hidden"
                    name="families[]"
                    :value="family.id"
                />
            </div>

            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <div class="flex justify-between items-center gap-2.5">
                    <div>
                        <p class="text-base text-gray-800 dark:text-white font-semibold">
                            @lang('passport::app.templates.builder.sections-heading')
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            @lang('passport::app.templates.builder.sections-info')
                        </p>
                    </div>

                    <button type="button" class="secondary-button text-sm shrink-0" @click="addSection">
                        @lang('passport::app.templates.builder.add-section')
                    </button>
                </div>

                <p v-if="! sections.length" class="mt-4 text-sm text-gray-400 dark:text-gray-300 italic">
                    @lang('passport::app.templates.builder.sections-empty')
                </p>

                <draggable
                    v-else
                    class="mt-4 grid gap-2.5"
                    ghost-class="draggable-ghost"
                    handle=".icon-drag"
                    v-bind="{animation: 200}"
                    :list="sections"
                    item-key="uid"
                >
                    <template #item="{ element, index }">
                        <div class="flex items-center gap-2.5">
                            <i class="icon-drag text-2xl text-gray-500 cursor-grab"></i>

                            <input
                                type="text"
                                v-model="element.locales[currentLocale].name"
                                :placeholder="lang.sectionName"
                                :aria-label="lang.sectionName"
                                class="flex-1 min-w-0 py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                @input="syncSectionCode(element)"
                            />

                            <input
                                type="text"
                                v-model="element.code"
                                :readonly="! element.isNew"
                                :placeholder="lang.code"
                                :aria-label="lang.code"
                                class="w-[200px] py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                :class="{'cursor-not-allowed bg-gray-100 dark:bg-cherry-800': ! element.isNew}"
                            />

                            <button
                                type="button"
                                class="icon-delete shrink-0 text-2xl text-gray-400 hover:text-red-500 cursor-pointer"
                                :title="lang.remove"
                                :aria-label="lang.remove"
                                @click="sections.splice(index, 1)"
                            ></button>

                            <input type="hidden" :name="'sections[' + index + '][code]'" :value="element.code" />

                            <template v-for="locale in locales" :key="'section-' + element.uid + '-' + locale.code">
                                <input
                                    type="hidden"
                                    :name="'sections[' + index + '][' + locale.code + '][name]'"
                                    :value="element.locales[locale.code].name"
                                />
                            </template>
                        </div>
                    </template>
                </draggable>
            </div>

            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <div class="flex justify-between items-center gap-2.5">
                    <div>
                        <p class="text-base text-gray-800 dark:text-white font-semibold">
                            @lang('passport::app.templates.builder.fields-heading')
                        </p>

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            @lang('passport::app.templates.builder.fields-info')
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <span class="text-sm text-gray-600 dark:text-gray-300" v-text="readiness"></span>

                        <button type="button" class="secondary-button text-sm" @click="addField">
                            @lang('passport::app.templates.builder.add-field')
                        </button>
                    </div>
                </div>

                <p v-if="! fields.length" class="mt-4 text-sm text-gray-400 dark:text-gray-300 italic">
                    @lang('passport::app.templates.builder.fields-empty')
                </p>

                <draggable
                    v-else
                    class="mt-4 grid gap-4"
                    ghost-class="draggable-ghost"
                    handle=".icon-drag"
                    v-bind="{animation: 200}"
                    :list="fields"
                    item-key="uid"
                >
                    <template #item="{ element, index }">
                        <div class="p-3 border rounded-md dark:border-gray-700">
                            <div class="flex items-start gap-2.5">
                                <i class="icon-drag mt-2.5 text-2xl text-gray-500 cursor-grab"></i>

                                <div class="grid grid-cols-3 max-md:grid-cols-1 gap-2.5 flex-1 min-w-0">
                                    <div>
                                        <label class="block mb-1 text-xs text-gray-500 dark:text-gray-300" v-text="lang.fieldLabel"></label>

                                        <input
                                            type="text"
                                            v-model="element.locales[currentLocale].label"
                                            :placeholder="lang.fieldLabel"
                                            :aria-label="lang.fieldLabel"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                            @input="syncFieldCode(element)"
                                        />
                                    </div>

                                    <div>
                                        <label class="block mb-1 text-xs text-gray-500 dark:text-gray-300" v-text="lang.code"></label>

                                        <input
                                            type="text"
                                            v-model="element.code"
                                            :readonly="! element.isNew"
                                            :placeholder="lang.code"
                                            :aria-label="lang.code"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                            :class="{'cursor-not-allowed bg-gray-100 dark:bg-cherry-800': ! element.isNew}"
                                        />
                                    </div>

                                    <div>
                                        <label class="block mb-1 text-xs text-gray-500 dark:text-gray-300" v-text="lang.section"></label>

                                        <select
                                            v-model="element.section"
                                            :aria-label="lang.section"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                        >
                                            <option value="" v-text="lang.noSection"></option>
                                            <option
                                                v-for="section in sections"
                                                :key="'opt-section-' + section.uid"
                                                :value="section.code"
                                                v-text="section.locales[currentLocale].name || section.code"
                                            ></option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block mb-1 text-xs text-gray-500 dark:text-gray-300" v-text="lang.source"></label>

                                        <select
                                            v-model="element.source_type"
                                            :aria-label="lang.source"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                        >
                                            <option
                                                v-for="option in sourceOptions"
                                                :key="'opt-source-' + option.id"
                                                :value="option.id"
                                                v-text="option.label"
                                            ></option>
                                        </select>
                                    </div>

                                    <div class="col-span-2 max-md:col-span-1">
                                        <label class="block mb-1 text-xs text-gray-500 dark:text-gray-300" v-text="element.source_type === 'fixed' ? lang.fixedValue : lang.attribute"></label>

                                        <input
                                            v-if="element.source_type === 'fixed'"
                                            type="text"
                                            v-model="element.locales[currentLocale].fixed_value"
                                            :placeholder="lang.fixedValue"
                                            :aria-label="lang.fixedValue"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                        />

                                        <v-multiselect
                                            v-else
                                            :options="attributeOptions"
                                            track-by="id"
                                            label="label"
                                            :searchable="true"
                                            :internal-search="false"
                                            :loading="loadingAttributes"
                                            :close-on-select="true"
                                            :placeholder="familyIds.length ? lang.selectAttribute : lang.selectFamiliesFirst"
                                            :disabled="! familyIds.length"
                                            :model-value="selectedAttribute(element)"
                                            @update:model-value="option => setAttribute(element, option)"
                                            @search-change="searchAttributes"
                                        >
                                        </v-multiselect>
                                    </div>

                                    <div>
                                        <label class="block mb-1 text-xs text-gray-500 dark:text-gray-300" v-text="lang.tier"></label>

                                        <select
                                            v-model="element.tier"
                                            :aria-label="lang.tier"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                        >
                                            <option
                                                v-for="option in tierOptions"
                                                :key="'opt-tier-' + option.id"
                                                :value="option.id"
                                                v-text="option.label"
                                            ></option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block mb-1 text-xs text-gray-500 dark:text-gray-300" v-text="lang.role"></label>

                                        <select
                                            v-model="element.role"
                                            :aria-label="lang.role"
                                            class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 dark:bg-cherry-900 dark:border-gray-600"
                                        >
                                            <option value="" v-text="lang.noRole"></option>
                                            <option
                                                v-for="option in roleOptions"
                                                :key="'opt-role-' + option.id"
                                                :value="option.id"
                                                v-text="option.label"
                                            ></option>
                                        </select>
                                    </div>

                                    <label class="flex items-center gap-2 self-end pb-2.5 text-sm text-gray-600 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" v-model="element.is_required" class="cursor-pointer" />
                                        <span v-text="lang.required"></span>
                                    </label>
                                </div>

                                <button
                                    type="button"
                                    class="icon-delete mt-2 shrink-0 text-2xl text-gray-400 hover:text-red-500 cursor-pointer"
                                    :title="lang.remove"
                                    :aria-label="lang.remove"
                                    @click="fields.splice(index, 1)"
                                ></button>
                            </div>

                            <input type="hidden" :name="'fields[' + index + '][code]'" :value="element.code" />
                            <input type="hidden" :name="'fields[' + index + '][section]'" :value="element.section" />
                            <input type="hidden" :name="'fields[' + index + '][source_type]'" :value="element.source_type" />
                            <input type="hidden" :name="'fields[' + index + '][attribute_id]'" :value="element.source_type === 'fixed' ? '' : element.attribute_id" />
                            <input type="hidden" :name="'fields[' + index + '][tier]'" :value="element.tier" />
                            <input type="hidden" :name="'fields[' + index + '][role]'" :value="element.role" />
                            <input type="hidden" :name="'fields[' + index + '][is_required]'" :value="element.is_required ? 1 : 0" />

                            <template v-for="locale in locales" :key="'field-' + element.uid + '-' + locale.code">
                                <input
                                    type="hidden"
                                    :name="'fields[' + index + '][' + locale.code + '][label]'"
                                    :value="element.locales[locale.code].label"
                                />

                                <input
                                    type="hidden"
                                    :name="'fields[' + index + '][' + locale.code + '][fixed_value]'"
                                    :value="element.locales[locale.code].fixed_value"
                                />
                            </template>
                        </div>
                    </template>
                </draggable>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-passport-template-builder', {
            template: '#v-passport-template-builder-template',

            props: {
                locales: { type: Array, default: () => [] },
                sourceOptions: { type: Array, default: () => [] },
                tierOptions: { type: Array, default: () => [] },
                roleOptions: { type: Array, default: () => [] },
                initialSections: { type: Array, default: () => [] },
                initialFields: { type: Array, default: () => [] },
                initialFamilies: { type: Array, default: () => [] },
                currentLocale: { type: String, required: true },
            },

            data() {
                return {
                    sections: [],
                    fields: [],
                    families: [],
                    familyOptions: [],
                    attributeOptions: [],
                    loadingFamilies: false,
                    loadingAttributes: false,
                    familyTimer: null,
                    attributeTimer: null,
                    sequence: 0,
                    lang: {
                        code: @json(trans('passport::app.templates.builder.code')),
                        remove: @json(trans('passport::app.templates.builder.remove')),
                        sectionName: @json(trans('passport::app.templates.builder.section-name')),
                        fieldLabel: @json(trans('passport::app.templates.builder.field-label')),
                        section: @json(trans('passport::app.templates.builder.section')),
                        noSection: @json(trans('passport::app.templates.builder.no-section')),
                        source: @json(trans('passport::app.templates.builder.source')),
                        attribute: @json(trans('passport::app.templates.builder.attribute')),
                        fixedValue: @json(trans('passport::app.templates.builder.fixed-value')),
                        tier: @json(trans('passport::app.templates.builder.tier')),
                        role: @json(trans('passport::app.templates.builder.role')),
                        noRole: @json(trans('passport::app.templates.builder.no-role')),
                        required: @json(trans('passport::app.templates.builder.required')),
                        selectFamilies: @json(trans('passport::app.templates.builder.select-families')),
                        selectFamiliesFirst: @json(trans('passport::app.templates.builder.select-families-first')),
                        selectAttribute: @json(trans('passport::app.templates.builder.select-attribute')),
                    },
                };
            },

            computed: {
                familyIds() {
                    return this.families.map((family) => family.id);
                },

                readiness() {
                    const required = this.fields.filter((field) => field.is_required);

                    const sourced = required.filter((field) => field.source_type === 'fixed' || field.attribute_id);

                    return @json(trans('passport::app.templates.builder.readiness'))
                        .replace(':sourced', sourced.length)
                        .replace(':required', required.length);
                },
            },

            mounted() {
                this.families = this.initialFamilies.map((family) => ({ ...family }));

                this.sections = this.initialSections.map((section) => ({
                    uid: `section-${this.sequence++}`,
                    isNew: false,
                    code: section.code,
                    locales: this.localeMap(section.locales, { name: '' }),
                }));

                this.fields = this.initialFields.map((field) => ({
                    uid: `field-${this.sequence++}`,
                    isNew: false,
                    code: field.code,
                    section: field.section,
                    source_type: field.source_type,
                    attribute_id: field.attribute_id,
                    attribute_label: field.attribute_label,
                    tier: field.tier,
                    role: field.role,
                    is_required: field.is_required,
                    locales: this.localeMap(field.locales, { label: '', fixed_value: '' }),
                }));

                this.fetchFamilies('');

                this.fetchAttributes('');
            },

            methods: {
                localeMap(saved, shape) {
                    return this.locales.reduce((carry, locale) => {
                        carry[locale.code] = { ...shape, ...(saved?.[locale.code] ?? {}) };

                        return carry;
                    }, {});
                },

                addSection() {
                    this.sections.push({
                        uid: `section-${this.sequence++}`,
                        isNew: true,
                        code: '',
                        locales: this.localeMap({}, { name: '' }),
                    });
                },

                addField() {
                    this.fields.push({
                        uid: `field-${this.sequence++}`,
                        isNew: true,
                        code: '',
                        section: '',
                        source_type: 'attribute',
                        attribute_id: '',
                        attribute_label: '',
                        tier: 'consumer',
                        role: '',
                        is_required: false,
                        locales: this.localeMap({}, { label: '', fixed_value: '' }),
                    });
                },

                /** A saved row keeps its code: the code identifies the field in published payloads. */
                syncSectionCode(section) {
                    if (section.isNew) {
                        section.code = this.slugify(section.locales[this.currentLocale].name);
                    }
                },

                syncFieldCode(field) {
                    if (field.isNew) {
                        field.code = this.slugify(field.locales[this.currentLocale].label);
                    }
                },

                slugify(value) {
                    return (value ?? '')
                        .toString()
                        .trim()
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                },

                selectedAttribute(field) {
                    return field.attribute_id
                        ? { id: field.attribute_id, label: field.attribute_label || field.attribute_id }
                        : null;
                },

                setAttribute(field, option) {
                    field.attribute_id = option?.id ?? '';
                    field.attribute_label = option?.label ?? '';
                },

                searchFamilies(query) {
                    clearTimeout(this.familyTimer);

                    this.familyTimer = setTimeout(() => this.fetchFamilies(query), 500);
                },

                searchAttributes(query) {
                    clearTimeout(this.attributeTimer);

                    this.attributeTimer = setTimeout(() => this.fetchAttributes(query), 500);
                },

                fetchFamilies(query) {
                    this.loadingFamilies = true;

                    this.fetch({ entityName: 'attribute_family', query })
                        .then((options) => this.familyOptions = options)
                        .finally(() => this.loadingFamilies = false);
                },

                /**
                 * Sources are searched server-side and scoped to the bound families,
                 * so the page never embeds the attribute table.
                 */
                fetchAttributes(query) {
                    if (! this.familyIds.length) {
                        this.attributeOptions = [];

                        return;
                    }

                    this.loadingAttributes = true;

                    this.fetch({ entityName: 'attributes', query, inFamilies: this.familyIds })
                        .then((options) => this.attributeOptions = options)
                        .finally(() => this.loadingAttributes = false);
                },

                fetch(params) {
                    return this.$axios.get("{{ route('admin.catalog.options.fetch-all') }}", {
                        params: { locale: "{{ core()->getRequestedLocaleCode() }}", ...params },
                    })
                        .then(({ data }) => data.options.map((option) => ({
                            id: String(option.id),
                            label: option.label,
                        })))
                        .catch(() => []);
                },
            },

            watch: {
                familyIds() {
                    this.fetchAttributes('');
                },
            },
        });
    </script>
@endPushOnce
