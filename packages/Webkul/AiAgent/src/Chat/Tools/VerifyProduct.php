<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class VerifyProduct implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('verify_product')
            ->for('Verify product data quality and completeness after making changes. Use this to self-check your work — call it after creating or updating a product.')
            ->withStringParameter('sku', 'Product SKU to verify')
            ->using(function (string $sku) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products')) {
                    return $denied;
                }

                $product = DB::table('products')->where('sku', $sku)->first();

                if (! $product) {
                    return json_encode(['error' => "Product not found: {$sku}"]);
                }

                $values = json_decode($product->values, true) ?? [];
                $common = $values['common'] ?? [];
                $channelLocale = $values['channel_locale_specific'][$context->channel][$context->locale] ?? [];
                $categories = $values['categories'] ?? [];

                $issues = [];
                $score = 100;

                // Check critical fields
                $name = $channelLocale['name'] ?? $common['name'] ?? null;
                if (empty($name)) {
                    $issues[] = 'Missing product name';
                    $score -= 25;
                }

                $description = $channelLocale['description'] ?? $common['description'] ?? null;
                if (empty($description)) {
                    $issues[] = 'Missing description';
                    $score -= 15;
                } elseif (mb_strlen($description) < 50) {
                    $issues[] = 'Description is very short (< 50 chars)';
                    $score -= 5;
                }

                if (empty($common['url_key'])) {
                    $issues[] = 'Missing URL key';
                    $score -= 10;
                }

                if (empty($common['image'])) {
                    $issues[] = 'No product image';
                    $score -= 15;
                }

                if (empty($categories)) {
                    $issues[] = 'No categories assigned';
                    $score -= 10;
                }

                // Check SEO fields
                $metaTitle = $channelLocale['meta_title'] ?? $common['meta_title'] ?? null;
                $metaDesc = $channelLocale['meta_description'] ?? $common['meta_description'] ?? null;
                if (empty($metaTitle)) {
                    $issues[] = 'Missing SEO meta title';
                    $score -= 5;
                }
                if (empty($metaDesc)) {
                    $issues[] = 'Missing SEO meta description';
                    $score -= 5;
                }

                // Check product status
                if (! $product->status) {
                    $issues[] = 'Product is inactive';
                    $score -= 5;
                }

                $score = max(0, $score);

                // Determine quality level
                $quality = match (true) {
                    $score >= 90 => 'excellent',
                    $score >= 70 => 'good',
                    $score >= 50 => 'needs_improvement',
                    default      => 'poor',
                };

                $filledFields = array_merge(
                    array_keys($common),
                    array_keys($channelLocale),
                );

                return json_encode([
                    'result' => [
                        'sku'           => $sku,
                        'quality_score' => $score,
                        'quality_level' => $quality,
                        'issues'        => empty($issues) ? null : $issues,
                        'filled_fields' => $filledFields,
                        'categories'    => $categories,
                        'has_image'     => ! empty($common['image']),
                        'status'        => $product->status ? 'active' : 'inactive',
                    ],
                ]);
            });
    }
}
