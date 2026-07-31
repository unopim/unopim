<?php

namespace Webkul\Completeness\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Completeness\DataGrids\AttributeCompletenessDataGrid;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\Completeness\Repositories\CompletenessSettingsRepository;
use Webkul\Core\Repositories\ChannelRepository;

class CompletenessSettingsController extends Controller
{
    /**
     * Sync event the history listener turns into an attribute-family audit entry.
     */
    const HISTORY_EVENT = 'core.model.proxy.sync.completeness';

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CompletenessSettingsRepository $completenessSettingsRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    /**
     * Record a completeness change against the family it belongs to.
     *
     * Settings are stored one row per channel, so a save is reported as the
     * channel list an attribute required before and after rather than as a
     * row-level insert/delete the history drawer cannot render.
     *
     * @param  array<int, array{attribute_id: int, old: array<int, string>, new: array<int, string>}>  $changes
     */
    protected function recordHistory(int $familyId, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $family = $this->attributeFamilyRepository->find($familyId);

        if (! $family) {
            return;
        }

        $codesById = $this->attributeRepository
            ->findWhereIn('id', array_column($changes, 'attribute_id'))
            ->pluck('code', 'id');

        $oldValues = [];
        $newValues = [];

        foreach ($changes as $change) {
            $code = $codesById->get($change['attribute_id']);

            if (! $code) {
                continue;
            }

            $oldValues[$code] = implode(', ', $change['old']);
            $newValues[$code] = implode(', ', $change['new']);
        }

        if ($oldValues === $newValues) {
            return;
        }

        Event::dispatch(self::HISTORY_EVENT, [
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'model'      => $family,
        ]);
    }

    public function edit($familyId)
    {
        return resolve(AttributeCompletenessDataGrid::class)->setAttributeFamilyId($familyId)->toJson();
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

        if ($toInsert !== []) {
            $channels = $this->channelRepository->findWhereIn('code', $toInsert);

            foreach ($channels as $channel) {
                $this->completenessSettingsRepository->create([
                    'family_id'    => $familyId,
                    'attribute_id' => $attributeId,
                    'channel_id'   => $channel->id,
                ]);
            }
        }

        if ($toDelete !== []) {
            $channels = $this->channelRepository->findWhereIn('code', $toDelete);

            foreach ($channels as $channel) {
                $this->completenessSettingsRepository->deleteWhere([
                    'family_id'    => $familyId,
                    'attribute_id' => $attributeId,
                    'channel_id'   => $channel->id,
                ]);
            }
        }

        if ($toDelete !== [] || $toInsert !== []) {
            $this->recordHistory($familyId, [[
                'attribute_id' => $attributeId,
                'old'          => $existingCodes,
                'new'          => $newCodes,
            ]]);

            dispatch(new BulkProductCompletenessJob([], $familyId, auth()->guard('admin')->id()));
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
        $changes = [];

        foreach ($attributeIds as $attributeId) {
            $existingCodes = $this->completenessSettingsRepository->findWhere([
                'family_id'    => $familyId,
                'attribute_id' => $attributeId,
            ])->pluck('channel.code')->all();

            $toInsert = array_diff($newCodes, $existingCodes);
            $toDelete = array_diff($existingCodes, $newCodes);

            if ($toInsert !== []) {
                $channels = $this->channelRepository->findWhereIn('code', $toInsert);

                foreach ($channels as $channel) {
                    $this->completenessSettingsRepository->create([
                        'family_id'    => $familyId,
                        'attribute_id' => $attributeId,
                        'channel_id'   => $channel->id,
                    ]);
                }
            }

            if ($toDelete !== []) {
                $channels = $this->channelRepository->findWhereIn('code', $toDelete);

                foreach ($channels as $channel) {
                    $this->completenessSettingsRepository->deleteWhere([
                        'family_id'    => $familyId,
                        'attribute_id' => $attributeId,
                        'channel_id'   => $channel->id,
                    ]);
                }
            }

            if ($toDelete !== [] || $toInsert !== []) {
                $hasChanged = true;

                $changes[] = [
                    'attribute_id' => (int) $attributeId,
                    'old'          => $existingCodes,
                    'new'          => $newCodes,
                ];
            }
        }

        if ($hasChanged) {
            $this->recordHistory($familyId, $changes);

            dispatch(new BulkProductCompletenessJob([], $familyId, auth()->guard('admin')->id()));
        }

        return response()->json([
            'success' => true,
            'message' => trans('completeness::app.catalog.families.edit.completeness.mass-update-success'),
        ]);
    }
}
