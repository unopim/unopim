<?php

namespace Webkul\HistoryControl\Traits;

use OwenIt\Auditing\Auditable;
use Webkul\Category\Models\CategoryProxy;

/**
 * Trait HistoryTrait
 */
trait HistoryTrait
{
    use Auditable;

    /**
     * Transform the audit data.
     *
     * @param  array  $audit
     */
    public function transformAudit(array $data): array
    {
        if (isset($data['old_values']['root_category_id'])) {
            $data['old_values']['common']['Root Category'] = CategoryProxy::find($data['old_values']['root_category_id'])->name;
            unset($data['old_values']['root_category_id']);
        }

        if (isset($data['new_values']['root_category_id'])) {
            $data['new_values']['common']['Root Category'] = CategoryProxy::find($data['new_values']['root_category_id'])->name;
            unset($data['new_values']['root_category_id']);
        }

        if (isset($this->historyTranslatableFields)) {
            foreach ($this->historyTranslatableFields as $fieldIndex => $fieldLabel) {
                if (isset($data['old_values'][$fieldIndex])) {
                    $data['old_values'][$this->locale][$fieldLabel] = $data['old_values'][$fieldIndex];
                    unset($data['old_values'][$fieldIndex]);
                }

                if (isset($data['new_values'][$fieldIndex])) {
                    $data['new_values'][$this->locale][$fieldLabel] = $data['new_values'][$fieldIndex];
                    unset($data['new_values'][$fieldIndex]);
                }
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function generateTags(): array
    {
        return $this->historyTags;
    }
}
