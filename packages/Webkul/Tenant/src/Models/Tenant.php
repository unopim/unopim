<?php

namespace Webkul\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Tenant\Contracts\Tenant as TenantContract;
use Webkul\Tenant\Database\Factories\TenantFactory;
use Webkul\Tenant\Exceptions\TenantStateTransitionException;

class Tenant extends Model implements TenantContract
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenants';

    protected $fillable = [
        'uuid',
        'name',
        'domain',
        'status',
        'settings',
        'es_index_uuid',
    ];

    protected $casts = [
        'status'   => 'string',
        'settings' => 'json',
    ];

    /**
     * Valid lifecycle states for a tenant.
     */
    const STATUS_PROVISIONING = 'provisioning';

    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUS_DELETING = 'deleting';

    const STATUS_DELETED = 'deleted';

    /**
     * All valid statuses.
     */
    const STATUSES = [
        self::STATUS_PROVISIONING,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_DELETING,
        self::STATUS_DELETED,
    ];

    /**
     * Allowed state transitions.
     */
    const TRANSITIONS = [
        self::STATUS_PROVISIONING => [self::STATUS_ACTIVE, self::STATUS_DELETED],
        self::STATUS_ACTIVE       => [self::STATUS_SUSPENDED, self::STATUS_DELETING],
        self::STATUS_SUSPENDED    => [self::STATUS_ACTIVE, self::STATUS_DELETING],
        self::STATUS_DELETING     => [self::STATUS_DELETED],
        self::STATUS_DELETED      => [],
    ];

    /**
     * Check if a transition to the given status is valid.
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Transition to a new lifecycle state with audit logging.
     *
     * @throws TenantStateTransitionException
     */
    public function transitionTo(string $status, ?int $operatorId = null): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new TenantStateTransitionException($this->status, $status);
        }

        $previousStatus = $this->status;
        $this->status = $status;

        $settings = $this->settings ?? [];
        $settings['transition_log'] = $settings['transition_log'] ?? [];
        $settings['transition_log'][] = [
            'from'        => $previousStatus,
            'to'          => $status,
            'operator_id' => $operatorId,
            'at'          => now()->toIso8601String(),
        ];
        $this->settings = $settings;

        $this->save();
    }

    /**
     * Scope: only active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
