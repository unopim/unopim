<?php

namespace Webkul\AdminApi\Models;

use Laravel\Passport\RefreshToken as PassportRefreshToken;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

/**
 * Custom Passport RefreshToken model with tenant isolation support.
 *
 * This model extends Laravel Passport's default RefreshToken model to add
 * multi-tenant support. Refresh tokens are long-lived credentials that can
 * be used to obtain new access tokens, making tenant isolation critical.
 *
 * Security Impact:
 * - Refresh tokens are scoped to their issuing tenant
 * - Prevents long-lived credential replay across tenant boundaries
 * - Validates associated access token belongs to same tenant
 * - Prevents token persistence attacks after tenant deletion
 *
 * @see \Webkul\AdminApi\Models\Token
 * @see \Webkul\Tenant\Models\Concerns\BelongsToTenant
 */
class RefreshToken extends PassportRefreshToken
{
    use BelongsToTenant;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'revoked'     => 'bool',
        'expires_at'  => 'datetime',
        'tenant_id'   => 'integer',
    ];

    /**
     * Get the access token relationship.
     *
     * Extends the parent relationship to ensure tenant-scoped access.
     * A refresh token can only be used with its associated access token
     * from the same tenant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accessToken()
    {
        return $this->belongsTo(Token::class, 'access_token_id');
    }

    /**
     * Boot the model and add tenant context on creation.
     *
     * When a refresh token is created, its tenant_id should match
     * the associated access token's tenant_id. This boot method
     * ensures proper tenant association.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refreshToken) {
            // Inherit tenant_id from associated access token if not set
            if (is_null($refreshToken->tenant_id) && function_exists('core')) {
                $currentTenantId = core()->getCurrentTenantId();
                if ($currentTenantId !== null) {
                    $refreshToken->tenant_id = $currentTenantId;
                }
            }
        });

        static::created(function ($refreshToken) {
            // Validate tenant consistency with access token
            if ($refreshToken->relationLoaded('accessToken') && $refreshToken->accessToken) {
                if ($refreshToken->tenant_id !== $refreshToken->accessToken->tenant_id) {
                    // Log security event - tenant mismatch
                    if (function_exists('logger')) {
                        logger()->critical('RefreshToken tenant mismatch detected', [
                            'refresh_token_id' => $refreshToken->id,
                            'refresh_token_tenant_id' => $refreshToken->tenant_id,
                            'access_token_id' => $refreshToken->access_token_id,
                            'access_token_tenant_id' => $refreshToken->accessToken->tenant_id,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Determine if the refresh token is valid and active.
     *
     * Enhanced validation that includes tenant verification.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // Standard validation
        if ($this->revoked) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Verify associated access token belongs to same tenant
        if ($this->relationLoaded('accessToken') && $this->accessToken) {
            if (isset($this->tenant_id) && isset($this->accessToken->tenant_id)) {
                if ($this->tenant_id !== $this->accessToken->tenant_id) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Scope a query to only include refresh tokens for a specific tenant.
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
     * Scope a query to only include active (non-revoked, non-expired) refresh tokens.
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
     * Revoke the refresh token.
     *
     * @return bool
     */
    public function revoke(): bool
    {
        $this->revoked = true;

        return $this->save();
    }
}
