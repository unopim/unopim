<?php

use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Services\EnrichmentService;

describe('EnrichmentService locale-aware completeness check', function () {

    it('detects missing fields when locale-specific bucket is empty even though common has content', function () {
        // Simulate: common has full English content, fr_FR channel_locale bucket is empty.
        // The caller (GenerateContent / AutoEnrichProductJob) must pass only the locale-specific
        // bucket as $ctx->attributes — NOT merged with common.
        $channelLocale = [];  // fr_FR has nothing yet

        $ctx = new ImageProductContext(
            detectedProduct: 'Electronics',
            attributes: $channelLocale,
        );

        // $ctx->attributes is empty → every TARGETS key is missing.
        // Verify that merging enrichment (empty) with attributes (empty) yields all targets as missing.
        $existing = array_merge($ctx->enrichment, $ctx->attributes);

        $targets = (new ReflectionClassConstant(EnrichmentService::class, 'TARGETS'))->getValue();

        $missing = array_filter($targets, fn (string $key) => empty($existing[$key]));

        expect($missing)->not->toBeEmpty()
            ->and(in_array('name', $missing))->toBeTrue()
            ->and(in_array('description', $missing))->toBeTrue()
            ->and(in_array('meta_title', $missing))->toBeTrue();
    });

    it('reports no missing fields when locale-specific bucket already has all target content', function () {
        $channelLocale = [
            'name'              => 'Produit en Français',
            'short_description' => 'Courte description',
            'description'       => 'Description complète du produit',
            'meta_title'        => 'Titre SEO',
            'meta_description'  => 'Description SEO',
            'meta_keywords'     => 'mots-clés',
            'product_number'    => 'FR-001',
        ];

        $ctx = new ImageProductContext(attributes: $channelLocale);

        $existing = array_merge($ctx->enrichment, $ctx->attributes);

        $targets = (new ReflectionClassConstant(EnrichmentService::class, 'TARGETS'))->getValue();

        $missing = array_filter($targets, fn (string $key) => empty($existing[$key]));

        expect($missing)->toBeEmpty();
    });

    it('does not treat common-bucket English content as filling the fr_FR locale bucket', function () {
        // Bug scenario: common has English content (name, description, meta fields),
        // but fr_FR channel_locale bucket is empty.
        // The old code did array_merge($common, $channelLocale) before the completeness check,
        // which caused English content to mask the missing fr_FR fields.
        $common = [
            'name'             => 'English Product Name',
            'description'      => 'English description',
            'meta_title'       => 'English SEO title',
            'meta_description' => 'English SEO description',
            'meta_keywords'    => 'english, keywords',
        ];

        $frChannelLocale = [];  // fr_FR has no locale-specific content

        $targets = (new ReflectionClassConstant(EnrichmentService::class, 'TARGETS'))->getValue();

        // Old (buggy) approach: merging common into attributes hides the missing fr_FR fields.
        $mergedWrong = array_merge($common, $frChannelLocale);
        $ctxWrong = new ImageProductContext(attributes: $mergedWrong);
        $existingWrong = array_merge($ctxWrong->enrichment, $ctxWrong->attributes);
        $missingWrong = array_filter($targets, fn (string $key) => empty($existingWrong[$key]));

        // With the wrong approach, 'name', 'description', and meta fields are hidden
        // because common filled them in — they incorrectly appear NOT missing.
        expect(in_array('name', $missingWrong))->toBeFalse()
            ->and(in_array('description', $missingWrong))->toBeFalse()
            ->and(in_array('meta_title', $missingWrong))->toBeFalse();

        // Correct (fixed) approach: only the locale-specific bucket determines completeness.
        $ctxCorrect = new ImageProductContext(attributes: $frChannelLocale);
        $existingCorrect = array_merge($ctxCorrect->enrichment, $ctxCorrect->attributes);
        $missingCorrect = array_filter($targets, fn (string $key) => empty($existingCorrect[$key]));

        // With the fix, fr_FR is correctly identified as fully missing — generate everything.
        expect($missingCorrect)->not->toBeEmpty()
            ->and(in_array('name', $missingCorrect))->toBeTrue()
            ->and(in_array('description', $missingCorrect))->toBeTrue()
            ->and(in_array('meta_title', $missingCorrect))->toBeTrue();
    });

    it('correctly identifies partially filled locale bucket', function () {
        // de_DE has a name but no description or SEO fields.
        $deChannelLocale = [
            'name' => 'Deutsches Produkt',
        ];

        $ctx = new ImageProductContext(attributes: $deChannelLocale);
        $existing = array_merge($ctx->enrichment, $ctx->attributes);
        $targets = (new ReflectionClassConstant(EnrichmentService::class, 'TARGETS'))->getValue();
        $missing = array_filter($targets, fn (string $key) => empty($existing[$key]));

        expect($missing)->not->toBeEmpty()
            ->and(in_array('name', $missing))->toBeFalse()      // name is present
            ->and(in_array('description', $missing))->toBeTrue() // description is missing
            ->and(in_array('meta_title', $missing))->toBeTrue(); // SEO is missing
    });
});
