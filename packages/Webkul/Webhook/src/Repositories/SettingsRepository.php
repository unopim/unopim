<?php

namespace Webkul\Webhook\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Webhook\Models\WebhookSetting;

class SettingsRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return WebhookSetting::class;
    }

    public function createOrUpdate(string $field, $value, array $extra = [])
    {
        $config = $this->updateOrCreate(
            ['field' => $field],
            [
                'value' => $value,
                'extra' => $extra,
            ]
        );

        return $config;
    }

    public function getAllDataAndNormalize(): array
    {
        $configurations = $this->all();
        $normalizedData = [];

        foreach ($configurations as $config) {
            $value = $config->value;

            if ($config->field === 'webhook_active') {
                $value = (int) $config->value;
            }

            $normalizedData[$config->field] = $value;
        }

        return $normalizedData;
    }

    public function isWebhookActive(): bool
    {
        return (bool) ((int) $this->where('field', 'webhook_active')->first()?->value ?? 0);
    }

    public function getWebhookUrl(): ?string
    {
        return $this->where('field', 'webhook_url')->first()?->value;
    }
}
