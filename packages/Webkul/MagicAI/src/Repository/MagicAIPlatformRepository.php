<?php

namespace Webkul\MagicAI\Repository;

use Webkul\Core\Eloquent\Repository;

class MagicAIPlatformRepository extends Repository
{
    /**
     * Specify the Model class name
     */
    public function model(): string
    {
        return 'Webkul\MagicAI\Contracts\MagicAIPlatform';
    }

    /**
     * Get the default active platform.
     */
    public function getDefault()
    {
        return $this->model->active()->default()->first();
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
        return $this->model->active()->get()->map(function ($platform) {
            return [
                'id'         => $platform->id,
                'label'      => $platform->label.' ('.ucfirst($platform->provider).')',
                'provider'   => $platform->provider,
                'models'     => $platform->model_list,
                'is_default' => $platform->is_default,
            ];
        })->toArray();
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

        return array_map(function ($model) {
            return [
                'id'    => $model,
                'label' => $model,
            ];
        }, $platform->model_list);
    }
}
