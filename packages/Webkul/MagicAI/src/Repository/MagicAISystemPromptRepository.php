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
}
