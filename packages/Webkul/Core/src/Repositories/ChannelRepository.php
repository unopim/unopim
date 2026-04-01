<?php

namespace Webkul\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Contracts\Channel;
use Webkul\Core\Eloquent\Repository;

class ChannelRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Core\Contracts\Channel';
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
            $channel->locales()->sync($data['locales']);
        }

        if (isset($data['currencies'])) {
            $channel->currencies()->sync($data['currencies']);
        }

        return $channel;
    }

    /**
     * Update.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return Channel
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $model = $this->getModel();

        $data = $this->removeEmptyTranslations($data, $model);

        $channel = parent::update($data, $id, $attribute);

        // Sync the Channel Locales
        $oldLocales = $channel->locales()->pluck('code')->toArray();

        $channel->locales()->sync($data['locales']);

        $newLocales = $channel->locales()->pluck('code')->toArray();

        Event::dispatch('core.model.proxy.sync.locales', ['old_values' => $oldLocales, 'new_values' => $newLocales, 'model' => $channel]);

        // Sync the Channel Currencies
        $oldCurrencies = $channel->currencies()->pluck('code')->toArray();

        $channel->currencies()->sync($data['currencies']);

        $newCurrencies = $channel->currencies()->pluck('code')->toArray();

        Event::dispatch('core.model.proxy.sync.currencies', ['old_values' => $oldCurrencies, 'new_values' => $newCurrencies, 'model' => $channel]);

        return $channel;
    }

    /**
     * Remove locale translation payloads when every translated attribute is empty.
     */
    protected function removeEmptyTranslations(array $data, $model): array
    {
        foreach (core()->getAllActiveLocales() as $locale) {
            $localeCode = $locale->code;

            if (! isset($data[$localeCode]) || ! is_array($data[$localeCode])) {
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
        return $this->all()->map(function ($channel) {
            $channelLabel = $channel->name;

            return [
                'code'  => $channel->code,
                'label' => empty($channelLabel) ? "[$channel->code]" : $channelLabel,
            ];
        });
    }
}
