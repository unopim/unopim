<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\User\Contracts\Role as RoleContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;
use Webkul\User\Database\Factories\RoleFactory;

class Role extends Model implements HistoryContract, RoleContract
{
    use BelongsToTenant, HasFactory;
    use HistoryTrait;

    /** Tags for History */
    protected $historyTags = ['role'];

    /** Fields for History */
    protected $historyFields = [
        'name',
        'description',
    ];

    /** Proxy Table Fields for History */
    protected $historyProxyFields = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'permission_type',
        'permissions',
        'is_locked',
    ];

    /**
     * The attributes that are castable.
     *
     * @var array
     */
    protected $casts = [
        'permissions' => 'array',
        'is_locked'   => 'boolean',
    ];

    /**
     * Boot method to protect locked roles from modification.
     */
    protected static function booted(): void
    {
        static::updating(function (Role $role) {
            if ($role->getOriginal('is_locked') && $role->isDirty('permission_type')) {
                throw new \RuntimeException('Cannot change permission_type on a locked role.');
            }

            if ($role->getOriginal('is_locked') && $role->isDirty('is_locked')) {
                throw new \RuntimeException('Cannot unlock a locked role.');
            }
        });

        static::deleting(function (Role $role) {
            if ($role->is_locked) {
                throw new \RuntimeException('Cannot delete a locked role.');
            }
        });
    }

    /**
     * Get the admins.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function admins()
    {
        return $this->hasMany(AdminProxy::modelClass());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return RoleFactory::new();
    }
}
