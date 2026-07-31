<?php

namespace Webkul\MagicAI\Repository;

use Webkul\Core\Eloquent\Repository;
use Webkul\MagicAI\Contracts\MagicPrompt;

class MagicPromptRepository extends Repository
{
    /**
     * Specify the Model class name
     */
    public function model(): string
    {
        return MagicPrompt::class;
    }
}
