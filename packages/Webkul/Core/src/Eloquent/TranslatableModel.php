<?php

namespace Webkul\Core\Eloquent;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Helpers\Locales;

class TranslatableModel extends Model
{
    use Translatable;

    /**
     * Get locales helper.
     */
    protected function getLocalesHelper(): Locales
    {
        return app(Locales::class);
    }

    /**
     * Locale. This method is being overridden to address the
     * performance issues caused by the existing implementation
     * which increases application time.
     */
    protected function locale(): string
    {
        if ($this->isChannelBased()) {
            return core()->getDefaultLocaleCodeFromDefaultChannel();
        } else {
            if ($this->defaultLocale) {
                return $this->defaultLocale;
            }

            return config('translatable.locale') ?: app()->make('translator')->getLocale();
        }
    }

    /**
     * Is channel based.
     */
    protected function isChannelBased(): bool
    {
        return false;
    }

    public function scopeWhereTranslationIn(Builder $query, string $translationField, mixed $value, ?string $locale = null, string $method = 'whereHas'): Builder
    {
        return $query->$method('translations', function (Builder $query) use ($translationField, $value, $locale) {
            $query->whereIn($this->getTranslationsTable().'.'.$translationField, $value);

            if ($locale) {
                $query->whereIn($this->getTranslationsTable().'.'.$this->getLocaleKey(), $locale);
            }
        });
    }

    /**
     * Resolve a translated field for the requested locale, falling back to any
     * locale that has a non-empty value. Returns null when every translation
     * is empty so callers can apply their own placeholder (e.g. "[code]").
     */
    public function getTranslatedValueWithFallback(string $column, ?string $locale = null): ?string
    {
        $locale = $locale ?: core()->getRequestedLocaleCode();

        $value = $this->translate($locale)?->{$column};

        if (! empty($value)) {
            return $value;
        }

        foreach ($this->translations as $translation) {
            if (! empty($translation->{$column})) {
                return $translation->{$column};
            }
        }

        return null;
    }
}
