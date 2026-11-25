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
     *
     * A text match narrows the candidates portably across database engines; the decoded values
     * then confirm the code belongs to this attribute, as a single value, a list or a comma-separated string.
     */
    public function isUsedByProducts(AttributeOption $option): bool
    {
        $attributeCode = $option->attribute->code;

        $optionCode = mb_strtolower($option->code);

        $valuesColumn = DB::rawQueryGrammar()->castToText(DB::getQueryGrammar()->wrap('values'));

        return DB::table('products')
            ->select('id', 'values')
            ->whereRaw("LOWER({$valuesColumn}) LIKE ?", ['%'.$optionCode.'%'])
            ->lazyById(200)
            ->contains(fn (object $product): bool => in_array(
                $optionCode,
                $this->productOptionCodes(json_decode((string) $product->values, true) ?? [], $attributeCode),
                true,
            ));
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
