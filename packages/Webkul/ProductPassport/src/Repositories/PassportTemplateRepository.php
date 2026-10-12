<?php

namespace Webkul\ProductPassport\Repositories;

use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;
use Webkul\ProductPassport\Contracts\PassportTemplate as PassportTemplateContract;
use Webkul\ProductPassport\Models\PassportTemplate;

class PassportTemplateRepository extends Repository
{
    public function model(): string
    {
        return PassportTemplate::class;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PassportTemplateContract
    {
        return DB::transaction(function () use ($data): PassportTemplateContract {
            [$attributes, $families, $sections, $fields] = $this->split($data);

            $template = parent::create($attributes);

            $this->syncFamilies($template, $families);

            $this->syncFields($template, $fields, $this->syncSections($template, $sections));

            return $template;
        });
    }

    /**
     * The editor submits the complete section and field list on every save, so
     * rows absent from the payload are removed rather than tracked with
     * per-row "deleted" flags.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, $id): PassportTemplateContract
    {
        return DB::transaction(function () use ($data, $id): PassportTemplateContract {
            [$attributes, $families, $sections, $fields] = $this->split($data);

            $template = parent::update($attributes, $id);

            $this->syncFamilies($template, $families);

            $this->syncFields($template, $fields, $this->syncSections($template, $sections));

            return $template->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: list<int>, 2: list<array<string, mixed>>, 3: list<array<string, mixed>>}
     */
    private function split(array $data): array
    {
        $families = array_values(array_map('intval', (array) ($data['families'] ?? [])));
        $sections = array_values((array) ($data['sections'] ?? []));
        $fields = array_values((array) ($data['fields'] ?? []));

        unset($data['families'], $data['sections'], $data['fields']);

        return [$this->translatableOf($data), $families, $sections, $fields];
    }

    /**
     * Locale payloads the merchant never filled arrive as nulls (Laravel converts
     * the empty inputs), and a translation row cannot hold one. Dropping them
     * keeps the untranslated locales without a row, so lookups fall back.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function translatableOf(array $row): array
    {
        return array_filter($row, function ($value, $key): bool {
            if (! is_array($value) || is_numeric($key)) {
                return ! is_array($value);
            }

            return array_filter($value, fn ($translated): bool => trim((string) $translated) !== '') !== [];
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param  list<int>  $familyIds
     */
    private function syncFamilies(PassportTemplateContract $template, array $familyIds): void
    {
        $template->families()->sync($familyIds);
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, int> section code => id
     */
    private function syncSections(PassportTemplateContract $template, array $sections): array
    {
        $existing = $template->sections()->get()->keyBy('code');

        $keptIds = [];
        $idsByCode = [];

        foreach ($sections as $position => $section) {
            $code = (string) ($section['code'] ?? '');

            if ($code === '') {
                continue;
            }

            $payload = ['code' => $code, 'position' => $position] + $this->translationsOf($section);

            $model = $existing->get($code);

            if ($model === null) {
                $model = $template->sections()->create($payload);
            } else {
                $model->update($payload);
            }

            $keptIds[] = $model->id;
            $idsByCode[$code] = $model->id;
        }

        $template->sections()->whereNotIn('id', $keptIds ?: [0])->delete();

        return $idsByCode;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, int>  $sectionIds
     */
    private function syncFields(PassportTemplateContract $template, array $fields, array $sectionIds): void
    {
        $existing = $template->fields()->get()->keyBy('code');

        $keptIds = [];

        foreach ($fields as $position => $field) {
            $code = (string) ($field['code'] ?? '');

            if ($code === '') {
                continue;
            }

            $isFixed = ($field['source_type'] ?? '') === 'fixed';

            $payload = [
                'code'                         => $code,
                'passport_template_section_id' => $sectionIds[$field['section'] ?? ''] ?? null,
                'source_type'                  => $isFixed ? 'fixed' : 'attribute',
                'attribute_id'                 => $isFixed ? null : ($field['attribute_id'] ?? null),
                'tier'                         => $field['tier'] ?? 'consumer',
                'is_required'                  => filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'role'                         => ($field['role'] ?? '') ?: null,
                'position'                     => $position,
            ] + $this->translationsOf($field);

            $model = $existing->get($code);

            if ($model === null) {
                $model = $template->fields()->create($payload);
            } else {
                $model->update($payload);
            }

            $keptIds[] = $model->id;
        }

        $template->fields()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    /**
     * TranslatableModel::fill() expects each locale code as a TOP-LEVEL key, so
     * the per-locale payload is passed through untouched rather than nested.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, array<string, mixed>>
     */
    private function translationsOf(array $row): array
    {
        $locales = array_filter($row, fn ($value, $key): bool => is_array($value) && ! is_numeric($key), ARRAY_FILTER_USE_BOTH);

        return $this->translatableOf($locales);
    }
}
