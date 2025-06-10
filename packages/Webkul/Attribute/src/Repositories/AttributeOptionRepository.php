<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class AttributeOptionRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeOption';
    }

    /**
     * @return \Webkul\Attribute\Contracts\AttributeOption
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

        try {
            DB::beginTransaction();

            foreach ($optionIds as $index => $optionId) {
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
}
