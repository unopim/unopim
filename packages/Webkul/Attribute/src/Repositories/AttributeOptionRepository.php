<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Contracts\AttributeOption;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Traits\Sanitizer;

class AttributeOptionRepository extends Repository
{
    use Sanitizer;

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AttributeOption::class;
    }

    /**
     * @return AttributeOption
     */
    public function create(array $data)
    {
        if (! isset($data['sort_order'])) {
            $maxSortOrder = $this->model
                ->where('attribute_id', $data['attribute_id'])
                ->max('sort_order');

            $data['sort_order'] = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        $option = parent::create($data);

        $this->uploadSwatchImage($data, $option->id);

        return $option;
    }

    /**
     * @param  int  $id
     * @return AttributeOption
     */
    public function update(array $data, $id)
    {

        $option = parent::update($data, $id);

        $this->uploadSwatchImage($data, $id);

        return $option;
    }

    /**
     * Determine whether any product holds the option as a value of its attribute, in any scope.
     */
    public function isUsedByProducts(AttributeOption $option): bool
    {
        return $this->usedByProducts([$option->id]) !== [];
    }

    /**
     * Return the ids of the given options that any product holds as a value of their attribute, in any scope.
     *
     * A text match on the option code and the attribute key narrows the candidates portably across
     * database engines; the decoded values then confirm the code belongs to that attribute, as a single
     * value, a list or a comma-separated string.
     *
     * @param  array<int, int|string>  $optionIds
     * @return array<int, int>
     */
    public function usedByProducts(array $optionIds): array
    {
        $usedIds = [];

        $valuesColumn = 'LOWER('.DB::rawQueryGrammar()->castToText(DB::getQueryGrammar()->wrap('values')).')';

        foreach (array_chunk(array_unique($optionIds), 100) as $optionIdChunk) {
            $optionsByAttribute = [];

            foreach ($this->model->newQuery()->with('attribute:id,code')->whereIn('id', $optionIdChunk)->get(['id', 'code', 'attribute_id']) as $option) {
                if ($option->attribute) {
                    $optionsByAttribute[$option->attribute->code][mb_strtolower($option->code)] = (int) $option->id;
                }
            }

            if ($optionsByAttribute === []) {
                continue;
            }

            $pending = array_fill_keys(array_merge(...array_map(array_values(...), array_values($optionsByAttribute))), true);

            DB::table('products')
                ->select('id', 'values')
                ->where(function ($query) use ($optionsByAttribute, $valuesColumn): void {
                    foreach ($optionsByAttribute as $attributeCode => $codes) {
                        foreach (array_keys($codes) as $optionCode) {
                            $query->orWhere(function ($match) use ($attributeCode, $optionCode, $valuesColumn): void {
                                $match->whereRaw("{$valuesColumn} LIKE ?", ['%'.$this->escapeLike((string) $optionCode).'%'])
                                    ->whereRaw("{$valuesColumn} LIKE ?", ['%"'.$this->escapeLike(mb_strtolower($attributeCode)).'"%']);
                            });
                        }
                    }
                })
                ->lazyById(200)
                ->each(function (object $product) use ($optionsByAttribute, &$pending, &$usedIds): bool {
                    $values = json_decode((string) $product->values, true) ?? [];

                    foreach ($optionsByAttribute as $attributeCode => $codes) {
                        foreach (array_intersect_key($codes, array_flip($this->productOptionCodes($values, $attributeCode))) as $optionId) {
                            $usedIds[] = $optionId;

                            unset($pending[$optionId]);
                        }
                    }

                    return $pending !== [];
                });
        }

        return array_values(array_unique($usedIds));
    }

    /**
     * Escape the LIKE wildcards in a value so it matches literally.
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Collect the lower-cased option codes a product holds for the attribute across every value scope.
     *
     * @param  array<string, mixed>  $values
     * @return array<int, string>
     */
    protected function productOptionCodes(array $values, string $attributeCode): array
    {
        $scopedValues = [$values['common'][$attributeCode] ?? null];

        foreach (['locale_specific', 'channel_specific'] as $scope) {
            foreach ((array) ($values[$scope] ?? []) as $bucket) {
                $scopedValues[] = is_array($bucket) ? ($bucket[$attributeCode] ?? null) : null;
            }
        }

        foreach ((array) ($values['channel_locale_specific'] ?? []) as $channelBucket) {
            foreach ((array) $channelBucket as $bucket) {
                $scopedValues[] = is_array($bucket) ? ($bucket[$attributeCode] ?? null) : null;
            }
        }

        $codes = [];

        foreach ($scopedValues as $value) {
            $items = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : []);

            foreach ($items as $item) {
                if (is_scalar($item)) {
                    $codes[] = mb_strtolower(trim((string) $item));
                }
            }
        }

        return $codes;
    }

    public function updateSortOrder(array $optionIds, string $direction, int $toIndex, int $attributeId): bool
    {
        $option = $this->where('id', $toIndex)->where('attribute_id', $attributeId)->first();

        if (! $option) {
            return false;
        }

        $baseSortOrder = $option?->sort_order ?? 1;

        $totalSortedOptions = count($optionIds) - 1;

        // Restrict reorder to this attribute's own options so a crafted request can't touch other attributes' sort_order.
        $allowedIds = $this->model->newQuery()
            ->whereIn('id', $optionIds)
            ->where('attribute_id', $attributeId)
            ->pluck('id')
            ->flip();

        try {
            DB::beginTransaction();

            foreach ($optionIds as $index => $optionId) {
                if (! $allowedIds->has($optionId)) {
                    continue;
                }

                Event::dispatch('catalog.attribute.option.update.before', $optionId);

                $sortOrder = $direction === 'down'
                    ? ($baseSortOrder - ($totalSortedOptions - $index))
                    : $baseSortOrder + $index;

                $option = $this->update(['sort_order' => $sortOrder], $optionId);

                Event::dispatch('catalog.attribute.option.update.after', $option);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            report($e);

            return false;
        }

        return true;
    }

    /**
     * @param  int  $optionId
     */
    public function uploadSwatchImage(array $data, $optionId): void
    {
        if (empty($data['swatch_value'])) {
            return;
        }

        if ($data['swatch_value'] instanceof UploadedFile) {
            $mimeType = $data['swatch_value']->getMimeType();

            $path = $data['swatch_value']->store('attribute_option');

            $this->sanitizeSVG($path, $mimeType);

            parent::update([
                'swatch_value' => $path,
            ], $optionId);
        }
    }
}
