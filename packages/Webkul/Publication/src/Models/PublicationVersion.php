<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Publication\Contracts\PublicationVersion as PublicationVersionContract;
use Webkul\Publication\Database\Factories\PublicationVersionFactory;
use Webkul\Publication\Exceptions\ImmutableVersionException;

#[Table(name: 'publication_versions')]
class PublicationVersion extends Model implements PublicationVersionContract
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Columns that may change after a version is written. Everything else is
     * an attested claim and is sealed once published.
     */
    private const MUTABLE_AFTER_PUBLISH = ['is_current', 'updated_at'];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'is_current'   => 'boolean',
            'published_at' => 'datetime',
            'version'      => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $touched = array_diff(array_keys($version->getDirty()), self::MUTABLE_AFTER_PUBLISH);

            if ($touched !== []) {
                throw new ImmutableVersionException(
                    'Published version '.$version->id.' is immutable; attempted to change: '.implode(', ', $touched)
                );
            }
        });

        static::deleting(function (self $version): void {
            throw new ImmutableVersionException('Published version '.$version->id.' cannot be deleted.');
        });
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(LocaleProxy::modelClass());
    }

    public function markSuperseded(): void
    {
        $this->forceFill(['is_current' => false])->save();
    }

    protected static function newFactory(): PublicationVersionFactory
    {
        return PublicationVersionFactory::new();
    }
}
