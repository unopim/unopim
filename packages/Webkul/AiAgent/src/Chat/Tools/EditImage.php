<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Http\File;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Image as AiImage;
use Laravel\Ai\Image;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\MagicAI\Enums\AiProvider;

class EditImage implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('edit_image')
            ->for('Edit an uploaded product image using AI. Supports: remove/change background, enhance quality, add/remove objects, adjust lighting, change colors, resize for different platforms, add text overlays. The user must upload an image first. Only works with providers that support image generation (OpenAI, Gemini, xAI).')
            ->withStringParameter('instruction', 'What to do with the image (e.g. "Remove background and make it white", "Enhance lighting and contrast", "Add shadow under the product")')
            ->withStringParameter('sku', 'Optional: Product SKU to attach the edited image to')
            ->withEnumParameter('size', 'Output image size/aspect ratio', ['1024x1024', '1024x1792', '1792x1024'])
            ->using(function (string $instruction, ?string $sku = null, string $size = '1024x1024') use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products.edit')) {
                    return $denied;
                }

                if (! $context->hasImages()) {
                    return json_encode(['error' => 'No image was uploaded. Ask the user to upload an image first.']);
                }

                $imagePath = $context->firstImagePath();

                if (! $imagePath || ! file_exists($imagePath)) {
                    return json_encode(['error' => 'Uploaded image file not found on disk.']);
                }

                $aiProvider = AiProvider::from($context->platform->provider);

                if (! $aiProvider->supportsImages()) {
                    return json_encode([
                        'error' => "The current AI provider ({$aiProvider->label()}) does not support image editing. Switch to OpenAI, Gemini, or xAI.",
                    ]);
                }

                try {
                    $configKey = $aiProvider->configKey();
                    config([
                        "ai.providers.{$configKey}.key" => $context->platform->api_key,
                    ]);

                    if ($context->platform->api_url) {
                        config(["ai.providers.{$configKey}.url" => $context->platform->api_url]);
                    }

                    $imageModel = $this->resolveImageModel($context);

                    $sizeMap = [
                        '1024x1024' => '1:1',
                        '1024x1792' => '2:3',
                        '1792x1024' => '3:2',
                    ];

                    $response = Image::of($instruction)
                        ->attachments([
                            AiImage::fromPath($imagePath),
                        ])
                        ->size($sizeMap[$size] ?? '1:1')
                        ->quality('high')
                        ->generate(
                            provider: $aiProvider->toLab(),
                            model: $imageModel,
                        );

                    if (empty($response->images)) {
                        return json_encode(['error' => 'Image editing returned no results.']);
                    }

                    $imageData = $response->images[0];
                    $mime = $imageData->mime ?? 'image/png';
                    $extension = $mime === 'image/png' ? 'png' : 'webp';

                    $filename = 'ai-edited-'.Str::random(12).'.'.$extension;
                    $storagePath = 'ai-agent/edited/'.$filename;
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
                        'edited'       => true,
                        'filename'     => $filename,
                        'download_url' => asset('storage/'.$storagePath),
                        'instruction'  => $instruction,
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
                            $result['warning'] = "Product SKU '{$sku}' not found. Edited image saved but not attached.";
                        }
                    }

                    return json_encode([
                        'result'       => $result,
                        'download_url' => $result['download_url'],
                        'product_url'  => $result['product_url'] ?? null,
                    ]);
                } catch (\Throwable $e) {
                    return json_encode(['error' => 'Image editing failed: '.$e->getMessage()]);
                }
            });
    }

    /**
     * Resolve an image-editing capable model for the provider.
     *
     * Priority: user-selected model (if image-capable) → known valid models
     * from the platform list → fallback defaults.
     */
    protected function resolveImageModel(ChatContext $context): string
    {
        $provider = $context->platform->provider;

        $imageModelPatterns = match ($provider) {
            'openai' => ['dall-e', 'gpt-image'],
            'gemini' => ['gemini-2', 'imagen'],
            'xai'    => ['grok'],
            default  => [],
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
            'openai' => ['gpt-image-1', 'gpt-image-1-mini', 'gpt-image-1.5', 'dall-e-3', 'dall-e-2'],
            'gemini' => ['gemini-2.0-flash-preview-image-generation', 'gemini-2.5-flash-image'],
            'xai'    => ['grok-2-image'],
            default  => [],
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
            'openai' => 'gpt-image-1',
            'gemini' => 'gemini-2.0-flash-preview-image-generation',
            'xai'    => 'grok-2-image',
            default  => $context->model,
        };
    }
}
