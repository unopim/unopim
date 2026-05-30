<?php

namespace Webkul\DataTransfer\Helpers\Importers\AttributeFamily;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\Rules\Code;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Importer extends AbstractImporter
{
    /*
     * -------------------------------------------------------------------------
     * Error code constants
     * -------------------------------------------------------------------------
     */

    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'code_not_found_to_delete';

    public const ERROR_NOT_FOUND_LOCALE = 'locale_not_exist';

    public const ERROR_INVALID_ATTRIBUTE_GROUP = 'invalid_attribute_group';

    public const ERROR_INVALID_ATTRIBUTE = 'invalid_attribute';

    public const ERROR_INVALID_CHANNEL = 'invalid_channel';

    public const ERROR_DUPLICATE_CODE = 'duplicate_code';

    /**
     * All columns accepted from the import file
     */
    protected array $validColumnNames = [
        'code',
        'locale',
        'name',
        'attribute_group',
        'attributes',
        'completeness',
    ];

    /**
     * Columns that must be present on every row
     */
    protected array $permanentAttributes = ['code', 'locale'];

    /**
     * Primary key column name
     */
    protected string $masterAttributeCode = 'id';

    /*
     * -------------------------------------------------------------------------
     * Error message translation keys
     * -------------------------------------------------------------------------
     */

    protected array $messages = [
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.attribute-families.validation.errors.code_not_found_to_delete',
        self::ERROR_NOT_FOUND_LOCALE          => 'data_transfer::app.importers.products.validation.errors.locale-not-exist',
        self::ERROR_INVALID_ATTRIBUTE_GROUP   => 'data_transfer::app.importers.attribute-families.validation.errors.invalid-attribute-group',
        self::ERROR_INVALID_ATTRIBUTE         => 'data_transfer::app.importers.attribute-families.validation.errors.invalid-attribute',
        self::ERROR_INVALID_CHANNEL           => 'data_transfer::app.importers.attribute-families.validation.errors.invalid-channel',
        self::ERROR_DUPLICATE_CODE            => 'data_transfer::app.importers.attribute-families.validation.errors.duplicate-code',
    ];

    /*
     * -------------------------------------------------------------------------
     * Internal caches populated at construction / first use
     * -------------------------------------------------------------------------
     */

    /** @var string[] Active locale codes */
    protected array $locales = [];

    /** @var array<string, int>  attribute_group code → id */
    protected array $attributeGroupCache = [];

    /** @var array<string, int>  attribute code → id */
    protected array $attributeCache = [];

    /** @var array<string, int>  channel code → id */
    protected array $channelCache = [];

    /** @var string[] Family codes seen in the current batch (for duplicate detection) */
    protected array $familyCodesInBatch = [];

    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeRepository $attributeRepository,
        protected ChannelRepository $channelRepository,
        protected Storage $attributeFamilyStorage,
        protected LocaleRepository $localeRepository,
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocales();
        $this->initAttributeGroupCache();
        $this->initAttributeCache();
        $this->initChannelCache();
    }

    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    protected function initLocales(): void
    {
        $this->locales = $this->localeRepository->getActiveLocales()->pluck('code')->toArray();
    }

    protected function initAttributeGroupCache(): void
    {
        $this->attributeGroupCache = $this->attributeGroupRepository
            ->query()
            ->select(['id', 'code'])
            ->get()
            ->pluck('id', 'code')
            ->toArray();
    }

    protected function initAttributeCache(): void
    {
        $this->attributeCache = $this->attributeRepository
            ->query()
            ->select(['id', 'code'])
            ->get()
            ->pluck('id', 'code')
            ->toArray();
    }

    protected function initChannelCache(): void
    {
        $this->channelCache = $this->channelRepository
            ->all(['id', 'code'])
            ->pluck('id', 'code')
            ->toArray();
    }

    public function validateData(): void
    {
        $this->attributeFamilyStorage->init();

        parent::validateData();
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        // Return cached result for rows already examined in this batch
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        /*
         * DELETE action — only requires that the code exists in storage.
         */
        if ($this->import->action === Import::ACTION_DELETE) {
            if (! $this->attributeFamilyStorage->get($rowData['code'] ?? '')) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, 'code');

                return false;
            }

            return true;
        }

        /*
         * INSERT / UPDATE actions
         */

        // Locale must be supplied and must be active
        if (empty($rowData['locale']) || ! in_array($rowData['locale'], $this->locales)) {
            $this->skipRow(
                $rowNumber,
                self::ERROR_NOT_FOUND_LOCALE,
                'locale',
                trans($this->messages[self::ERROR_NOT_FOUND_LOCALE])
            );

            return false;
        }

        $isUpdate = $this->attributeFamilyStorage->has($rowData['code'] ?? '')
            || in_array($rowData['code'] ?? '', $this->familyCodesInBatch);

        // Core field validation (code required; unique check for new families)
        $validator = Validator::make($rowData, [
            'code' => [
                'required',
                'string',
                new Code,
                $isUpdate ? '' : 'unique:attribute_families,code',
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $field => $messages) {
                $errorCode = array_key_first($validator->failed()[$field] ?? []);
                $this->skipRow($rowNumber, $errorCode, $field, current($messages));
            }
        }

        if (! empty($rowData['attribute_group'])
            && ! isset($this->attributeGroupCache[$rowData['attribute_group']])
        ) {
            $this->skipRow(
                $rowNumber,
                self::ERROR_INVALID_ATTRIBUTE_GROUP,
                'attribute_group',
                trans($this->messages[self::ERROR_INVALID_ATTRIBUTE_GROUP])
            );
        }

        // Validate attribute code when provided
        if (! empty($rowData['attributes'])
            && ! isset($this->attributeCache[$rowData['attributes']])
        ) {
            $this->skipRow(
                $rowNumber,
                self::ERROR_INVALID_ATTRIBUTE,
                'attributes',
                trans($this->messages[self::ERROR_INVALID_ATTRIBUTE])
            );
        }

        // Validate completeness channel codes when provided
        if (! empty($rowData['completeness'])) {
            foreach (explode(',', $rowData['completeness']) as $channelCode) {
                $channelCode = trim($channelCode);

                if ($channelCode === '') {
                    continue;
                }

                if (! isset($this->channelCache[$channelCode])) {
                    $this->skipRow(
                        $rowNumber,
                        self::ERROR_INVALID_CHANNEL,
                        'completeness',
                        trans($this->messages[self::ERROR_INVALID_CHANNEL])
                    );
                    break;
                }
            }
        }

        $isValidRow = ! $this->errorHelper->isRowInvalid($rowNumber);

        if ($isValidRow && ! $isUpdate) {
            // Track the code so subsequent rows for the same family in this
            // batch are treated as updates rather than new inserts
            $this->familyCodesInBatch[] = $rowData['code'];
        }

        return $isValidRow;
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action === Import::ACTION_DELETE) {
            $this->deleteAttributeFamilyData($batch);
        } else {
            $this->saveAttributeFamilyData($batch);
        }

        $batch = $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    protected function deleteAttributeFamilyData(JobTrackBatchContract $batch): bool
    {
        $this->attributeFamilyStorage->load(Arr::pluck($batch->data, 'code'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            $id = $this->attributeFamilyStorage->get($rowData['code'] ?? '');

            if (! $id) {
                continue;
            }

            $idsToDelete[] = $id;
        }

        $idsToDelete = array_unique($idsToDelete);
        $this->deletedItemsCount = count($idsToDelete);

        if (! empty($idsToDelete)) {
            $this->attributeFamilyRepository->deleteWhere([['id', 'IN', $idsToDelete]]);
        }

        return true;
    }

    protected function saveAttributeFamilyData(JobTrackBatchContract $batch): bool
    {
        $codes = Arr::pluck($batch->data, 'code');
        $this->attributeFamilyStorage->load($codes);

        $families = [];

        foreach ($batch->data as $rowData) {
            $this->prepareAttributeFamilies($rowData, $families);
        }

        $this->saveAttributeFamilies($families);

        return true;
    }

    public function prepareAttributeFamilies(array $rowData, array &$families): void
    {
        $code = $rowData['code'];
        $locale = $rowData['locale'];

        $isExisting = $this->attributeFamilyStorage->has($code);
        $bucket = $isExisting ? 'update' : 'insert';

        if (! isset($families[$bucket][$code])) {
            $families[$bucket][$code] = [
                'code'             => $code,
                'translations'     => [],
                'attribute_groups' => [],
            ];
        }

        $entry = &$families[$bucket][$code];

        if (! empty($rowData['name'])) {
            $entry['translations'][$locale] = $rowData['name'];
        }

        $groupCode = $rowData['attribute_group'] ?? '';
        $attributeCode = $rowData['attributes'] ?? '';

        if ($groupCode !== '' && $attributeCode !== '') {
            if (! isset($entry['attribute_groups'][$groupCode])) {
                $entry['attribute_groups'][$groupCode] = [
                    'attributes'   => [],
                    'completeness' => [],
                ];
            }

            if (! in_array($attributeCode, $entry['attribute_groups'][$groupCode]['attributes'])) {
                $entry['attribute_groups'][$groupCode]['attributes'][] = $attributeCode;
            }

            if (! empty($rowData['completeness'])) {
                $channelCodes = array_filter(
                    array_map('trim', explode(',', $rowData['completeness']))
                );

                $existing = $entry['attribute_groups'][$groupCode]['completeness'][$attributeCode] ?? [];

                $entry['attribute_groups'][$groupCode]['completeness'][$attributeCode] = array_unique(
                    array_merge($existing, $channelCodes)
                );
            }
        }

        $families[$bucket][$code] = $entry;
    }

    public function saveAttributeFamilies(array $families): void
    {
        /*
         * Inserts
         */
        if (! empty($families['insert'])) {
            $this->createdItemsCount += count($families['insert']);

            foreach ($families['insert'] as $code => $familyData) {
                $createData = $this->buildRepositoryPayload($familyData);

                $newFamily = $this->attributeFamilyRepository->create($createData);

                if ($newFamily) {
                    $this->attributeFamilyStorage->set($code, $newFamily->id);
                    $this->syncCompletenessSettings($newFamily->id, $familyData['attribute_groups']);
                }
            }
        }

        if (! empty($families['update'])) {
            $this->updatedItemsCount += count($families['update']);

            foreach ($families['update'] as $code => $familyData) {
                $familyId = $this->attributeFamilyStorage->get($code);
                $updateData = $this->buildRepositoryPayload($familyData, forUpdate: true);

                $this->attributeFamilyRepository->update($updateData, $familyId);
                $this->syncCompletenessSettings($familyId, $familyData['attribute_groups']);
            }
        }
    }

    /*
     * -------------------------------------------------------------------------
     * Repository payload builder
     * -------------------------------------------------------------------------
     */

    /**
     * Convert the internal $familyData structure into the shape expected by
     * AttributeFamilyRepository::create() or ::update().
     *
     * create() expects:
     *   attribute_groups[$groupId]['position']         = int
     *   attribute_groups[$groupId]['custom_attributes'] = [['id' => int], ...]
     *
     * update() additionally needs:
     *   attribute_groups[$groupId]['attribute_groups_mapping'] = ''
     *   (empty string → the repository treats it as a new mapping and removes
     *    the old ones at the end of its loop)
     */
    protected function buildRepositoryPayload(array $familyData, bool $forUpdate = false): array
    {
        $payload = ['code' => $familyData['code']];

        // Translatable name fields — one key per locale (e.g. 'en' => 'Default')
        foreach ($familyData['translations'] as $locale => $name) {
            $payload[$locale] = ['name' => $name];
        }

        // Attribute group structure
        $groupPosition = 1;

        foreach ($familyData['attribute_groups'] as $groupCode => $groupData) {
            $groupId = $this->attributeGroupCache[$groupCode] ?? null;

            if (! $groupId) {
                continue;
            }

            $customAttributes = [];
            $attrPosition = 1;

            foreach ($groupData['attributes'] as $attributeCode) {
                $attributeId = $this->attributeCache[$attributeCode] ?? null;

                if (! $attributeId) {
                    continue;
                }

                $customAttributes[] = [
                    'id'       => $attributeId,
                    'position' => $attrPosition++,
                ];
            }

            $groupEntry = [
                'position'          => $groupPosition++,
                'custom_attributes' => $customAttributes,
            ];

            if ($forUpdate) {
                // Empty string signals the repository to create a new mapping
                // and discard the previous one for this family
                $groupEntry['attribute_groups_mapping'] = '';
            }

            $payload['attribute_groups'][$groupId] = $groupEntry;
        }

        return $payload;
    }

    /*
     * -------------------------------------------------------------------------
     * Completeness settings sync
     * -------------------------------------------------------------------------
     */

    /**
     * Synchronise the completeness_settings table for the given family.
     *
     * For each (attribute, channel) pair declared in the CSV the method
     * ensures a row exists in completeness_settings.  Rows that are no longer
     * referenced by the import are removed.
     *
     * @param  array<string, array{completeness: array}>  $attributeGroups
     */
    protected function syncCompletenessSettings(int $familyId, array $attributeGroups): void
    {
        // Collect all (attribute_id, channel_id) pairs from this import
        $incoming = [];

        foreach ($attributeGroups as $groupData) {
            foreach ($groupData['completeness'] ?? [] as $attributeCode => $channelCodes) {
                $attributeId = $this->attributeCache[$attributeCode] ?? null;

                if (! $attributeId) {
                    continue;
                }

                foreach ($channelCodes as $channelCode) {
                    $channelId = $this->channelCache[$channelCode] ?? null;

                    if (! $channelId) {
                        continue;
                    }

                    $incoming[] = [
                        'family_id'    => $familyId,
                        'attribute_id' => $attributeId,
                        'channel_id'   => $channelId,
                    ];
                }
            }
        }

        // Remove all existing completeness rows for this family and re-insert
        DB::table('completeness_settings')
            ->where('family_id', $familyId)
            ->delete();

        if (! empty($incoming)) {
            // Deduplicate before inserting
            $unique = array_unique(array_map('serialize', $incoming));
            $rows = array_map('unserialize', $unique);

            DB::table('completeness_settings')->insert($rows);
        }
    }

    /*
     * -------------------------------------------------------------------------
     * Public helpers
     * -------------------------------------------------------------------------
     */

    /**
     * Return true when the family code is present in the storage cache
     */
    public function isAttributeFamilyExist(string $code): bool
    {
        return $this->attributeFamilyStorage->has($code);
    }
}
