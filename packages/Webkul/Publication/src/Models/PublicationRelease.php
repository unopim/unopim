<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Publication\Contracts\PublicationRelease as PublicationReleaseContract;
use Webkul\Publication\Database\Factories\PublicationReleaseFactory;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\User\Models\AdminProxy;

/**
 * One publish moment of a publication, identified across every locale by a
 * per-publication `sequence`.
 *
 * Versions are numbered per locale, so "v3" names a different state in each
 * language. A release names one moment: the state of the passport as of
 * release N is, for every locale, the most recent version minted at or before
 * release N. Releases are immutable for the same reason versions are: a number
 * that has been printed must keep meaning what it meant.
 */
#[Fillable([
    'publication_id',
    'sequence',
    'published_at',
    'published_by_id',
])]
#[Table(name: 'publication_releases')]
class PublicationRelease extends Model implements PublicationReleaseContract
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sequence'     => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $release): void {
            $touched = array_diff(array_keys($release->getDirty()), ['updated_at']);

            if ($touched !== []) {
                throw new ImmutableVersionException(
                    'Release '.$release->id.' is immutable; attempted to change: '.implode(', ', $touched)
                );
            }
        });

        static::deleting(function (self $release): void {
            throw new ImmutableVersionException('Release '.$release->id.' cannot be deleted.');
        });
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'published_by_id');
    }

    public function carrierIssuances(): HasMany
    {
        return $this->hasMany(PublicationCarrierIssuanceProxy::modelClass(), 'release_id');
    }

    /**
     * The versions minted in this release (at most one per locale by construction).
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PublicationVersionProxy::modelClass(), 'release_id');
    }

    /**
     * The state of the publication as of this release: for every locale, the most
     * recent version minted at or before it. Redacted versions are included; how
     * they render is the caller's decision, not a question of which state applied.
     *
     * @return Collection<int, PublicationVersion> keyed by locale_id
     */
    public function versionsAsOf(): Collection
    {
        $versions = $this->publication->versions()
            ->with(['release', 'locale'])
            ->whereHas('release', fn ($release) => $release->where('sequence', '<=', $this->sequence))
            ->get();

        return $versions
            ->sortByDesc(fn (PublicationVersion $version): int => (int) $version->release->sequence)
            ->unique('locale_id')
            ->sortBy('locale_id')
            ->keyBy('locale_id');
    }

    protected static function newFactory(): PublicationReleaseFactory
    {
        return PublicationReleaseFactory::new();
    }
}
