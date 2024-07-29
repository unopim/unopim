<?php

namespace Webkul\AdminApi\ApiDataSource;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Core\Repositories\LocaleRepository;

class LocaleDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(protected LocaleRepository $localeRepository) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the locale repository.
     */
    public function prepareApiQueryBuilder()
    {
        $this->addFilter('status', ['=']);

        return $this->localeRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted locale data.
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
            ];
        }, $paginator['data'] ?? []);
    }

    /**
     * Get locale by its code.
     *
     * @param  string  $code  The unique code of the locale.
     * @return array An associative array containing the locale's code, status, and label.
     *
     * @throws ModelNotFoundException If a locale with the given code is not found.
     */
    public function getByCode(string $code)
    {
        $locale = $this->localeRepository->findOneByField('code', $code);

        if (! $locale) {
            throw new ModelNotFoundException(
                sprintf('Locale with code %s could not be found.', (string) $code)
            );
        }

        return [
            'code'   => $locale->code,
            'status' => (int) $locale->status,
        ];
    }
}
