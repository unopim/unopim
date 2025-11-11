<?php

namespace Webkul\Completeness\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\Completeness\Repositories\CompletenessSettingsRepository;

class HandleFamilyAttributeChanges
{
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected CompletenessSettingsRepository $completenessSettingRepository
    ) {}

    /**
     * Handle the event when attributes are changed in a family.
     */
    public function handle(array $added, array $removed, int $familyId)
    {
        $removedAttributes = $removed ?? [];
        $familyId = $familyId ?? null;

        if (empty($removedAttributes) || empty($familyId)) {
            return;
        }

        $removedAttributeIds = $this->attributeRepository->whereIn('code', $removed)->get()->pluck('id');

        $settings = $this->completenessSettingRepository->where('family_id', $familyId)->whereIn('attribute_id', $removedAttributeIds)->count();

        if ($settings) {
            Log::info('Removing completeness settings for removed attributes in family ID: '.$familyId.'.Attribute removed: '.$removedAttributeIds);

            try {
                $deletedCount = $this->completenessSettingRepository->where('family_id', $familyId)->whereIn('attribute_id', $removedAttributeIds)->delete();

                Log::info('Deleted '.$deletedCount.' completeness settings for family ID: '.$familyId);
            } catch (\Exception $e) {
                Log::error($e);
            }

            BulkProductCompletenessJob::dispatch([], $familyId);
        }
    }
}
