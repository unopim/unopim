<?php

namespace Webkul\DataTransfer\Helpers\Exporters\AttributeFamily;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends AbstractExporter
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected AttributeFamilyRepository $attributeFamilyRepository,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the channels and locales for the export process.
     *
     * @return void
     */
    public function initilize()
    {
        $this->initializeFileBuffer();
    }

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();
        $attributeFamilies = $this->prepareAttributeFamilies($batch, $filePath);

        $this->exportBuffer->write($attributeFamilies);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        return $this->source->all()?->getIterator();
    }

    /**
     * Prepare attribute families from current batch
     */
    public function prepareAttributeFamilies(JobTrackBatchContract $batch, mixed $filePath)
    {
        $locales = core()->getAllActiveLocales()->pluck('code');
        $attributeFamilies = [];

        $familyIds = collect($batch->data)->pluck('id')->toArray();

        // Get all attributes belonging to families, along with their group code
        $attributes = DB::table('attribute_family_group_mappings')
            ->join('attribute_group_mappings', 'attribute_family_group_mappings.id', '=', 'attribute_group_mappings.attribute_family_group_id')
            ->join('attributes', 'attribute_group_mappings.attribute_id', '=', 'attributes.id')
            ->join('attribute_groups', 'attribute_family_group_mappings.attribute_group_id', '=', 'attribute_groups.id')
            ->whereIn('attribute_family_group_mappings.attribute_family_id', $familyIds)
            ->select(
                'attribute_family_group_mappings.attribute_family_id',
                'attribute_groups.code as group_code',
                'attributes.code as attribute_code'
            )
            ->get()
            ->groupBy('attribute_family_id');

        $completenessSettings = DB::table('completeness_settings')
            ->join('channels', 'completeness_settings.channel_id', '=', 'channels.id')
            ->join('attributes', 'completeness_settings.attribute_id', '=', 'attributes.id')
            ->whereIn('completeness_settings.family_id', $familyIds)
            ->select('completeness_settings.family_id', 'channels.code as channel_code', 'attributes.code as attribute_code')
            ->get();

        $groupedCompleteness = $completenessSettings->groupBy(function ($item) {
            return $item->family_id.'_'.$item->attribute_code;
        });

        foreach ($batch->data as $rowData) {
            $translations = collect($rowData['translations'] ?? [])->keyBy('locale')->toArray();
            $familyId = $rowData['id'] ?? null;

            $familyAttrs = $familyId ? $attributes->get($familyId, collect()) : collect();

            if ($familyAttrs->isEmpty()) {
                foreach ($locales as $locale) {
                    $data = [
                        'code'             => $rowData['code'] ?? null,
                        'locale'           => $locale,
                        'name'             => $translations[$locale]['name'] ?? null,
                        'attribute_group'  => '',
                        'attributes'       => '',
                        'completeness'     => '',
                    ];

                    $attributeFamilies[] = $data;
                }
            } else {
                foreach ($familyAttrs as $familyAttr) {
                    $compStr = $groupedCompleteness->get($familyId.'_'.$familyAttr->attribute_code, collect())
                        ->pluck('channel_code')
                        ->unique()
                        ->implode(',');

                    foreach ($locales as $locale) {
                        $data = [
                            'code'             => $rowData['code'] ?? null,
                            'locale'           => $locale,
                            'name'             => $translations[$locale]['name'] ?? null,
                            'attribute_group'  => $familyAttr->group_code,
                            'attributes'       => $familyAttr->attribute_code,
                            'completeness'     => $compStr,
                        ];

                        $attributeFamilies[] = $data;
                    }
                }
            }

            $this->createdItemsCount++;
        }

        return $attributeFamilies;
    }
}
