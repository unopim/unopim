<?php

namespace Webkul\MagicAI\Contracts;

interface LLMModelInterface
{
    public function ask(): string;

    public function images(array $options): array;
}
