<?php

namespace Webkul\Core\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Contracts\Channel;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Services\ChannelActivationSyncer;

class ChannelRepository extends Repository
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected ChannelActivationSyncer $channelActivationSyncer,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Channel::class;
    }

    /**
     * Create.
     *
     * @return Channel
     */
    public function create(array $data)
    {
        $model = $this->getModel();

        foreach ($model->getFillable() as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $data = $this->removeEmptyTranslations($data, $model);

        foreach (core()->getAllActiveLocales() as $locale) {
            $localeCode = $locale->code;

            foreach ($model->translatedAttributes as $attribute) {
                if (isset($data[$attribute])) {
                    $data[$localeCode][$attribute] = $data[$attribute];
                }
            }

            if (isset($data[$localeCode])) {
                $allEmpty = true;

                foreach ($model->translatedAttributes as $field) {
                    if (! empty($data[$localeCode][$field])) {
                        $allEmpty = false;

                        break;
                    }
                }

                if ($allEmpty) {
                    unset($data[$localeCode]);
                }
            }
        }

        $channel = parent::create($data);

        if (isset($data['locales'])) {
            $synced = $channel->locales()->sync($data['locales']);

            $this->channelActivationSyncer->syncLocales($synced['attached'], []);
        }

        if (isset($data['currencies'])) {
            $synced = $channel->currencies()->sync($data['currencies']);

            $this->channelActivationSyncer->syncCurrencies($synced['attached'], []);
        }

        return $channel;
    }

    /**
     * Update.
     *
     * @param  int  $id
     * @return Channel
     */
    public function update(array $data, $id)
    {
        $model = $this->getModel();

        $data = $this->removeEmptyTranslations($data, $model);

        $channel = parent::update($data, $id);

        $oldLocales = $channel->locales()->pluck('code')->toArray();

        $syncedLocales = $channel->locales()->sync($data['locales']);

        $newLocales = $channel->locales()->pluck('code')->toArray();

        Event::dispatch('core.model.proxy.sync.locales', ['old_values' => $oldLocales, 'new_values' => $newLocales, 'model' => $channel]);

        $this->channelActivationSyncer->syncLocales($syncedLocales['attached'], $syncedLocales['detached']);

        $oldCurrencies = $channel->currencies()->pluck('code')->toArray();

        $syncedCurrencies = $channel->currencies()->sync($data['currencies']);

        $newCurrencies = $channel->currencies()->pluck('code')->toArray();

        Event::dispatch('core.model.proxy.sync.currencies', ['old_values' => $oldCurrencies, 'new_values' => $newCurrencies, 'model' => $channel]);

        $this->channelActivationSyncer->syncCurrencies($syncedCurrencies['attached'], $syncedCurrencies['detached']);

        return $channel;
    }

    /**
     * Delete.
     *
     * @param  int  $id
     * @return mixed
     */
    public function delete($id)
    {
        $channel = $this->find($id);

        $localeIds = $channel?->locales()->pluck('locales.id')->toArray() ?? [];
        $currencyIds = $channel?->currencies()->pluck('currencies.id')->toArray() ?? [];

        $deleted = parent::delete($id);

        $this->channelActivationSyncer->syncLocales([], $localeIds);

        $this->channelActivationSyncer->syncCurrencies([], $currencyIds);

        return $deleted;
    }

    /**
     * Remove locale translation payloads when every translated attribute is empty.
     */
    protected function removeEmptyTranslations(array $data, $model): array
    {
        foreach (core()->getAllActiveLocales() as $locale) {
            $localeCode = $locale->code;
            if (! isset($data[$localeCode])) {
                continue;
            }
            if (! is_array($data[$localeCode])) {
                continue;
            }

            $allEmpty = true;

            foreach ($model->translatedAttributes as $field) {
                $value = $data[$localeCode][$field] ?? null;

                if ($value !== null && $value !== '') {
                    $allEmpty = false;

                    break;
                }
            }

            if ($allEmpty) {
                unset($data[$localeCode]);
            }
        }

        return $data;
    }

    /**
     * This function returns a query builder instance for further manipulation of the channel model.
     *
     * @return Builder
     */
    public function queryBuilder()
    {
        return $this->with([
            'translations',
            'locales',
            'currencies',
            'root_category',
        ]);
    }

    public function getChannelAsOptions(): Collection
    {
        return $this->all()->map(function ($channel): array {
            $channelLabel = $channel->name;

            return [
                'code'  => $channel->code,
                'label' => empty($channelLabel) ? "[$channel->code]" : $channelLabel,
            ];
        });
    }
}
