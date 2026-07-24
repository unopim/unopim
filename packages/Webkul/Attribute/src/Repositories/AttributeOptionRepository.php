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

    public function updateSortOrder(array $optionIds, string $direction, int $toIndex, int $attributeId): bool
    {
        $option = $this->where('id', $toIndex)->where('attribute_id', $attributeId)->first();

        if (! $option) {
            return false;
        }

        $baseSortOrder = $option?->sort_order ?? 1;

        $totalSortedOptions = count($optionIds) - 1;

        // Only reorder options that belong to this attribute, so a crafted request
        // cannot rewrite sort_order on options of other attributes.
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
