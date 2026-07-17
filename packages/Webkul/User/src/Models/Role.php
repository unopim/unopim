<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\User\Contracts\Role as RoleContract;
use Webkul\User\Database\Factories\RoleFactory;

#[Fillable([
    'name',
    'description',
    'permission_type',
    'permissions',
])]
class Role extends Model implements HistoryContract, RoleContract
{
    use HasFactory;
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
     * Get the admins.
     *
     * @return HasMany
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

    /**
     * The attributes that are castable.
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
