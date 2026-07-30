<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Webkul\Admin\Mail\Admin\ResetPasswordNotification;
use Webkul\AdminApi\Models\Apikey;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\LocaleProxy;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\BooleanPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Notification\Models\UserNotification;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Database\Factories\AdminFactory;
use Webkul\User\Jobs\RefreshGravatarPayload;

#[Fillable([
    'name',
    'email',
    'password',
    'image',
    'use_gravatar',
    'api_token',
    'role_id',
    'ui_locale_id',
    'catalog_locale_id',
    'default_channel_id',
    'status',
    'type',
    'timezone',
    'sso_provider',
    'sso_identifier',
])]
#[Hidden([
    'password',
    'api_token',
    'remember_token',
])]
class Admin extends Authenticatable implements AdminContract, HistoryAuditable, OAuthenticatable, PresentableHistoryInterface
{
    use HasApiTokens, HasFactory, HistoryTrait, Notifiable;

    /**
     * Mirrors the `max-age=300` gravatar.com serves for avatar images, so an avatar replaced
     * upstream surfaces here within minutes instead of being pinned for the whole cache TTL.
     */
    public const GRAVATAR_FRESH_SECONDS = 300;

    public const GRAVATAR_HIT_TTL = 86400;

    public const GRAVATAR_MISS_TTL = 600;

    private const GRAVATAR_CACHE_PREFIX = 'admin.gravatar.';

    private const GRAVATAR_REFRESH_LOCK_PREFIX = 'admin.gravatar.refreshing.';

    /**
     * @var array<int, string>
     */
    protected array $historyTags = ['admin'];

    /**
     * @var array<int, string>
     */
    protected $auditExclude = [
        'password',
        'api_token',
        'remember_token',
        'sso_identifier',
    ];

    /**
     * Mirrors the DB column default so a freshly instantiated model reflects
     * 'user' before an insert round-trip populates it from the schema default.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type'         => 'user',
        'use_gravatar' => true,
    ];

    /**
     * Get image url for the product image.
     */
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    /**
     * Get image url for the product image.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->image_url());
    }

    /**
     * Get avatar url. Priority: uploaded image -> gravatar -> null.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->image_url() ?: $this->gravatar_url);
    }

    /**
     * Build deterministic gravatar url from email.
     */
    protected function gravatarUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => self::getGravatarUrlFromEmail($this->email));
    }

    /**
     * Build gravatar url from email.
     */
    public static function getGravatarUrlFromEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        if ($normalizedEmail === '') {
            return null;
        }

        $hash = md5($normalizedEmail);

        try {
            return route('admin.avatar.public', ['hash' => $hash]);
        } catch (\Throwable) {
            return "https://gravatar.com/avatar/{$hash}?s=200&d=404";
        }
    }

    /**
     * Whether this admin's email resolves to an actual gravatar image.
     *
     * Resolved server-side (and cached) so the UI can decide deterministically whether to render
     * the gravatar, instead of relying on the browser's image load succeeding.
     */
    public function hasGravatar(): bool
    {
        return (bool) $this->use_gravatar && self::gravatarExistsForEmail($this->email);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'use_gravatar' => 'boolean',
        ];
    }

    public static function getPresenters(): array
    {
        return [
            'status'       => BooleanPresenter::class,
            'use_gravatar' => BooleanPresenter::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $admin): void {
            if (! $admin->wasChanged(['email', 'use_gravatar'])) {
                return;
            }

            self::forgetGravatarCacheForEmail($admin->getOriginal('email'));
            self::forgetGravatarCacheForEmail($admin->email);
        });
    }

    public static function gravatarExistsForEmail(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        if ($normalizedEmail === '') {
            return false;
        }

        return self::gravatarPayload(md5($normalizedEmail))['found'];
    }

    /** Cache-only gravatar check; never contacts gravatar.com. */
    public static function gravatarCachedForEmail(?string $email): bool
    {
        if (! $email) {
            return false;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        if ($normalizedEmail === '') {
            return false;
        }

        $cached = Cache::get(self::GRAVATAR_CACHE_PREFIX.md5($normalizedEmail));

        return is_array($cached) && ($cached['found'] ?? false);
    }

    public static function forgetGravatarCacheForEmail(?string $email): void
    {
        if (! $email) {
            return;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        if ($normalizedEmail === '') {
            return;
        }

        Cache::forget(self::GRAVATAR_CACHE_PREFIX.md5($normalizedEmail));
        Cache::forget(self::GRAVATAR_REFRESH_LOCK_PREFIX.md5($normalizedEmail));
    }

    /**
     * Cached upstream gravatar lookup. Misses are cached (shorter TTL) so a listing that renders one
     * avatar per row does not trigger a synchronous gravatar round-trip per request.
     *
     * A cached payload older than the freshness window is still served immediately and refreshed
     * out of band, so an avatar changed on gravatar.com surfaces without ever adding latency here.
     *
     * @return array{found: bool, body: string, content_type: string, last_modified?: ?string, fetched_at?: int}
     */
    public static function gravatarPayload(string $hash): array
    {
        if (! self::isGravatarHash($hash)) {
            return self::gravatarMiss();
        }

        $cached = Cache::get(self::GRAVATAR_CACHE_PREFIX.$hash);

        if (! is_array($cached)) {
            return self::refreshGravatarPayload($hash);
        }

        if (self::isGravatarPayloadStale($cached)) {
            self::queueGravatarRefresh($hash);
        }

        return $cached;
    }

    /**
     * Refresh only when the cached payload has actually aged out, so a queue backlog holding several
     * refreshes for the same hash costs one upstream request rather than one per job.
     */
    public static function refreshStaleGravatarPayload(string $hash): void
    {
        $cached = Cache::get(self::GRAVATAR_CACHE_PREFIX.$hash);

        if (
            is_array($cached)
            && ! self::isGravatarPayloadStale($cached)
        ) {
            return;
        }

        self::refreshGravatarPayload($hash);
    }

    /**
     * Re-fetch the upstream gravatar, revalidating against the cached copy so an unchanged avatar
     * costs a bodyless 304 rather than a full image transfer.
     *
     * @return array{found: bool, body: string, content_type: string, last_modified?: ?string, fetched_at?: int}
     */
    public static function refreshGravatarPayload(string $hash): array
    {
        if (! self::isGravatarHash($hash)) {
            return self::gravatarMiss();
        }

        $cached = Cache::get(self::GRAVATAR_CACHE_PREFIX.$hash);
        $cached = is_array($cached) ? $cached : null;

        try {
            $response = Http::timeout(4)
                ->withHeaders(array_filter([
                    'User-Agent'        => 'UnoPim Avatar Proxy',
                    'Accept'            => 'image/*',
                    'If-Modified-Since' => ($cached['found'] ?? false) ? ($cached['last_modified'] ?? null) : null,
                ]))
                ->get("https://gravatar.com/avatar/{$hash}?s=200&d=404");
        } catch (ConnectionException) {
            return self::putGravatarPayload($hash, $cached ?? self::gravatarMiss());
        }

        if (
            $response->status() === 304
            && $cached !== null
        ) {
            return self::putGravatarPayload($hash, $cached);
        }

        return self::putGravatarPayload($hash, $response->successful()
            ? [
                'found'         => true,
                'body'          => $response->body(),
                'content_type'  => $response->header('Content-Type') ?: 'image/png',
                'last_modified' => $response->header('Last-Modified') ?: null,
            ]
            : self::gravatarMiss());
    }

    /**
     * @return array{found: bool, body: string, content_type: string, last_modified: null}
     */
    private static function gravatarMiss(): array
    {
        return ['found' => false, 'body' => '', 'content_type' => 'image/png', 'last_modified' => null];
    }

    private static function isGravatarHash(string $hash): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $hash);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function isGravatarPayloadStale(array $payload): bool
    {
        return now()->getTimestamp() - (int) ($payload['fetched_at'] ?? 0) >= self::GRAVATAR_FRESH_SECONDS;
    }

    private static function queueGravatarRefresh(string $hash): void
    {
        if (! Cache::add(self::GRAVATAR_REFRESH_LOCK_PREFIX.$hash, true, self::GRAVATAR_FRESH_SECONDS)) {
            return;
        }

        RefreshGravatarPayload::dispatch($hash)->afterResponse();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{found: bool, body: string, content_type: string, last_modified?: ?string, fetched_at?: int}
     */
    private static function putGravatarPayload(string $hash, array $payload): array
    {
        $payload['fetched_at'] = now()->getTimestamp();

        Cache::put(
            self::GRAVATAR_CACHE_PREFIX.$hash,
            $payload,
            ($payload['found'] ?? false) ? self::GRAVATAR_HIT_TTL : self::GRAVATAR_MISS_TTL,
        );

        return $payload;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['image_url'] = $this->image_url;

        return $array;
    }

    /**
     * Get the role that owns the admin.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleProxy::modelClass());
    }

    /**
     * Get the api integration that owns the admin.
     *
     * @return BelongsTo
     */
    public function apiKey()
    {
        return $this->hasOne(Apikey::class);
    }

    /**
     * Checks if admin has permission to perform certain action.
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        $role = $this->role;

        if (! $role) {
            return false;
        }

        if (
            $role->permission_type == 'custom'
            && ! $role->permissions
        ) {
            return false;
        }

        return in_array($permission, $role->permissions);
    }

    /**
     * Determine whether this admin is a non-interactive API (robot) account.
     */
    public function isApiUser(): bool
    {
        return $this->type === 'api';
    }

    /**
     * Scope to interactive human admins only, excluding API robots.
     */
    public function scopeHumans(Builder $query): Builder
    {
        return $query->where('type', 'user');
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return AdminFactory::new();
    }

    /**
     * Returns the ui locale selected by the user.
     */
    public function uiLocale(): BelongsTo
    {
        return $this->belongsTo(LocaleProxy::modelClass(), 'ui_locale_id');
    }

    /**
     * Locale the admin authors catalog content in; separate from uiLocale(), which is only the panel language.
     */
    public function catalogLocale(): BelongsTo
    {
        return $this->belongsTo(LocaleProxy::modelClass(), 'catalog_locale_id');
    }

    /**
     * Channel the admin works in by default.
     */
    public function defaultChannel(): BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass(), 'default_channel_id');
    }

    /**
     * Find the user instance for the given username.
     */
    public function findForPassport(string $username)
    {
        return $this->where('email', $username)->first();
    }

    /**
     * Returns the notifications associated with the user.
     */
    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }
}
