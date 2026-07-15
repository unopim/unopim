<?php

namespace Webkul\Product\Services;

use Webkul\Product\Contracts\VariantPlacementSuggester as VariantPlacementSuggesterContract;

class VariantPlacementSuggester extends AbstractSuggester implements VariantPlacementSuggesterContract
{
    protected array $variantKeywords = [
        'sku', 'price', 'cost', 'special_price', 'stock', 'qty', 'quantity',
        'weight', 'ean', 'barcode', 'upc', 'mpn',
    ];

    protected array $mediaTypes = ['image', 'gallery', 'file', 'media'];

    public function key(): string
    {
        return 'variant_placement';
    }

    public function supportsAi(): bool
    {
        return true;
    }

    public function suggestByRules(array $context): array
    {
        return $this->suggest(
            $context['attributes'] ?? [],
            (int) ($context['levels'] ?? 1),
            $context['axisCodes'] ?? []
        );
    }

    protected function aiSystemPrompt(): string
    {
        return 'You are a PIM assistant. For each product attribute, decide its variant placement: '
            .'"common" (identical for every variant), "sub_parent" (shared within a sub-group), or '
            .'"variant" (unique per variant, e.g. sku, price, stock). '
            .'Respond with ONLY a JSON object mapping each attribute code to one of those three values.';
    }

    protected function validateAiResult(array $result, array $context): array
    {
        $levels = (int) ($context['levels'] ?? 1);
        $allowed = $levels >= 2 ? ['common', 'sub_parent', 'variant'] : ['common', 'variant'];
        $codes = array_column($context['attributes'] ?? [], 'code');
        $axisCodes = $context['axisCodes'] ?? [];

        $clean = [];

        foreach ($result as $code => $level) {
            if (in_array($code, $codes, true)
                && ! in_array($code, $axisCodes, true)
                && in_array($level, $allowed, true)
            ) {
                $clean[$code] = $level;
            }
        }

        return $clean;
    }

    protected function aiInstruction(array $context): string
    {
        $levels = (int) ($context['levels'] ?? 1);
        $attributes = $context['attributes'] ?? [];
        $axisCodes = $context['axisCodes'] ?? [];

        return 'Levels: '.$levels.' ('.($levels >= 2 ? 'common, sub_parent, variant' : 'common, variant').'). '
            .'Do not include these axis attributes: '.implode(', ', $axisCodes).'. '
            .'Attributes: '.json_encode($attributes).'. '
            .'Return JSON like {"attribute_code":"common|sub_parent|variant"}.';
    }

    public function suggest(array $attributes, int $levels, array $axisCodes = []): array
    {
        $result = [];

        foreach ($attributes as $attribute) {
            $code = $attribute['code'];

            if (in_array($code, $axisCodes, true)) {
                continue;
            }

            $result[$code] = $this->levelFor($attribute, $levels);
        }

        return $result;
    }

    protected function levelFor(array $attribute, int $levels): string
    {
        if (! empty($attribute['is_unique'])) {
            return 'variant';
        }

        if ($this->matchesVariantKeyword($attribute['code'])) {
            return 'variant';
        }

        if (in_array($attribute['type'] ?? '', $this->mediaTypes, true)) {
            return $levels >= 2 ? 'sub_parent' : 'variant';
        }

        return 'common';
    }

    protected function matchesVariantKeyword(string $code): bool
    {
        foreach ($this->variantKeywords as $keyword) {
            if (str_contains($code, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
