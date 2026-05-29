<?php

namespace Webkul\MagicAI\Repository;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Eloquent\Repository;
use Webkul\MagicAI\Contracts\MagicAISystemPrompt;

class MagicAISystemPromptRepository extends Repository
{
    /**
     * Specify the Model class name
     */
    public function model(): string
    {
        return MagicAISystemPrompt::class;
    }

    /**
     * Get all tone options for dropdown
     */
    public function getAllPromptOptions(): array
    {
        return $this->all()->map(fn (Model $prompt) => [
            'id'         => $prompt->id,
            'label'      => ucfirst((string) $prompt->title),
            'is_enabled' => (bool) $prompt->is_enabled,
        ])->toArray();
    }

    /**
     * Disable all enabled system prompts
     */
    public function disableAllEnabledPrompts(): int
    {
        return $this->model->where('is_enabled', true)->update(['is_enabled' => false]);
    }
}
