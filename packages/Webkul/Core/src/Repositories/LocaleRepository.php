<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Contracts\Locale;
use Webkul\Core\Eloquent\Repository;

class LocaleRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Locale::class;
    }

    /**
     * Create.
     *
     * @return mixed
     */
    public function create(array $attributes)
    {
        Event::dispatch('core.locale.create.before');

        $driver = DB::getDriverName();

        switch ($driver) {
            case 'pgsql':
                $sequence = $this->model->getTable().'_id_seq';
                DB::statement("
                    SELECT setval(
                        '{$sequence}',
                        (SELECT COALESCE(MAX(id), 0) + 1 FROM {$this->model->getTable()}),
                        false
                    )
                ");
                break;

            case 'mysql':
            default:
                break;
        }

        $locale = parent::create($attributes);

        Event::dispatch('core.locale.create.after', $locale);

        return $locale;
    }

    /**
     * Update.
     *
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        Event::dispatch('core.locale.update.before', $id);

        $locale = parent::update($attributes, $id);

        Event::dispatch('core.locale.update.after', $locale);

        return $locale;
    }

    /**
     * Delete.
     *
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        Event::dispatch('core.locale.delete.before', $id);

        $locale = parent::find($id);

        $locale->delete($id);

        Storage::delete((string) $locale->logo_path);

        Event::dispatch('core.locale.delete.after', $id);
    }

    /**
     * Fetchs All active locales
     */
    public function getActiveLocales()
    {
        return $this->where('status', 1)->orderBy('code')->get();
    }

    /**
     * Check whether the locale is linked to any channel or user
     */
    public function checkLocaleBeingUsed(int $localeId): bool
    {
        return $this->find($localeId)->isLocaleBeingUsed() ?? false;
    }

    /**
     * This function returns a query builder instance for further manipulation of the Locale model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryBuilder()
    {
        return $this;
    }
}
