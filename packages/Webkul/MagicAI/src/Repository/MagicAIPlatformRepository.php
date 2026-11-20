<?php

namespace Webkul\MagicAI\Repository;

use Webkul\Core\Eloquent\Repository;
use Webkul\MagicAI\Contracts\MagicAIPlatform;

class MagicAIPlatformRepository extends Repository
{
    /**
     * Specify the Model class name
     */
    public function model(): string
    {
        return MagicAIPlatform::class;
    }

    /**
     * The saved platform a submission points at when it sends a masked key and
     * the platform id, so that platform's stored key is reused.
     */
    public function findForMaskedKey(mixed $apiKey, mixed $platformId): ?MagicAIPlatform
    {
        if (! is_string($apiKey) || preg_match('/^\*+$/', $apiKey) !== 1 || (int) $platformId < 1) {
            return null;
        }

        return $this->find((int) $platformId);
    }

    /**
     * Get the platform marked as default, enabled or not.
     */
    public function getDefault()
    {
        return $this->model->default()->first();
    }

    /**
     * Get the default platform to generate with: the default one when it is
     * enabled, else any other enabled platform.
     */
    public function getActiveDefault()
    {
        return $this->model->active()->default()->first()
            ?? $this->model->active()->first();
    }

    /**
     * Get all active platforms.
     */
    public function getActiveList()
    {
        return $this->model->active()->get();
    }

    /**
     * Get active platforms formatted for dropdown options.
     */
    public function getActivePlatformOptions(): array
    {
        return $this->model->active()->get()->map(fn ($platform): array => [
            'id'         => $platform->id,
            'label'      => $platform->label.' ('.ucfirst((string) $platform->provider).')',
            'provider'   => $platform->provider,
            'models'     => $platform->model_list,
            'is_default' => $platform->is_default,
        ])->toArray();
    }

    /**
     * Get model options for a specific platform.
     */
    public function getModelOptions(int $platformId): array
    {
        $platform = $this->find($platformId);

        if (! $platform) {
            return [];
        }

        return array_map(fn ($model): array => [
            'id'    => $model,
            'label' => $model,
        ], $platform->model_list);
    }
}
