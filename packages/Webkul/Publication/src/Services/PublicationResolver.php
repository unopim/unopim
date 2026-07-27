<?php

namespace Webkul\Publication\Services;

use Illuminate\Support\Facades\Log;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Models\PublicationVersion;

class PublicationResolver
{
    private const ACCEPT_LANGUAGE_TOKEN_CAP = 10;

    private const LANGUAGE_TAG_PATTERN = '/^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$/';

    /**
     * The per-channel public-tier kill switch: `general.publication.settings.enabled`.
     * Distinct from the publish-time-only status gate — shared by every public-facing
     * controller (page and asset alike) so a disabled channel takes the whole
     * publication, including its documents, off the air consistently.
     */
    public function isChannelEnabled(Publication $publication): bool
    {
        return (bool) (core()->getConfigData('general.publication.settings.enabled', $publication->channel->code) ?? false);
    }

    /**
     * Finds a publication by uuid/type with its channel locales and current versions eager loaded.
     */
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

    /**
     * Resolves a GTIN to a single canonical publication. A GTIN identifies the
     * product, not the channel, so it is non-unique across publications: the
     * designated passport channel (`general.publication.settings.gs1_passport_channel`)
     * makes the mapping deterministic. When unset, falls back to the lowest
     * channel_id and logs the ambiguity so a multi-channel GTIN never silently
     * resolves to an arbitrary passport.
     */
    public function findByGtin(string $gtin, string $type): ?Publication
    {
        $passportChannel = core()->getConfigData('general.publication.settings.gs1_passport_channel');

        $query = PublicationProxy::modelClass()::query()
            ->where('gtin', $gtin)
            ->where('type', $type)
            ->with([
                'channel.locales',
                'versions' => fn ($query) => $query->where('is_current', true)->with('locale'),
            ]);

        if (! empty($passportChannel)) {
            return $query->whereHas('channel', fn ($channel) => $channel->where('code', $passportChannel))->first();
        }

        $publication = $query->orderBy('channel_id')->first();

        if ($publication !== null) {
            Log::warning('GS1 resolve without designated passport channel', [
                'gtin'       => $gtin,
                'channel_id' => $publication->channel_id,
            ]);
        }

        return $publication;
    }

    /**
     * Resolves the best-match current version by explicit locale, falling back to Accept-Language preference.
     */
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
        // An explicit locale in the URL is authoritative: match it exactly or 404,
        // so a bogus segment can't serve another locale's content under a 200.
        if ($localeCode !== null) {
            return [$localeCode];
        }

        return array_values(array_unique([
            ...$this->parseAcceptLanguage($acceptLanguage),
            ...$publication->channel->locales->pluck('code')->all(),
        ]));
    }

    /**
     * Caps the token count, validates each tag against a BCP-47-ish shape,
     * and honours `q=` weights so a malformed or hostile header can't be
     * trusted and client ranking isn't silently ignored.
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
