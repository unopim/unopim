<?php

namespace Webkul\DataTransfer\Helpers\Importers\Channel;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Importer extends AbstractImporter
{
    public const ERROR_CODE_NOT_FOUND_FOR_DELETE = 'code_not_found_to_delete';

    public const ERROR_LOCALE_NOT_FOUND = 'locale_not_found';

    public const ERROR_ROOT_CATEGORY_NOT_FOUND = 'root_category_not_found';

    public const ERROR_CURRENCY_NOT_FOUND = 'currency_not_found';

    public const ERROR_INVALID_LOCALE = 'invalid_locale';

    /**
     * Permanent entity columns
     */
    protected array $validColumnNames = [
        'code',
        'name',
        'root_category',
        'locales',
        'currencies',
        'locale',
    ];

    /**
     * Required columns that must exist in the source headers.
     */
    protected array $permanentAttributes = [
        'code',
        'name',
        'root_category',
        'locales',
        'currencies',
        'locale',
    ];

    /**
     * Error message templates
     */
    protected array $messages = [
        self::ERROR_CODE_NOT_FOUND_FOR_DELETE => 'data_transfer::app.importers.channels.validation.errors.code-not-found-to-delete',
        self::ERROR_LOCALE_NOT_FOUND          => 'data_transfer::app.importers.channels.validation.errors.locale-not-found',
        self::ERROR_ROOT_CATEGORY_NOT_FOUND   => 'data_transfer::app.importers.channels.validation.errors.root-category-not-found',
        self::ERROR_CURRENCY_NOT_FOUND        => 'data_transfer::app.importers.channels.validation.errors.currency-not-found',
        self::ERROR_INVALID_LOCALE            => 'data_transfer::app.importers.channels.validation.errors.invalid-locale',
    ];

    protected array $activeLocales = [];

    protected array $activeCurrencies = [];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $importBatchRepository,
        protected ChannelRepository $channelRepository,
        protected CategoryRepository $categoryRepository,
        protected LocaleRepository $localeRepository,
        protected CurrencyRepository $currencyRepository
    ) {
        parent::__construct($importBatchRepository);

        $this->initLocalesAndCurrencies();
    }

    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    protected function initLocalesAndCurrencies(): void
    {
        $this->activeLocales = $this->localeRepository->getActiveLocales()->pluck('id', 'code')->toArray();
        $this->activeCurrencies = $this->currencyRepository->getActiveCurrencies()->pluck('id', 'code')->toArray();
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        if ($this->import->action == Import::ACTION_DELETE) {
            if (! $this->channelRepository->findOneByField('code', $rowData['code'])) {
                $this->skipRow($rowNumber, self::ERROR_CODE_NOT_FOUND_FOR_DELETE, $rowData['code']);

                return false;
            }

            return true;
        }

        $validator = Validator::make($rowData, [
            'code'          => 'required|string',
            'name'          => 'required|string',
            'locale'        => 'required|string',
            'root_category' => 'required|string',
            'locales'       => 'required|string',
            'currencies'    => 'required|string',
        ]);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }

            return false;
        }

        if (! isset($this->activeLocales[$rowData['locale']])) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_LOCALE, 'locale', trans($this->messages[self::ERROR_INVALID_LOCALE]));
        }

        $category = $this->categoryRepository->findOneByField('code', $rowData['root_category']);
        if (! $category) {
            $this->skipRow($rowNumber, self::ERROR_ROOT_CATEGORY_NOT_FOUND, 'root_category', trans($this->messages[self::ERROR_ROOT_CATEGORY_NOT_FOUND]));
        }

        $locales = array_map('trim', explode(',', $rowData['locales']));
        foreach ($locales as $locale) {
            if (! isset($this->activeLocales[$locale])) {
                $this->skipRow($rowNumber, self::ERROR_LOCALE_NOT_FOUND, 'locales', trans($this->messages[self::ERROR_LOCALE_NOT_FOUND]));

                break;
            }
        }

        $currencies = array_map('trim', explode(',', $rowData['currencies']));
        foreach ($currencies as $currency) {
            if (! isset($this->activeCurrencies[$currency])) {
                $this->skipRow($rowNumber, self::ERROR_CURRENCY_NOT_FOUND, 'currencies', trans($this->messages[self::ERROR_CURRENCY_NOT_FOUND]));

                break;
            }
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->jobTrack->action == Import::ACTION_DELETE) {
            $this->deleteChannelData($batch);
        } else {
            $this->saveChannelData($batch);
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

    protected function deleteChannelData(JobTrackBatchContract $batch): bool
    {
        foreach ($batch->data as $rowData) {
            $channel = $this->channelRepository->findOneByField('code', $rowData['code']);

            if ($channel) {
                try {
                    Event::dispatch('core.channel.delete.before', $channel->id);

                    $this->channelRepository->delete($channel->id);

                    Event::dispatch('core.channel.delete.after', $channel->id);

                    $this->deletedItemsCount++;
                } catch (\Exception $e) {
                    // skip deleting if it fails due to constraint
                }
            }
        }

        return true;
    }

    protected function saveChannelData(JobTrackBatchContract $batch): bool
    {
        $channelsData = [];

        foreach ($batch->data as $rowData) {
            $code = $rowData['code'];

            if (! isset($channelsData[$code])) {
                $channelsData[$code] = [
                    'code'          => $code,
                    'root_category' => $rowData['root_category'],
                    'locales'       => $rowData['locales'],
                    'currencies'    => $rowData['currencies'],
                ];
            }

            $channelsData[$code][$rowData['locale']] = [
                'name' => $rowData['name'],
            ];
        }

        foreach ($channelsData as $code => $data) {
            $channel = $this->channelRepository->findOneByField('code', $code);

            $category = $this->categoryRepository->findOneByField('code', $data['root_category']);
            $categoryId = $category ? $category->id : null;

            if (! $categoryId) {
                continue;
            }

            $localeIds = [];
            foreach (array_map('trim', explode(',', $data['locales'])) as $localeCode) {
                if (isset($this->activeLocales[$localeCode])) {
                    $localeIds[] = $this->activeLocales[$localeCode];
                }
            }

            $currencyIds = [];
            foreach (array_map('trim', explode(',', $data['currencies'])) as $currencyCode) {
                if (isset($this->activeCurrencies[$currencyCode])) {
                    $currencyIds[] = $this->activeCurrencies[$currencyCode];
                }
            }

            $payload = [
                'code'             => $code,
                'root_category_id' => $categoryId,
                'locales'          => $localeIds,
                'currencies'       => $currencyIds,
            ];

            foreach ($this->activeLocales as $localeCode => $id) {
                if (isset($data[$localeCode])) {
                    $payload[$localeCode] = [
                        'name' => $data[$localeCode]['name'],
                    ];
                }
            }

            if ($channel) {
                Event::dispatch('core.channel.update.before', $channel->id);

                $this->channelRepository->update($payload, $channel->id);

                Event::dispatch('core.channel.update.after', $channel->id);

                $this->updatedItemsCount++;
            } else {
                Event::dispatch('core.channel.create.before');

                $channel = $this->channelRepository->create($payload);

                Event::dispatch('core.channel.create.after', $channel);

                $this->createdItemsCount++;
            }
        }

        return true;
    }
}
