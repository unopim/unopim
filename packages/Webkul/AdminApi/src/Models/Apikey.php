<?php

namespace Webkul\AdminApi\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\AdminApi\Contracts\Apikey as ApikeyContract;
use Webkul\AdminApi\Database\Factories\ApiKeyFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\User\Models\AdminProxy;

class Apikey extends Model implements ApikeyContract, HistoryContract
{
    use HasFactory;
    use HistoryTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'api_keys';

    /** Tags for History */
    protected $historyTags = ['Apikey'];

    /** Fields for History */
    protected $historyFields = [
        'name',
        'oauth_client_id',
    ];

    /** Proxy Table Fields for History */
    protected $historyProxyFields = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'admin_id',
        'oauth_client_id',
        'permission_type',
        'permissions',
        'revoked',
    ];

    protected $attributes = [
        'revoked' => false,
    ];

    /**
     * The attributes that are castable.
     *
     * @var array
     */
    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the admins.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admins()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'admin_id');
    }

    /**
     * Get the oauthClients.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function oauthClients()
    {
        return $this->belongsTo(Client::class, 'oauth_client_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ApiKeyFactory::new();
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
            $this->permission_type == 'custom'
            && ! $this->permissions
        ) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Returns an array of permission types for API integrations.
     *
     * @return array An array containing permission type details. Each element is an associative array with 'id' and 'label' keys.
     */
    public function getPermissionTypes()
    {
        return [
            [
                'id'    => 'custom',
                'label' => trans('admin::app.configuration.integrations.edit.custom'),
            ],
            [
                'id'    => 'all',
                'label' => trans('admin::app.configuration.integrations.edit.all'),
            ],
        ];
    }
}
