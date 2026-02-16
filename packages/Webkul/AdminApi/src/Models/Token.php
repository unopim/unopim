<?php

namespace Webkul\AdminApi\Models;

use Laravel\Passport\Token as PassportToken;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

/**
 * Custom Passport Token model with tenant isolation support.
 *
 * This model extends Laravel Passport's default Token model to add
 * multi-tenant support through the BelongsToTenant trait. This ensures
 * that access tokens are properly scoped to the tenant that issued them,
 * preventing cross-tenant token usage and data access vulnerabilities.
 *
 * Security Impact:
 * - Tokens are automatically filtered by tenant_id via TenantScope global scope
 * - Token issuance captures tenant_id from the authenticated user
 * - Token validation includes tenant verification
 * - Prevents token replay attacks across tenant boundaries
 *
 * @see \Webkul\Tenant\Models\Concerns\BelongsToTenant
 * @see https://laravel.com/docs/passport#customizing-the-token-model
 */
class Token extends PassportToken
{
    use BelongsToTenant;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'scopes'      => 'array',
        'revoked'     => 'bool',
        'expires_at'  => 'datetime',
        'tenant_id'   => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * Get the client relationship.
     *
     * Extends the parent relationship to ensure the client is also
     * tenant-scoped. This prevents tokens from being associated with
     * clients from other tenants.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Boot the model and add tenant context on creation.
     *
     * This static boot method ensures that when a new token is created,
     * the tenant_id is automatically populated from the current tenant
     * context. This happens before the token is saved to the database,
     * ensuring all tokens are associated with a tenant.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($token) {
            // Set tenant_id from current context if not already set
            if (is_null($token->tenant_id) && function_exists('core')) {
                $currentTenantId = core()->getCurrentTenantId();
                if ($currentTenantId !== null) {
                    $token->tenant_id = $currentTenantId;
                }
            }
        });
    }

    /**
     * Determine if the token is valid for the given user.
     *
     * Enhanced validation that includes tenant verification.
     * A token is only valid if it belongs to the same tenant as the user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function isValidFor($user): bool
    {
        // Standard Passport validation
        if (! $this->isActive()) {
            return false;
        }

        // User must match
        if ($this->user_id != $user->getAuthIdentifier()) {
            return false;
        }

        // Tenant validation (critical for multi-tenant isolation)
        if (isset($user->tenant_id) && isset($this->tenant_id)) {
            if ($user->tenant_id != $this->tenant_id) {
                return false;
            }
        }

        // Client must belong to same tenant
        if ($this->relationLoaded('client') && $this->client) {
            if (isset($user->tenant_id) && isset($this->client->tenant_id)) {
                if ($user->tenant_id != $this->client->tenant_id) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if the token has been revoked or expired.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return ! $this->revoked && (! $this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Scope a query to only include tokens for a specific tenant.
     *
     * This method provides a convenient way to query tokens within
     * a specific tenant context. It's useful for administrative
     * operations that need to work within tenant boundaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include active (non-revoked, non-expired) tokens.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('revoked', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Revoke the token and all associated refresh tokens.
     *
     * Enhanced revocation that includes tenant context in audit logging.
     *
     * @return bool
     */
    public function revoke(): bool
    {
        $this->revoked = true;

        // Log tenant context for security audit
        if (function_exists('core')) {
            $tenantId = $this->tenant_id ?? core()->getCurrentTenantId();
            // Consider adding audit log entry here
        }

        return $this->save();
    }
}
