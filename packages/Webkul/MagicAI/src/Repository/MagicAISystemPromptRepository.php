<?php

namespace Webkul\MagicAI\Repository;

use Webkul\Core\Eloquent\Repository;

class MagicAISystemPromptRepository extends Repository
{
    /**
     * Specify the Model class name
     */
    public function model(): string
    {
        return 'Webkul\MagicAI\Contracts\MagicAISystemPrompt';
    }

    /**
     * Get all tone options for dropdown
     */
    public function getAllPromptOptions(): array
    {
        return $this->all()->map(function ($prompt) {
            return [
                'id'         => $prompt->id,
                'label'      => ucfirst($prompt->title),
                'is_enabled' => (bool) $prompt->is_enabled,
            ];
        })->toArray();
    }
}
