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
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Webkul\Admin\Mail\Admin\ResetPasswordNotification;
use Webkul\AdminApi\Models\Apikey;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\LocaleProxy;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Notification\Models\UserNotification;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Database\Factories\AdminFactory;

#[Fillable([
    'name',
    'email',
    'password',
    'image',
    'api_token',
    'role_id',
    'ui_locale_id',
    'catalog_locale_id',
    'default_channel_id',
    'status',
    'type',
    'timezone',
])]
#[Hidden([
    'password',
    'api_token',
    'remember_token',
])]
class Admin extends Authenticatable implements AdminContract, HistoryAuditable, OAuthenticatable
{
    use HasApiTokens, HasFactory, HistoryTrait, Notifiable;

    /**
     * @var array<int, string>
     */
    protected array $historyTags = ['admin'];

    /**
     * Mirrors the DB column default so a freshly instantiated model reflects
     * 'user' before an insert round-trip populates it from the schema default.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'user',
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
        return Attribute::make(get: fn () => $this->image_url() ?: $this->getGravatarUrlAttribute());
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
