<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\Admin\Mail\Admin\ResetPasswordNotification;
use Webkul\AdminApi\Models\Apikey;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Notification\Models\UserNotification;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\User\Database\Factories\AdminFactory;

class Admin extends Authenticatable implements AdminContract, AuditableContract
{
    use BelongsToTenant, Auditable, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'api_token',
        'role_id',
        'ui_locale_id',
        'status',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
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
    public function getImageUrlAttribute()
    {
        return $this->image_url();
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(RoleProxy::modelClass());
    }

    /**
     * Get the api integration that owns the admin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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
        if (
            $this->role->permission_type == 'custom'
            && ! $this->role->permissions
        ) {
            return false;
        }

        // Tenant users cannot access platform-reserved permissions (Story 5.5)
        $guard = app(\Webkul\Tenant\Auth\TenantPermissionGuard::class);

        if (! $guard->isAllowed($this, $permission)) {
            return false;
        }

        return in_array($permission, $this->role->permissions);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
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
