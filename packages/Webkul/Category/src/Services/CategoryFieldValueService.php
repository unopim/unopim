<?php

namespace Webkul\Category\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;
use Webkul\Core\Repositories\LocaleRepository;

class CategoryFieldValueService
{
    const CHUNK_SIZE = 200;

    public function __construct(protected LocaleRepository $localeRepository) {}

    public function moveValues(string $code, bool $toPerLocale): void
    {
        $table = (new Category)->getTable();
        $locales = $this->localeRepository->getActiveLocales()->pluck('code')->all();
        $preferred = core()->getRequestedLocaleCode();

        DB::transaction(function () use ($table, $code, $toPerLocale, $locales, $preferred) {
            $this->eachCategory(function (object $category, array $data) use ($table, $code, $toPerLocale, $locales, $preferred) {
                $changed = $toPerLocale
                    ? $this->spreadToLocales($data, $code, $locales)
                    : $this->collapseToCommon($data, $code, $preferred);

                if ($changed === null) {
                    return;
                }

                DB::table($table)
                    ->where('id', $category->id)
                    ->update(['additional_data' => json_encode($changed)]);
            });
        });
    }

    protected function spreadToLocales(array $data, string $code, array $locales): ?array
    {
        $value = $data['common'][$code] ?? null;

        if (! $this->isFilled($value)) {
            return null;
        }

        foreach ($locales as $locale) {
            $data['locale_specific'][$locale][$code] = $value;
        }

        unset($data['common'][$code]);

        return $data;
    }

    protected function collapseToCommon(array $data, string $code, ?string $preferred): ?array
    {
        $localeValues = [];

        foreach ($data['locale_specific'] ?? [] as $locale => $values) {
            if ($this->isFilled($values[$code] ?? null)) {
                $localeValues[$locale] = $values[$code];
            }
        }

        if ($localeValues === []) {
            return null;
        }

        $data['common'][$code] = $localeValues[$preferred] ?? reset($localeValues);

        foreach (array_keys($data['locale_specific'] ?? []) as $locale) {
            unset($data['locale_specific'][$locale][$code]);
        }

        return $data;
    }

    protected function eachCategory(callable $callback): void
    {
        DB::table((new Category)->getTable())
            ->select('id', 'additional_data')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($categories) use ($callback) {
                foreach ($categories as $category) {
                    $decoded = json_decode($category->additional_data ?? '', true);

                    $callback($category, is_array($decoded) ? $decoded : []);
                }
            });
    }

    protected function isFilled(mixed $value): bool
    {
        return is_scalar($value) && (string) $value !== '';
    }
}
