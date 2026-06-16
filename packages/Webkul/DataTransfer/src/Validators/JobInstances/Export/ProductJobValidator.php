<?php

namespace Webkul\DataTransfer\Validators\JobInstances\Export;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Enums\CompletenessCondition;
use Webkul\DataTransfer\Enums\ProductExportScope;
use Webkul\DataTransfer\Enums\ProductStatusFilter;
use Webkul\DataTransfer\Enums\TimeCondition;
use Webkul\DataTransfer\Helpers\Formatters\ScopeFilterValue;
use Webkul\DataTransfer\Validators\JobInstances\Default\JobValidator;

class ProductJobValidator extends JobValidator
{
    /**
     * Stores validation rules for data
     */
    protected array $rules = [
        'filters.file_format' => 'required',
        'filters.with_media'  => 'in:1,0',
        'filters.header_row'  => 'nullable|in:1,0',
        'filters.use_labels'  => 'nullable|in:1,0',
        'filters.date_format' => 'nullable|in:Y-m-d,d-m-Y,d/m/Y,m/d/Y',
        'filters.file_path'   => 'nullable|string|max:255',
        'filters.sku'         => 'nullable|string',
    ];

    /**
     * Create a new validator instance.
     */
    public function __construct(protected ChannelRepository $channelRepository)
    {
        $this->rules['filters.status'] = 'nullable|in:'.implode(',', ProductStatusFilter::values());
        $this->rules['filters.completeness'] = 'nullable|in:'.implode(',', CompletenessCondition::values());
        $this->rules['filters.time_condition'] = 'nullable|in:'.implode(',', TimeCondition::values());
        $this->rules['filters.time_value'] = 'nullable|integer|min:1|required_if:filters.time_condition,'.TimeCondition::LAST_N_DAYS->value;
        $this->rules['filters.time_date'] = 'nullable|date|required_if:filters.time_condition,'.TimeCondition::BETWEEN_DATES->value;
        $this->rules['filters.time_date_end'] = 'nullable|date|after_or_equal:filters.time_date|required_if:filters.time_condition,'.TimeCondition::BETWEEN_DATES->value;

        $this->attributeNames = [
            'filters.file_format'    => trans('data_transfer::app.exporters.fields.file-format'),
            'filters.with_media'     => trans('data_transfer::app.exporters.fields.with-media'),
            'filters.header_row'     => trans('data_transfer::app.exporters.fields.header-row'),
            'filters.use_labels'     => trans('data_transfer::app.exporters.fields.use-labels'),
            'filters.date_format'    => trans('data_transfer::app.exporters.fields.date-format'),
            'filters.file_path'      => trans('data_transfer::app.exporters.fields.file-path'),
            'filters.status'         => trans('data_transfer::app.exporters.fields.status'),
            'filters.sku'            => trans('data_transfer::app.exporters.products.filters.identifiers'),
            'filters.completeness'   => trans('data_transfer::app.exporters.products.filters.completeness'),
            'filters.time_condition' => trans('data_transfer::app.exporters.products.filters.time-condition'),
            'filters.time_value'     => trans('data_transfer::app.exporters.products.filters.time-value'),
            'filters.time_date'      => trans('data_transfer::app.exporters.products.filters.time-date'),
            'filters.time_date_end'  => trans('data_transfer::app.exporters.products.filters.time-date-end'),
        ];
    }

    /**
     * Validates the job data and the channel dependent scope filters.
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $options = []): void
    {
        parent::validate($data, $options);

        $this->validateScopeFilters($data['filters'] ?? []);
    }

    /**
     * Ensures the selected locales and currencies belong to the selected
     * channels. When no channel is selected every option is allowed, matching
     * the export's full-scope fallback behaviour.
     *
     * @throws ValidationException
     */
    protected function validateScopeFilters(array $filters): void
    {
        $channelCodes = ScopeFilterValue::toCodes($filters[ProductExportScope::CHANNELS->value] ?? null);

        if (empty($channelCodes)) {
            return;
        }

        $channels = $this->channelRepository->with(['locales', 'currencies'])->findWhereIn('code', $channelCodes);

        $messages = array_merge(
            $this->scopeViolation(
                ProductExportScope::LOCALES,
                $filters,
                $this->relationCodes($channels, 'locales'),
                'data_transfer::app.exporters.products.invalid-locales'
            ),
            $this->scopeViolation(
                ProductExportScope::CURRENCIES,
                $filters,
                $this->relationCodes($channels, 'currencies'),
                'data_transfer::app.exporters.products.invalid-currencies'
            )
        );

        if (empty($messages)) {
            return;
        }

        throw ValidationException::withMessages($messages);
    }

    /**
     * Builds an error message entry when the selected values for a scope fall
     * outside the allowed codes.
     */
    protected function scopeViolation(ProductExportScope $scope, array $filters, array $allowedCodes, string $translationKey): array
    {
        $selected = ScopeFilterValue::toCodes($filters[$scope->value] ?? null);

        if (! array_diff($selected, $allowedCodes)) {
            return [];
        }

        return ["filters[{$scope->value}]" => [trans($translationKey)]];
    }

    /**
     * Distinct codes of the given relation across the channels.
     */
    protected function relationCodes(Collection $channels, string $relation): array
    {
        return $channels->flatMap(fn ($channel) => $channel->{$relation}->pluck('code'))->unique()->all();
    }
}
