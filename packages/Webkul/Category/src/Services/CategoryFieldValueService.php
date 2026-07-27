<?php

namespace Webkul\Category\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;
use Webkul\Core\Repositories\LocaleRepository;

/**
 * Reads and rewrites the category values a field owns inside `categories.additional_data`.
 *
 * A value lives under `common.<code>` while the field is not per-locale and under
 * `locale_specific.<locale>.<code>` once it is. The JSON is decoded and written back
 * whole in PHP, so no driver-specific JSON SQL is involved.
 */
class CategoryFieldValueService
{
    const CHUNK_SIZE = 200;

    public function __construct(protected LocaleRepository $localeRepository) {}

    /**
     * Move stored values across when `value_per_locale` is toggled, so they stay
     * readable instead of being stranded under the previous path.
     *
     * Collapsing to a single value keeps the requested locale's value and drops the
     * rest — that loss is intrinsic to the direction.
     */
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

    /**
     * Copy the shared value into every active locale. Replication keeps what each
     * locale already displayed, since one value was shown for all of them.
     *
     * @return array<string, mixed>|null null when nothing needed moving
     */
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

    /**
     * @return array<string, mixed>|null null when nothing needed moving
     */
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

    /**
     * Walks every category with its decoded `additional_data`.
     */
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
