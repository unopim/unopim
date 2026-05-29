<?php

namespace Webkul\MagicAI\Services\Prompt;

class Prompt
{
    protected ?AbstractPrompt $typeInstance = null;

    public function getPrompt(string $prompt, int $resourceId, string $resourceType): string
    {
        $typeInstance = $this->getTypeInstance($resourceType);

        return $typeInstance instanceof AbstractPrompt ? $this->getTypeInstance($resourceType)->updatePrompt($prompt, $resourceId) : $prompt;
    }

    public function getTypeInstance(string $resourceType): ?AbstractPrompt
    {
        if ($resourceType === 'product') {
            $this->typeInstance = ProductPrompt::getInstance();
        }

        if ($resourceType === 'category') {
            $this->typeInstance = CategoryPrompt::getInstance();
        }

        return $this->typeInstance;
    }
}
