<?php

namespace Webkul\MagicAI\Services\Prompt;

use Webkul\Product\Repositories\ProductRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

class Prompt
{
    protected $typeInstance;

    public function getPrompt(string $prompt, int $resourceId, string $resourceType)
    {
        $typeInstance = $this->getTypeInstance($resourceType);

        $prompt = $typeInstance ? $this->getTypeInstance($resourceType)->updatePrompt($prompt, $resourceId) : $prompt;

        return $prompt;
    }

    public function getTypeInstance($resourceType)
    {
        if ($resourceType === 'product') {
            $this->typeInstance = new ProductPrompt(app(ProductRepository::class), app(AttributeRepository::class));
        }

        if ($resourceType === 'category') {
            $this->typeInstance = CategoryPrompt::getInstance();
        }

        return $this->typeInstance;
    }
}
