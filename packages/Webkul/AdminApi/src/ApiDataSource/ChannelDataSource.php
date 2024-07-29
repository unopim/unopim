<?php

namespace Webkul\AdminApi\ApiDataSource;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Core\Repositories\ChannelRepository;

class ChannelDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(protected ChannelRepository $channelRepository) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the channel repository.
     */
    public function prepareApiQueryBuilder()
    {
        return $this->channelRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted channel data.
     *
     * @throws \Exception If the paginator data is not in the expected format.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(function ($data) {
            return [
                'code'          => $data['code'],
                'labels'        => $this->getTranslations($data),
                'root_category' => $data['root_category'] ? $data['root_category']['code'] : null,
                'locales'       => $this->getLocales($data),
                'currencies'    => $this->getCurrencies($data),
            ];
        }, $paginator['data'] ?? []);
    }

    /**
     * Get channel by its code.
     *
     * @param  string  $code  The unique code of the channel.
     * @return array An associative array containing the channel's code, status, and label.
     *
     * @throws ModelNotFoundException If a channel with the given code is not found.
     */
    public function getByCode(string $code)
    {
        $this->prepareForSingleData();

        $requestedFilters = [
            'code' => [
                [
                    'operator' => '=',
                    'value'    => $code,
                ],
            ],
        ];

        $this->queryBuilder = $this->processRequestedFilters($requestedFilters);

        $channel = $this->queryBuilder->first()?->toArray();

        if (! $channel) {
            throw new ModelNotFoundException(
                sprintf('Channel with code %s could not be found.', (string) $code)
            );
        }

        return [
            'code'          => $channel['code'],
            'labels'        => $this->getTranslations($channel),
            'root_category' => $channel['root_category'] ? $channel['root_category']['code'] : null,
            'locales'       => $this->getLocales($channel),
            'currencies'    => $this->getCurrencies($channel),
        ];
    }

    /**
     * Get locales of the channel.
     *
     * @param  array  $channel  The channel data from the database.
     * @return array An array of locales associated with the channel.
     */
    public function getLocales(array $channel)
    {
        if (empty($channel['locales'])) {
            return [];
        }

        $locales = array_map(function ($data) {
            return $data['code'];
        }, $channel['locales']);

        return $locales;
    }

    /**
     * Get currencies of the channel.
     *
     * @param  array  $channel  The channel data from the database.
     * @return array An array of currencies associated with the channel.
     */
    public function getCurrencies(array $channel)
    {
        if (empty($channel['currencies'])) {
            return [];
        }

        $currencies = array_map(function ($data) {
            return $data['code'];
        }, $channel['currencies']);

        return $currencies;
    }
}
