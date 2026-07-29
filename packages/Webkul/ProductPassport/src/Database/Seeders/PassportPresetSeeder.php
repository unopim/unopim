<?php

namespace Webkul\ProductPassport\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\ProductPassport\Contracts\PassportTemplate as PassportTemplateContract;
use Webkul\ProductPassport\Enums\PassportFieldSource;
use Webkul\ProductPassport\Enums\PassportFieldTier;
use Webkul\ProductPassport\Models\PassportTemplateProxy;

/**
 * Materializes a preset from `passport_presets` config into an editable template.
 *
 * Idempotent by template code: an existing template is returned untouched, so the
 * command is safe to re-run and never overwrites a merchant's edits. Labels are
 * resolved for the locales the catalog actually enables, not for every locale the
 * package ships translations for.
 */
class PassportPresetSeeder
{
    public function __construct(
        private readonly LocaleRepository $locales,
    ) {}

    public function run(string $code): ?PassportTemplateContract
    {
        $preset = config('passport_presets.'.$code);

        if (! is_array($preset)) {
            return null;
        }

        $existing = PassportTemplateProxy::modelClass()::query()->where('code', $code)->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($code, $preset): PassportTemplateContract {
            $template = PassportTemplateProxy::modelClass()::create(array_merge(
                ['code' => $code, 'is_enabled' => true],
                $this->translated(fn (string $locale): array => ['name' => trans($preset['name_key'], [], $locale)]),
            ));

            $sections = [];

            foreach (array_values($preset['sections'] ?? []) as $position => $labelKey) {
                $sectionCode = array_keys($preset['sections'])[$position];

                $sections[$sectionCode] = $template->sections()->create(array_merge(
                    ['code' => $sectionCode, 'position' => $position],
                    $this->translated(fn (string $locale): array => ['name' => trans($labelKey, [], $locale)]),
                ))->id;
            }

            foreach (array_values($preset['fields'] ?? []) as $position => $field) {
                $labelKey = 'passport::app.templates.preset.fields.'.$field['code'];

                $template->fields()->create(array_merge([
                    'code'                         => $field['code'],
                    'passport_template_section_id' => $sections[$field['section'] ?? ''] ?? null,
                    'source_type'                  => PassportFieldSource::Attribute,
                    'attribute_id'                 => null,
                    'tier'                         => $field['tier'] ?? PassportFieldTier::Consumer,
                    'is_required'                  => (bool) ($field['required'] ?? false),
                    'role'                         => $field['role'] ?? null,
                    'position'                     => $position,
                ], $this->translated(fn (string $locale): array => ['label' => trans($labelKey, [], $locale)])));
            }

            return $template;
        });
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_keys((array) config('passport_presets', []));
    }

    /**
     * TranslatableModel::fill() expects each locale code as a TOP-LEVEL key, so the
     * per-locale payload is keyed by code rather than nested under the attribute.
     *
     * @param  callable(string): array<string, string>  $values
     * @return array<string, array<string, string>>
     */
    private function translated(callable $values): array
    {
        return $this->locales->getActiveLocales()
            ->mapWithKeys(fn ($locale): array => [$locale->code => $values($locale->code)])
            ->all();
    }
}
