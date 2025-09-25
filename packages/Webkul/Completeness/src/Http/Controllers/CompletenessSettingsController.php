<?php

namespace Webkul\Completeness\Http\Controllers;

use Illuminate\Routing\Controller;
use Webkul\Completeness\DataGrids\AttributeCompletenessDataGrid;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\Completeness\Repositories\CompletenessSettingsRepository;
use Webkul\Core\Repositories\ChannelRepository;

class CompletenessSettingsController extends Controller
{
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CompletenessSettingsRepository $completenessSettingsRepository
    ) {}

    public function edit($familyId)
    {
        return app(AttributeCompletenessDataGrid::class)->setAttributeFamilyId($familyId)->toJson();
    }

    public function update()
    {
        $data = request()->only(['channel_requirements', 'familyId', 'attributeId']);

        $familyId = (int) $data['familyId'];
        $attributeId = (int) $data['attributeId'];

        $newCodes = array_filter(explode(',', $data['channel_requirements'] ?? ''));

        $existingCodes = $this->completenessSettingsRepository->findWhere([
            'family_id'    => $familyId,
            'attribute_id' => $attributeId,
        ])->pluck('channel.code')->all();

        $toInsert = array_diff($newCodes, $existingCodes);
        $toDelete = array_diff($existingCodes, $newCodes);

        if (! empty($toInsert)) {
            $channels = $this->channelRepository->findWhereIn('code', $toInsert);

            foreach ($channels as $channel) {
                $this->completenessSettingsRepository->create([
                    'family_id'    => $familyId,
                    'attribute_id' => $attributeId,
                    'channel_id'   => $channel->id,
                ]);
            }
        }

        if (! empty($toDelete)) {
            $channels = $this->channelRepository->findWhereIn('code', $toDelete);

            foreach ($channels as $channel) {
                $this->completenessSettingsRepository->deleteWhere([
                    'family_id'    => $familyId,
                    'attribute_id' => $attributeId,
                    'channel_id'   => $channel->id,
                ]);
            }
        }

        if (! empty($toDelete) || ! empty($toInsert)) {
            BulkProductCompletenessJob::dispatch([], $familyId);
        }

        return response()->json([
            'success' => true,
            'message' => trans('completeness::app.catalog.families.edit.completeness.update-success'),
        ]);
    }

    public function massUpdate()
    {
        $data = request()->only(['channel_requirements', 'indices', 'familyId']);

        $familyId = (int) $data['familyId'];
        $attributeIds = $data['indices'] ?? [];

        $newCodes = array_filter(explode(',', $data['channel_requirements'] ?? ''));

        $hasChanged = false;

        foreach ($attributeIds as $attributeId) {
            $existingCodes = $this->completenessSettingsRepository->findWhere([
                'family_id'    => $familyId,
                'attribute_id' => $attributeId,
            ])->pluck('channel.code')->all();

            $toInsert = array_diff($newCodes, $existingCodes);
            $toDelete = array_diff($existingCodes, $newCodes);

            if (! empty($toInsert)) {
                $channels = $this->channelRepository->findWhereIn('code', $toInsert);

                foreach ($channels as $channel) {
                    $this->completenessSettingsRepository->create([
                        'family_id'    => $familyId,
                        'attribute_id' => $attributeId,
                        'channel_id'   => $channel->id,
                    ]);
                }
            }

            if (! empty($toDelete)) {
                $channels = $this->channelRepository->findWhereIn('code', $toDelete);

                foreach ($channels as $channel) {
                    $this->completenessSettingsRepository->deleteWhere([
                        'family_id'    => $familyId,
                        'attribute_id' => $attributeId,
                        'channel_id'   => $channel->id,
                    ]);
                }
            }

            if (! empty($toDelete) || ! empty($toInsert)) {
                $hasChanged = true;
            }
        }

        if ($hasChanged) {
            BulkProductCompletenessJob::dispatch([], $familyId);
        }

        return response()->json([
            'success' => true,
            'message' => trans('completeness::app.catalog.families.edit.completeness.mass-update-success'),
        ]);
    }
}
