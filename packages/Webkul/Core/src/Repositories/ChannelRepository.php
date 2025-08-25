<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
     * @return \Webkul\Core\Contracts\Channel
     */
    public function create(array $data)
    {
        $model = $this->getModel();

        // Normalize numeric fields (Postgres doesn't allow "" for integers)
        foreach ($model->getFillable() as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null; // or 0 if you want default integer
            }
        }

        // Handle translated attributes per locale
        foreach (core()->getAllActiveLocales() as $locale) {
            foreach ($model->translatedAttributes as $attribute) {
                if (isset($data[$attribute])) {
                    $data[$locale->code][$attribute] = $data[$attribute];
                }
            }
        }

        // Ensure sequence is in sync for PostgreSQL
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'pgsql':
                $sequence = $model->getTable() . '_id_seq';
                DB::statement("
                    SELECT setval(
                        '{$sequence}',
                        (SELECT COALESCE(MAX(id), 0) + 1 FROM {$model->getTable()}),
                        false
                    )
                ");
                break;

            case 'mysql':
            default:
                // MySQL auto-increment handles itself
                break;
        }

        // Create channel
        $channel = parent::create($data);

        // Sync relations
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
     * @return \Webkul\Core\Contracts\Channel
     */
    public function update(array $data, $id, $attribute = 'id')
    {
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
     * This function returns a query builder instance for further manipulation of the channel model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
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
}
