<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\Core\Contracts\CoreConfig as CoreConfigContract;
use Webkul\Core\Database\Factories\CoreConfigFactory;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class CoreConfig extends Model implements AuditableContract, CoreConfigContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    /**
     * Tags for the history/audit trail.
     *
     * @var array
     */
    protected $historyTags = ['core-config'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'core_config';

    /**
     * Fillable for mass assignment
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'value',
        'channel_code',
        'locale_code',
    ];

    /**
     * Hidden properties
     *
     * @var array
     */
    protected $hidden = ['token'];

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return CoreConfigFactory::new();
    }

    /**
     * Custom history presenters per column (none needed — default rendering).
     *
     * @return array<string, class-string>
     */
    public static function getPresenters(): array
    {
        return [];
    }

    /**
     * Redact secret values (password-type config, or codes that look like a
     * credential) before they are written to the audit trail, so the history
     * never leaks a stored secret.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        if (! $this->isSensitiveConfig()) {
            return $data;
        }

        foreach (['old_values', 'new_values'] as $bucket) {
            if (isset($data[$bucket]['value']) && $data[$bucket]['value'] !== '') {
                $data[$bucket]['value'] = '••••••••';
            }
        }

        return $data;
    }

    /**
     * Whether this config row holds a secret (password field type, or a
     * credential-looking code) that must be redacted from history.
     */
    protected function isSensitiveConfig(): bool
    {
        $field = core()->getConfigField($this->code);

        if (($field['type'] ?? null) === 'password') {
            return true;
        }

        return (bool) preg_match('/(password|secret|token|api[_-]?key)/i', (string) $this->code);
    }
}
