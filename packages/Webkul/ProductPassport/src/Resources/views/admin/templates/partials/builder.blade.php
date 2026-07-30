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
            $locale->code => ['name' => $section->translate($locale->code)->name ?? ''],
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

    $familyValue = $template->families->pluck('id')->implode(',');

    $familyParams = $claimedFamilies === []
        ? []
        : ['exclude' => ['columnName' => 'id', 'values' => $claimedFamilies]];
@endphp

<v-passport-template-builder
    :locales='@json($localesForJs)'
    :source-options='@json($sourceOptions)'
    :tier-options='@json($tierOptions)'
    :role-options='@json($roleOptions)'
    :initial-sections='@json($sectionsForJs)'
    :initial-fields='@json($fieldsForJs)'
    current-locale="{{ $currentLocaleCode }}"
></v-passport-template-builder>

@pushOnce('scripts')
    <script type="text/x-template" id="v-passport-template-builder-template">
        <div class="flex flex-col gap-2">
            <x-admin::accordion :title="trans('passport::app.templates.builder.families-heading')">
                <x-slot:content>
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('passport::app.templates.builder.families')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="multiselect"
                            async="true"
                            entity-name="attribute_family"
                            track-by="id"
                            label-by="label"
                            name="families"
                            :value="$familyValue"
                            :label="trans('passport::app.templates.builder.families')"
                            :placeholder="trans('passport::app.templates.builder.select-families')"
                            :query-params="json_encode($familyParams)"
                            @input="onFamiliesChange($event)"
                        />

                        <x-admin::form.control-group.error control-name="families" />

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            @lang('passport::app.templates.builder.families-info')
                        </p>
                    </x-admin::form.control-group>
                </x-slot>
            </x-admin::accordion>

            <x-admin::accordion :title="trans('passport::app.templates.builder.sections-heading')">
                <x-slot:content>
                    <div class="flex justify-between items-center gap-2.5">
                        <p class="text-xs text-gray-500 dark:text-gray-300">
                            @lang('passport::app.templates.builder.sections-info')
                        </p>

                        <button type="button" class="secondary-button text-sm shrink-0" @click="openSection(null)">
                            @lang('passport::app.templates.builder.add-section')
                        </button>
                    </div>

                    <p v-if="! sections.length" class="mt-4 text-sm text-gray-400 dark:text-gray-300 italic">
                        @lang('passport::app.templates.builder.sections-empty')
                    </p>

                    <div v-else class="mt-4 overflow-x-auto">
                        <x-admin::table>
                            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                <x-admin::table.thead.tr>
                                    <x-admin::table.th class="!p-0" />

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.section-name')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.code')
                                    </x-admin::table.th>

                                    <x-admin::table.th />
                                </x-admin::table.thead.tr>
                            </x-admin::table.thead>

                            <draggable
                                tag="tbody"
                                ghost-class="draggable-ghost"
                                handle=".icon-drag"
                                v-bind="{animation: 200}"
                                :list="sections"
                                item-key="uid"
                                @end="touch('sections')"
                            >
                                <template #item="{ element, index }">
                                    <x-admin::table.tbody.tr class="hover:bg-violet-50 dark:hover:bg-cherry-800">
                                        <x-admin::table.td class="!px-0 text-center">
                                            <i class="icon-drag text-2xl cursor-grab"></i>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="element.locales[currentLocale].name || element.code"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="element.code"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td class="!px-0">
                                            <span
                                                class="icon-edit p-1.5 rounded-md text-2xl cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                                :title="lang.edit"
                                                @click="openSection(element)"
                                            ></span>

                                            <span
                                                class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                                :title="lang.remove"
                                                @click="sections.splice(index, 1); touch('sections')"
                                            ></span>
                                        </x-admin::table.td>

                                        <input type="hidden" :name="'sections[' + index + '][code]'" :value="element.code" />

                                        <template v-for="locale in locales" :key="'section-' + element.uid + '-' + locale.code">
                                            <input
                                                type="hidden"
                                                :name="'sections[' + index + '][' + locale.code + '][name]'"
                                                :value="element.locales[locale.code].name"
                                            />
                                        </template>
                                    </x-admin::table.tbody.tr>
                                </template>
                            </draggable>
                        </x-admin::table>
                    </div>
                </x-slot>
            </x-admin::accordion>

            <x-admin::accordion :title="trans('passport::app.templates.builder.fields-heading')">
                <x-slot:content>
                    <div class="flex justify-between items-center gap-2.5">
                        <p class="text-xs text-gray-500 dark:text-gray-300">
                            @lang('passport::app.templates.builder.fields-info')
                        </p>

                        <div class="flex items-center gap-2.5 shrink-0">
                            <span class="text-sm text-gray-600 dark:text-gray-300" v-text="readiness"></span>

                            <button type="button" class="secondary-button text-sm" @click="openField(null)">
                                @lang('passport::app.templates.builder.add-field')
                            </button>
                        </div>
                    </div>

                    <p v-if="! fields.length" class="mt-4 text-sm text-gray-400 dark:text-gray-300 italic">
                        @lang('passport::app.templates.builder.fields-empty')
                    </p>

                    <div v-else class="mt-4 overflow-x-auto">
                        <x-admin::table>
                            <x-admin::table.thead class="text-sm font-medium dark:bg-gray-800">
                                <x-admin::table.thead.tr>
                                    <x-admin::table.th class="!p-0" />

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.field-label')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.code')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.section')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.source')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.tier')
                                    </x-admin::table.th>

                                    <x-admin::table.th>
                                        @lang('passport::app.templates.builder.required')
                                    </x-admin::table.th>

                                    <x-admin::table.th />
                                </x-admin::table.thead.tr>
                            </x-admin::table.thead>

                            <draggable
                                tag="tbody"
                                ghost-class="draggable-ghost"
                                handle=".icon-drag"
                                v-bind="{animation: 200}"
                                :list="fields"
                                item-key="uid"
                                @end="touch('fields')"
                            >
                                <template #item="{ element, index }">
                                    <x-admin::table.tbody.tr class="hover:bg-violet-50 dark:hover:bg-cherry-800">
                                        <x-admin::table.td class="!px-0 text-center">
                                            <i class="icon-drag text-2xl cursor-grab"></i>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="element.locales[currentLocale].label || element.code"></p>

                                            <p
                                                v-if="element.role"
                                                class="text-xs text-gray-500 dark:text-gray-300"
                                                v-text="optionLabel(roleOptions, element.role)"
                                            ></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="element.code"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="sectionLabel(element.section)"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="sourceSummary(element)"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <p class="dark:text-white" v-text="optionLabel(tierOptions, element.tier)"></p>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            <span v-if="element.is_required" class="label-pending" v-text="lang.required"></span>
                                            <span v-else class="label-info" v-text="lang.optional"></span>
                                        </x-admin::table.td>

                                        <x-admin::table.td class="!px-0">
                                            <span
                                                class="icon-edit p-1.5 rounded-md text-2xl cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                                :title="lang.edit"
                                                @click="openField(element)"
                                            ></span>

                                            <span
                                                class="icon-delete p-1.5 rounded-md text-2xl cursor-pointer hover:bg-violet-100 dark:hover:bg-gray-800"
                                                :title="lang.remove"
                                                @click="fields.splice(index, 1); touch('fields')"
                                            ></span>
                                        </x-admin::table.td>

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
                                    </x-admin::table.tbody.tr>
                                </template>
                            </draggable>
                        </x-admin::table>
                    </div>
                </x-slot>
            </x-admin::accordion>

            <x-admin::modal ref="sectionModal">
                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('passport::app.templates.builder.section-modal-title')
                    </p>
                </x-slot>

                <x-slot:content data-unsaved-ignore>
                    <template v-if="draftSection?.isNew">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label
                                class="w-full"
                                localizable="true"
                                :current-locale-code="$currentLocaleCode"
                            >
                                @lang('passport::app.templates.builder.section-name')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="draft_section_name"
                                v-code-generator="'draft_section_code'"
                                ::key="'section-new-' + draftSection.uid"
                                :label="trans('passport::app.templates.builder.section-name')"
                                :placeholder="trans('passport::app.templates.builder.section-name')"
                                @input="onSectionNameInput($event.target.value)"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('passport::app.templates.builder.code')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="draft_section_code"
                                v-code
                                ::key="'section-new-code-' + draftSection.uid"
                                :label="trans('passport::app.templates.builder.code')"
                                :placeholder="trans('passport::app.templates.builder.code')"
                                @input="onSectionCodeInput($event.target.value)"
                            />
                        </x-admin::form.control-group>
                    </template>

                    <template v-else-if="draftSection">
                        <x-admin::form.translatable-field
                            :locales="$locales"
                            :submit="false"
                            field="name"
                            :label="trans('passport::app.templates.builder.section-name')"
                            :placeholder="trans('passport::app.templates.builder.section-name')"
                            ::key="'section-name-' + draftSection.uid"
                            ::values="sectionNameValues"
                            @update:values="onSectionNameChange($event)"
                        />

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('passport::app.templates.builder.code')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                class="cursor-not-allowed"
                                name="draft_section_code"
                                readonly
                                ::key="'section-code-' + draftSection.uid"
                                ::value="draftSection.code"
                                :label="trans('passport::app.templates.builder.code')"
                            />
                        </x-admin::form.control-group>
                    </template>
                </x-slot>

                <x-slot:footer>
                    <button type="button" class="primary-button" @click="commitSection">
                        @lang('passport::app.templates.builder.done')
                    </button>
                </x-slot>
            </x-admin::modal>

            <x-admin::modal ref="fieldModal">
                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        @lang('passport::app.templates.builder.field-modal-title')
                    </p>
                </x-slot>

                <x-slot:content data-unsaved-ignore>
                    <template v-if="draftField">
                        <template v-if="draftField.isNew">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label
                                    class="w-full"
                                    localizable="true"
                                    :current-locale-code="$currentLocaleCode"
                                >
                                    @lang('passport::app.templates.builder.field-label')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="draft_field_name"
                                    v-code-generator="'draft_field_code_new'"
                                    ::key="'field-new-' + draftField.uid"
                                    :label="trans('passport::app.templates.builder.field-label')"
                                    :placeholder="trans('passport::app.templates.builder.field-label')"
                                    @input="onFieldLabelInput($event.target.value)"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('passport::app.templates.builder.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="draft_field_code_new"
                                    v-code
                                    ::key="'field-new-code-' + draftField.uid"
                                    :label="trans('passport::app.templates.builder.code')"
                                    :placeholder="trans('passport::app.templates.builder.code')"
                                    @input="onFieldCodeInput($event.target.value)"
                                />
                            </x-admin::form.control-group>
                        </template>

                        <template v-else>
                            <x-admin::form.translatable-field
                                :locales="$locales"
                                :submit="false"
                                field="label"
                                :label="trans('passport::app.templates.builder.field-label')"
                                :placeholder="trans('passport::app.templates.builder.field-label')"
                                ::key="'field-label-' + draftField.uid"
                                ::values="fieldLabelValues"
                                @update:values="onFieldLabelChange($event)"
                            />

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('passport::app.templates.builder.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    class="cursor-not-allowed"
                                    name="draft_field_code"
                                    readonly
                                    ::key="'field-code-' + draftField.uid"
                                    ::value="draftField.code"
                                    :label="trans('passport::app.templates.builder.code')"
                                />
                            </x-admin::form.control-group>
                        </template>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('passport::app.templates.builder.section')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="draft_field_section"
                                track-by="id"
                                label-by="label"
                                ::options="sectionSelectOptions"
                                ::value="draftField.section"
                                :label="trans('passport::app.templates.builder.section')"
                                :placeholder="trans('passport::app.templates.builder.no-section')"
                                @input="draftField.section = optionId($event)"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('passport::app.templates.builder.source')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="draft_field_source"
                                track-by="id"
                                label-by="label"
                                ::options="sourceOptions"
                                ::value="draftField.source_type"
                                :label="trans('passport::app.templates.builder.source')"
                                :placeholder="trans('passport::app.templates.builder.source')"
                                @input="draftField.source_type = optionId($event)"
                            />
                        </x-admin::form.control-group>

                        <div v-if="draftField.source_type === 'attribute'">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('passport::app.templates.builder.attribute')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    async="true"
                                    entity-name="attributes"
                                    track-by="id"
                                    label-by="label"
                                    name="draft_field_attribute"
                                    ::key="attributeSelectKey"
                                    ::query-params="attributeQueryParams"
                                    ::value="draftField.attribute_id"
                                    :label="trans('passport::app.templates.builder.attribute')"
                                    :placeholder="trans('passport::app.templates.builder.select-attribute')"
                                    @input="onAttributeSelected($event)"
                                />

                                <p v-if="! familyIds.length" class="mt-1 text-xs text-orange-500">
                                    @lang('passport::app.templates.builder.select-families-first')
                                </p>
                            </x-admin::form.control-group>
                        </div>

                        <template v-else>
                            <x-admin::form.control-group v-if="draftField.isNew">
                                <x-admin::form.control-group.label
                                    class="w-full"
                                    localizable="true"
                                    :current-locale-code="$currentLocaleCode"
                                >
                                    @lang('passport::app.templates.builder.fixed-value')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="draft_field_fixed"
                                    ::key="'field-fixed-new-' + draftField.uid"
                                    :label="trans('passport::app.templates.builder.fixed-value')"
                                    :placeholder="trans('passport::app.templates.builder.fixed-value')"
                                    @input="draftField.locales['{{ $currentLocaleCode }}'].fixed_value = $event.target.value"
                                />
                            </x-admin::form.control-group>

                            <x-admin::form.translatable-field
                                v-else
                                :locales="$locales"
                                :submit="false"
                                field="fixed_value"
                                :label="trans('passport::app.templates.builder.fixed-value')"
                                :placeholder="trans('passport::app.templates.builder.fixed-value')"
                                ::key="'field-fixed-' + draftField.uid"
                                ::values="fieldFixedValues"
                                @update:values="onFieldFixedChange($event)"
                            />
                        </template>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('passport::app.templates.builder.tier')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="draft_field_tier"
                                track-by="id"
                                label-by="label"
                                ::options="tierOptions"
                                ::value="draftField.tier"
                                :label="trans('passport::app.templates.builder.tier')"
                                :placeholder="trans('passport::app.templates.builder.tier')"
                                @input="draftField.tier = optionId($event)"
                            />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('passport::app.templates.builder.role')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                name="draft_field_role"
                                track-by="id"
                                label-by="label"
                                ::options="roleOptions"
                                ::value="draftField.role"
                                :label="trans('passport::app.templates.builder.role')"
                                :placeholder="trans('passport::app.templates.builder.no-role')"
                                @input="draftField.role = optionId($event)"
                            />

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                                @lang('passport::app.templates.builder.role-info')
                            </p>
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="flex items-center gap-2.5">
                            <x-admin::form.control-group.label class="!mb-0">
                                @lang('passport::app.templates.builder.required')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="switch"
                                name="draft_field_required"
                                value="1"
                                ::checked="draftField.is_required"
                                @change="draftField.is_required = $event.target.checked"
                            />
                        </x-admin::form.control-group>
                    </template>
                </x-slot>

                <x-slot:footer>
                    <button type="button" class="primary-button" @click="commitField">
                        @lang('passport::app.templates.builder.done')
                    </button>
                </x-slot>
            </x-admin::modal>
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
                currentLocale: { type: String, required: true },
            },

            data() {
                return {
                    sections: [],
                    fields: [],
                    familyIds: @json($template->families->pluck('id')->map(fn ($id): string => (string) $id)->values()),
                    draftSection: null,
                    draftField: null,
                    editingSection: null,
                    editingField: null,
                    sequence: 0,
                    lang: {
                        edit: @json(trans('passport::app.templates.builder.edit')),
                        remove: @json(trans('passport::app.templates.builder.remove')),
                        required: @json(trans('passport::app.templates.builder.required')),
                        optional: @json(trans('passport::app.templates.builder.optional')),
                        noSection: @json(trans('passport::app.templates.builder.no-section')),
                        fixedValue: @json(trans('passport::app.templates.builder.fixed-value')),
                        noAttribute: @json(trans('passport::app.templates.builder.select-attribute')),
                    },
                };
            },

            computed: {
                sectionNameValues() {
                    return this.flatten(this.draftSection?.locales, 'name');
                },

                fieldLabelValues() {
                    return this.flatten(this.draftField?.locales, 'label');
                },

                fieldFixedValues() {
                    return this.flatten(this.draftField?.locales, 'fixed_value');
                },

                sectionSelectOptions() {
                    return this.sections.map((section) => ({
                        id:    section.code,
                        label: section.locales[this.currentLocale].name || section.code,
                    }));
                },

                attributeQueryParams() {
                    return { inFamilies: this.familyIds };
                },

                attributeSelectKey() {
                    return 'attribute-select-' + this.familyIds.join('-');
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
            },

            methods: {
                /**
                 * Row inputs are hidden and written by Vue, so they raise no input
                 * event of their own; the tracker is told which field group changed.
                 */
                touch(group) {
                    this.$nextTick(() => {
                        this.$el?.dispatchEvent(new CustomEvent('unsaved-changes:touch', {
                            detail: { name: group },
                            bubbles: true,
                        }));
                    });
                },

                touchFamilies() {
                    this.touch('families');
                },

                localeMap(saved, shape) {
                    return this.locales.reduce((carry, locale) => {
                        carry[locale.code] = { ...shape, ...(saved?.[locale.code] ?? {}) };

                        return carry;
                    }, {});
                },

                optionLabel(options, id) {
                    return options.find((option) => option.id === id)?.label ?? '';
                },

                sectionLabel(code) {
                    return this.sectionSelectOptions.find((option) => option.id === code)?.label ?? this.lang.noSection;
                },

                sourceSummary(field) {
                    if (field.source_type === 'fixed') {
                        return this.lang.fixedValue;
                    }

                    return field.attribute_label || this.lang.noAttribute;
                },

                optionId(event) {
                    if (! event) {
                        return '';
                    }

                    try {
                        const parsed = typeof event === 'string' ? JSON.parse(event) : event;

                        return parsed?.id ?? '';
                    } catch (exception) {
                        return '';
                    }
                },

                onFamiliesChange(event) {
                    const parse = (value) => {
                        try {
                            return typeof value === 'string' ? JSON.parse(value) : value;
                        } catch (exception) {
                            return [];
                        }
                    };

                    const selected = parse(event) ?? [];

                    this.familyIds = (Array.isArray(selected) ? selected : [selected])
                        .filter(Boolean)
                        .map((option) => String(option.id ?? option));

                    this.touchFamilies();
                },

                onAttributeSelected(event) {
                    const parse = () => {
                        try {
                            return typeof event === 'string' ? JSON.parse(event) : event;
                        } catch (exception) {
                            return null;
                        }
                    };

                    const option = parse();

                    this.draftField.attribute_id = option?.id ? String(option.id) : '';
                    this.draftField.attribute_label = option?.label ?? '';
                },

                openSection(section) {
                    this.editingSection = section;

                    this.draftSection = section
                        ? JSON.parse(JSON.stringify(section))
                        : {
                            uid: `section-${this.sequence++}`,
                            isNew: true,
                            code: '',
                            locales: this.localeMap({}, { name: '' }),
                        };

                    this.$refs.sectionModal.toggle();
                },

                commitSection() {
                    this.draftSection.code = this.draftSection.code || this.generatedCode('section', this.sections);

                    if (this.editingSection) {
                        Object.assign(this.editingSection, this.draftSection);
                    } else {
                        this.sections.push(this.draftSection);
                    }

                    this.draftSection = null;
                    this.editingSection = null;

                    this.touch('sections');

                    this.$refs.sectionModal.toggle();
                },

                openField(field) {
                    this.editingField = field;

                    this.draftField = field
                        ? JSON.parse(JSON.stringify(field))
                        : {
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
                        };

                    this.$refs.fieldModal.toggle();
                },

                commitField() {
                    this.draftField.code = this.draftField.code || this.generatedCode('field', this.fields);

                    if (this.editingField) {
                        Object.assign(this.editingField, this.draftField);
                    } else {
                        this.fields.push(this.draftField);
                    }

                    this.draftField = null;
                    this.editingField = null;

                    this.touch('fields');

                    this.$refs.fieldModal.toggle();
                },

                flatten(locales, key) {
                    return Object.entries(locales ?? {}).reduce((carry, [code, values]) => {
                        carry[code] = values[key] ?? '';

                        return carry;
                    }, {});
                },

                onSectionNameInput(name) {
                    this.draftSection.locales[this.currentLocale].name = name;
                },

                onSectionCodeInput(code) {
                    this.draftSection.code = code;
                },

                onFieldLabelInput(label) {
                    this.draftField.locales[this.currentLocale].label = label;
                },

                onFieldCodeInput(code) {
                    this.draftField.code = code;
                },

                onSectionNameChange(values) {
                    Object.entries(values).forEach(([code, name]) => {
                        this.draftSection.locales[code].name = name;
                    });
                },

                onFieldLabelChange(values) {
                    Object.entries(values).forEach(([code, label]) => {
                        this.draftField.locales[code].label = label;
                    });
                },

                onFieldFixedChange(values) {
                    Object.entries(values).forEach(([code, value]) => {
                        this.draftField.locales[code].fixed_value = value;
                    });
                },

                generatedCode(prefix, rows) {
                    const taken = rows.map((row) => row.code);

                    let index = rows.length + 1;

                    while (taken.includes(`${prefix}_${index}`)) {
                        index++;
                    }

                    return `${prefix}_${index}`;
                },

            },
        });
    </script>
@endPushOnce
