<?php

namespace Webkul\AdminApi\ApiDataSource;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Core\Repositories\CurrencyRepository;

class CurrencyDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(protected CurrencyRepository $currencyRepository) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the currency repository.
     */
    public function prepareApiQueryBuilder()
    {
        $this->addFilter('status', ['=']);

        return $this->currencyRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted currency data.
     *
     * @throws \Exception If the paginator data is not in the expected format.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(function ($data) {
            return [
                'code'   => $data['code'],
                'status' => (int) $data['status'],
                'label'  => core()->getCurrencyLabel($data['code'], core()->getCurrentLocale()->code),
            ];
        }, $paginator['data'] ?? []);
    }

    /**
     * Get currency by its code.
     *
     * @param  string  $code  The unique code of the currency.
     * @return array An associative array containing the currency's code, status, and label.
     *
     * @throws ModelNotFoundException If a currency with the given code is not found.
     */
    public function getByCode(string $code)
    {
        $currency = $this->currencyRepository->findOneByField('code', $code);

        if (! $currency) {
            throw new ModelNotFoundException(
                sprintf('Currency with code %s could not be found.', (string) $code)
            );
        }

        return [
            'code'   => $currency->code,
            'status' => (int) $currency->status,
            'label'  => core()->getCurrencyLabel($currency->code, core()->getCurrentLocale()->code),
        ];
    }
}
