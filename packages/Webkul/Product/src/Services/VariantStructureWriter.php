<?php

declare(strict_types=1);

namespace Webkul\Product\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Models\VariantStructureProxy;

/**
 * Validates and persists the desired state of one variant structure.
 *
 * Callers hand over a fully resolved desired state — merging a partial request
 * over the stored state is the caller's job — and this service decides whether
 * that state is legal and writes it atomically.
 */
class VariantStructureWriter
{
    public const AXIS_LEVELS = ['level_1', 'level_2'];

    public const PLACEMENT_LEVELS = ['common', 'sub_parent', 'variant'];

    /**
     * Whether any product at all points at the structure.
     *
     * Deletion uses this wider test: dropping the structure nulls the pointer on
     * every referencing product, which breaks even a tree that is still empty.
     */
    public function isReferenced(VariantStructure $structure): bool
    {
        return ProductProxy::modelClass()::query()
            ->where('variant_structure_id', $structure->id)
            ->exists();
    }

    /**
     * Create a structure for the family from a fully stated desired state, judged by
     * {@see save()} against the same rules every later write is held to. The code is
     * claimed up front so a duplicate fails validation rather than the unique index,
     * and the whole write is one transaction, so a rejected shape leaves nothing behind.
     *
     * @param  array{code: string, name?: ?string, levels: int, axes: array<string, array<int, string>>, placements?: array<string, array<int, string>>}  $desired
     *
     * @throws ValidationException When the family already carries the code, or the desired state is invalid.
     */
    public function create(AttributeFamily $attributeFamily, array $desired): VariantStructure
    {
        $code = trim((string) ($desired['code'] ?? ''));

        $this->assertCodeAvailable($attributeFamily, $code);

        $model = VariantStructureProxy::modelClass();

        try {
            return DB::transaction(fn (): VariantStructure => $this->save(
                $attributeFamily,
                new $model([
                    'attribute_family_id' => $attributeFamily->id,
                    'code'                => $code,
                ]),
                $desired
            ));
        } catch (UniqueConstraintViolationException) {
            throw $this->codeTakenException();
        }
    }

    /**
     * Apply the desired state to the structure.
     *
     * Placements carry no lifetime restriction and may be rewritten however many
     * products already hang off the structure. `levels` and `axes` may never move;
     * the caller holds them at stored values via {@see assertImmutableFieldsUnchanged()}.
     *
     * @param  array{name?: ?string, levels: int, axes: array<string, array<int, string>>, placements: array<string, array<int, string>>}  $desired
     *
     * @throws ValidationException When the desired state is invalid.
     */
    public function save(AttributeFamily $attributeFamily, VariantStructure $structure, array $desired): VariantStructure
    {
        $levels = (int) $desired['levels'];
        $axes = $this->normalizeAxes($desired['axes'] ?? []);
        $placements = $this->normalizePlacements($desired['placements'] ?? []);

        $this->assertValid($attributeFamily, $levels, $axes, $placements);

        $familyAttributes = $attributeFamily->customAttributes()->get()->keyBy('code');
        $name = $desired['name'] ?? null;

        return DB::transaction(function () use ($structure, $levels, $axes, $placements, $familyAttributes, $name): VariantStructure {
            $structure->fill([
                'name'   => $name === null || $name === '' ? $structure->code : $name,
                'levels' => $levels,
            ]);

            $structure->save();

            VariantStructureAxis::query()->where('variant_structure_id', $structure->id)->delete();
            VariantStructureAttribute::query()->where('variant_structure_id', $structure->id)->delete();

            foreach ($this->activeAxisLevels($levels) as $level) {
                foreach ($axes[$level] as $position => $attributeCode) {
                    VariantStructureAxis::create([
                        'variant_structure_id' => $structure->id,
                        'attribute_id'         => $familyAttributes[$attributeCode]->id,
                        'level'                => $level,
                        'position'             => $position,
                    ]);
                }
            }

            foreach (self::PLACEMENT_LEVELS as $level) {
                foreach ($placements[$level] as $attributeCode) {
                    VariantStructureAttribute::create([
                        'variant_structure_id' => $structure->id,
                        'attribute_id'         => $familyAttributes[$attributeCode]->id,
                        'level'                => $level,
                    ]);
                }
            }

            return $structure->refresh()->load(['axes.attribute', 'placements.attribute']);
        });
    }

    /**
     * Remove the structure together with its axis and placement rows.
     *
     * @throws ValidationException When a product still references the structure.
     */
    public function delete(VariantStructure $structure): void
    {
        if ($this->isReferenced($structure)) {
            throw ValidationException::withMessages([
                'code' => [trans('admin::app.catalog.families.edit.variant-structure-in-use')],
            ]);
        }

        DB::transaction(function () use ($structure): void {
            VariantStructureAxis::query()->where('variant_structure_id', $structure->id)->delete();
            VariantStructureAttribute::query()->where('variant_structure_id', $structure->id)->delete();

            $structure->delete();
        });
    }

    /**
     * Reject a payload that restates `levels` or `axes` differently.
     *
     * Both are baked into every product already built from the structure. A payload
     * may still carry them — a body read from GET round-trips — but only at their
     * stored values; axes compare order-sensitively, so a reorder is a change too.
     *
     * @throws ValidationException When the payload disagrees with the stored structure.
     */
    public function assertImmutableFieldsUnchanged(VariantStructure $structure, array $payload): void
    {
        $current = $this->currentState($structure);
        $changed = [];

        if (array_key_exists('levels', $payload) && (int) $payload['levels'] !== $current['levels']) {
            $changed[] = 'levels';
        }

        if (array_key_exists('axes', $payload) && $this->normalizeAxes((array) $payload['axes']) !== $current['axes']) {
            $changed[] = 'axes';
        }

        if ($changed === []) {
            return;
        }

        throw ValidationException::withMessages([
            'immutable' => [trans('admin::app.catalog.products.immutable-fields', ['fields' => implode(', ', $changed)])],
        ]);
    }

    /**
     * The desired state expressed as the stored structure currently stands.
     *
     * @return array{name: ?string, levels: int, axes: array<string, array<int, string>>, placements: array<string, array<int, string>>}
     */
    public function currentState(VariantStructure $structure): array
    {
        $axes = array_fill_keys(self::AXIS_LEVELS, []);
        $placements = array_fill_keys(self::PLACEMENT_LEVELS, []);

        foreach ($structure->axes as $axis) {
            if ($axis->attribute?->code !== null && isset($axes[$axis->level])) {
                $axes[$axis->level][(int) $axis->position] = $axis->attribute->code;
            }
        }

        foreach ($axes as $level => $codes) {
            ksort($codes);

            $axes[$level] = array_values($codes);
        }

        foreach ($structure->placements as $placement) {
            if ($placement->attribute?->code !== null && isset($placements[$placement->level])) {
                $placements[$placement->level][] = $placement->attribute->code;
            }
        }

        return [
            'name'       => $structure->name,
            'levels'     => (int) $structure->levels,
            'axes'       => $axes,
            'placements' => $placements,
        ];
    }

    /**
     * The axis levels a structure of the given depth actually owns.
     *
     * @return array<int, string>
     */
    protected function activeAxisLevels(int $levels): array
    {
        return $levels === 2 ? self::AXIS_LEVELS : ['level_1'];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function normalizeAxes(array $axes): array
    {
        $normalized = array_fill_keys(self::AXIS_LEVELS, []);

        foreach (self::AXIS_LEVELS as $level) {
            $normalized[$level] = array_values(array_filter(
                array_map(fn ($code): string => is_string($code) ? trim($code) : '', (array) ($axes[$level] ?? [])),
                fn (string $code): bool => $code !== ''
            ));
        }

        return $normalized;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function normalizePlacements(array $placements): array
    {
        $normalized = array_fill_keys(self::PLACEMENT_LEVELS, []);

        foreach (self::PLACEMENT_LEVELS as $level) {
            $normalized[$level] = array_values(array_filter(
                array_map(fn ($code): string => is_string($code) ? trim($code) : '', (array) ($placements[$level] ?? [])),
                fn (string $code): bool => $code !== ''
            ));
        }

        return $normalized;
    }

    /**
     * @param  array<string, array<int, string>>  $axes
     * @param  array<string, array<int, string>>  $placements
     *
     * @throws ValidationException
     */
    protected function assertValid(AttributeFamily $attributeFamily, int $levels, array $axes, array $placements): void
    {
        $errors = [];

        if (! in_array($levels, [1, 2], true)) {
            throw ValidationException::withMessages([
                'levels' => [trans('validation.in', ['attribute' => 'levels'])],
            ]);
        }

        if ($axes['level_1'] === []) {
            $errors['axes.level_1'][] = trans('validation.required', ['attribute' => 'axes.level_1']);
        }

        if ($levels === 2 && $axes['level_2'] === []) {
            $errors['axes.level_2'][] = trans('validation.required', ['attribute' => 'axes.level_2']);
        }

        if ($levels === 1 && $axes['level_2'] !== []) {
            $errors['axes.level_2'][] = trans('validation.in', ['attribute' => 'axes.level_2']);
        }

        if ($levels === 1 && $placements['sub_parent'] !== []) {
            $errors['placements.sub_parent'][] = trans('validation.in', ['attribute' => 'placements.sub_parent']);
        }

        $activeAxes = [];

        foreach ($this->activeAxisLevels($levels) as $level) {
            $activeAxes = [...$activeAxes, ...$axes[$level]];
        }

        if (count(array_unique($activeAxes)) !== count($activeAxes)) {
            $errors['axes'][] = trans('validation.distinct', ['attribute' => 'axes']);
        }

        $familyAttributes = $attributeFamily->customAttributes()->get()->keyBy('code');
        $eligibleAxes = $attributeFamily->getConfigurableAttributes()->keyBy('code');

        foreach ($activeAxes as $attributeCode) {
            if (! $eligibleAxes->has($attributeCode)) {
                $errors['axes'][] = trans('validation.exists', ['attribute' => $attributeCode]);
            }
        }

        $placedAttributes = [];

        foreach (self::PLACEMENT_LEVELS as $level) {
            foreach ($placements[$level] as $attributeCode) {
                if (! $familyAttributes->has($attributeCode)) {
                    $errors['placements.'.$level][] = trans('validation.exists', ['attribute' => $attributeCode]);

                    continue;
                }

                if (in_array($attributeCode, $activeAxes, true)) {
                    $errors['placements.'.$level][] = trans('validation.in', ['attribute' => $attributeCode]);

                    continue;
                }

                if (isset($placedAttributes[$attributeCode])) {
                    $errors['placements.'.$level][] = trans('validation.distinct', ['attribute' => $attributeCode]);

                    continue;
                }

                $placedAttributes[$attributeCode] = true;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Codes are unique per family, so the same code may exist under another family.
     *
     * @throws ValidationException When this family already carries the code.
     */
    protected function assertCodeAvailable(AttributeFamily $attributeFamily, string $code): void
    {
        $taken = VariantStructureProxy::modelClass()::query()
            ->where('attribute_family_id', $attributeFamily->id)
            ->where('code', $code)
            ->exists();

        if (! $taken) {
            return;
        }

        throw $this->codeTakenException();
    }

    /**
     * The single rejection for a code the family already carries, whether the
     * clash was seen up front or raised by the unique index under a race.
     */
    protected function codeTakenException(): ValidationException
    {
        return ValidationException::withMessages([
            'code' => [trans('validation.unique', ['attribute' => 'code'])],
        ]);
    }
}
