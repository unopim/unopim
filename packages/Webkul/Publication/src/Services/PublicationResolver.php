<?php

namespace Webkul\Publication\Services;

use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Models\PublicationVersion;

class PublicationResolver
{
    private const ACCEPT_LANGUAGE_TOKEN_CAP = 10;

    private const LANGUAGE_TAG_PATTERN = '/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$/';

    public function findPublication(string $uuid, string $type): ?Publication
    {
        return PublicationProxy::modelClass()::query()
            ->where('uuid', $uuid)
            ->where('type', $type)
            ->with([
                'channel.locales',
                'versions' => fn ($query) => $query->where('is_current', true)->with('locale'),
            ])
            ->first();
    }

    public function resolveVersion(Publication $publication, ?string $localeCode, ?string $acceptLanguage): ?PublicationVersion
    {
        $currentByLocale = $publication->versions->keyBy(fn (PublicationVersion $version): string => $version->locale->code);

        foreach ($this->localePreference($publication, $localeCode, $acceptLanguage) as $code) {
            if ($currentByLocale->has($code)) {
                return $currentByLocale->get($code);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function localePreference(Publication $publication, ?string $localeCode, ?string $acceptLanguage): array
    {
        $preferred = $localeCode !== null ? [$localeCode] : $this->parseAcceptLanguage($acceptLanguage);

        return array_values(array_unique([
            ...$preferred,
            ...$publication->channel->locales->pluck('code')->all(),
        ]));
    }

    /**
     * Caps the token count, validates each tag against a real BCP-47-ish
     * shape, and honours `q=` weights instead of discarding them — the
     * 2026-07-22 draft did neither, so a malformed or hostile header could
     * not be trusted and preference order silently ignored the client's
     * actual ranking.
     *
     * @return list<string>
     */
    private function parseAcceptLanguage(?string $header): array
    {
        if ($header === null || $header === '') {
            return [];
        }

        $weighted = [];

        foreach (array_slice(explode(',', $header), 0, self::ACCEPT_LANGUAGE_TOKEN_CAP) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            [$tag, $q] = array_pad(explode(';q=', $part, 2), 2, '1.0');
            $tag = trim($tag);

            if (! preg_match(self::LANGUAGE_TAG_PATTERN, $tag)) {
                continue;
            }

            $weighted[] = ['tag' => str_replace('-', '_', $tag), 'q' => (float) $q];
        }

        usort($weighted, fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        return array_column($weighted, 'tag');
    }
}
