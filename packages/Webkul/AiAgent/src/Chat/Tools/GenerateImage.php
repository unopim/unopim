<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Http\File;
use Illuminate\Support\Str;
use Laravel\Ai\Image;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\MagicAI\Enums\AiProvider;

class GenerateImage implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('generate_image')
            ->for('Generate an image from text and optionally attach to a product.')
            ->withStringParameter('prompt', 'Detailed description of the image to generate (e.g. "Professional product photo of a red leather handbag on white background")')
            ->withStringParameter('sku', 'Optional: Product SKU to attach the generated image to')
            ->withEnumParameter('size', 'Image size/aspect ratio', ['1024x1024', '1024x1792', '1792x1024'])
            ->using(function (string $prompt, ?string $sku = null, string $size = '1024x1024') use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products.edit')) {
                    return $denied;
                }

                $aiProvider = AiProvider::from($context->platform->provider);

                if (! $aiProvider->supportsImages()) {
                    return json_encode([
                        'error' => "The current AI provider ({$aiProvider->label()}) does not support image generation. Switch to OpenAI, Gemini, or xAI to generate images.",
                    ]);
                }

                try {
                    // Configure the AI provider
                    $configKey = $aiProvider->configKey();
                    config([
                        "ai.providers.{$configKey}.key" => $context->platform->api_key,
                    ]);

                    if ($context->platform->api_url) {
                        config(["ai.providers.{$configKey}.url" => $context->platform->api_url]);
                    }

                    // Find an image-generation capable model
                    $imageModel = $this->resolveImageModel($context);

                    $sizeMap = [
                        '1024x1024' => '1:1',
                        '1024x1792' => '2:3',
                        '1792x1024' => '3:2',
                    ];

                    $response = Image::of($prompt)
                        ->size($sizeMap[$size] ?? '1:1')
                        ->quality('high')
                        ->generate(
                            provider: $aiProvider->toLab(),
                            model: $imageModel,
                        );

                    if (empty($response->images)) {
                        return json_encode(['error' => 'Image generation returned no images.']);
                    }

                    $imageData = $response->images[0];
                    $mime = $imageData->mime ?? 'image/png';
                    $extension = $mime === 'image/png' ? 'png' : 'webp';

                    // Save generated image to storage
                    $filename = 'ai-generated-'.Str::random(12).'.'.$extension;
                    $storagePath = 'ai-agent/generated/'.$filename;
                    $fullPath = storage_path('app/public/'.$storagePath);

                    // Validate path stays within allowed directory.
                    $baseDir = storage_path('app/public/ai-agent/');
                    if (! str_starts_with($fullPath, $baseDir)) {
                        return json_encode(['error' => trans('ai-agent::app.common.invalid-file-path')]);
                    }

                    $dir = \dirname($fullPath);
                    if (! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    file_put_contents($fullPath, base64_decode($imageData->image));

                    $result = [
                        'generated'    => true,
                        'filename'     => $filename,
                        'download_url' => asset('storage/'.$storagePath),
                    ];

                    // Attach to product if SKU provided
                    if ($sku) {
                        $repo = app('Webkul\Product\Repositories\ProductRepository');
                        $product = $repo->findOneByField('sku', $sku);

                        if ($product) {
                            $fileStorer = app(FileStorer::class);
                            $targetPath = 'product'.DIRECTORY_SEPARATOR.$product->id.DIRECTORY_SEPARATOR.'image';

                            $storedImage = $fileStorer->store(
                                $targetPath,
                                new File($fullPath),
                                [FileStorer::HASHED_FOLDER_NAME_KEY => true],
                            );

                            if ($storedImage) {
                                $values = $product->values ?? [];
                                $values['common']['image'] = $storedImage;
                                $repo->updateWithValues(['values' => $values], $product->id);

                                $result['attached_to'] = $sku;
                                $result['product_url'] = route('admin.catalog.products.edit', $product->id);
                            }
                        } else {
                            $result['warning'] = "Product SKU '{$sku}' not found. Image generated but not attached.";
                        }
                    }

                    return json_encode([
                        'result'       => $result,
                        'download_url' => $result['download_url'],
                        'product_url'  => $result['product_url'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    return json_encode(['error' => 'Image generation failed: '.$e->getMessage()]);
                }
            });
    }

    /**
     * Resolve an image-generation capable model for the provider.
     *
     * Priority: user-selected model (if image-capable) → known valid models
     * from the platform list → fallback defaults.
     */
    protected function resolveImageModel(ChatContext $context): string
    {
        $provider = $context->platform->provider;

        $imageModelPatterns = match ($provider) {
            'openai'  => ['dall-e', 'gpt-image'],
            'gemini'  => ['gemini-2', 'imagen'],
            'xai'     => ['grok'],
            default   => [],
        };

        // 1. If the user explicitly selected an image-capable model, use it
        if ($context->model) {
            foreach ($imageModelPatterns as $pattern) {
                if (stripos($context->model, $pattern) !== false) {
                    return $context->model;
                }
            }
        }

        // 2. Scan the platform's model list — prefer known valid image models
        $knownImageModels = match ($provider) {
            'openai'  => ['gpt-image-1', 'gpt-image-1-mini', 'gpt-image-1.5', 'dall-e-3', 'dall-e-2'],
            'gemini'  => ['gemini-2.0-flash-preview-image-generation', 'gemini-2.5-flash-image'],
            'xai'     => ['grok-2-image'],
            default   => [],
        };

        $models = $context->platform->model_list ?? [];

        // First pass: prefer known valid models
        foreach ($knownImageModels as $known) {
            if (in_array($known, $models, true)) {
                return $known;
            }
        }

        // Second pass: any model matching image patterns
        foreach ($models as $model) {
            foreach ($imageModelPatterns as $pattern) {
                if (stripos($model, $pattern) !== false) {
                    return $model;
                }
            }
        }

        // 3. Fallback defaults
        return match ($provider) {
            'openai'  => 'gpt-image-1',
            'gemini'  => 'gemini-2.0-flash-preview-image-generation',
            'xai'     => 'grok-2-image',
            default   => $context->model,
        };
    }
}
