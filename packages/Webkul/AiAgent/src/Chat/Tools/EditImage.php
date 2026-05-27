<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Image as AiImage;
use Laravel\Ai\Image;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Core\Helpers\Database\GrammarQueryManager;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\Product\Models\Product;

class EditImage implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        $outer = $this;

        return new class($context, $outer) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected EditImage $outer)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'edit_image';
            }

            public function description(): string
            {
                return 'Edit an existing product image using AI. Fetches the image from the product by SKU, edits it with AI, and saves the edited image back to the product. Supports: remove/change background, enhance quality, add/remove objects, adjust lighting, change colors. For gallery attributes with multiple images, specify which image to edit by index. If the user uploaded an image in this chat, that uploaded image is used instead.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'         => $schema->string()->description('The product SKU whose image to edit'),
                    'instruction' => $schema->string()->description('What to do with the image (e.g. "Remove background and make it white", "Enhance lighting and contrast")'),
                    'attribute'   => $schema->string()->description('Optional: attribute code of the image to edit (e.g. "image", "gallery"). If omitted, auto-detects the first image/gallery attribute.'),
                    'image_index' => $schema->string()->description('Optional: for gallery attributes with multiple images, the 0-based index of the image to edit (default: 0)'),
                    'size'        => $schema->string()->enum(['1024x1024', '1024x1792', '1792x1024'])->description('Output image size/aspect ratio'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                $sku = $request->string('sku')->toString();
                $instruction = $request->string('instruction')->toString();
                $attribute = $request->string('attribute')->toString() ?: null;
                $image_index = $request->string('image_index')->toString() ?: null;
                $size = $request->string('size')->toString() ?: '1024x1024';

                $repo = app('Webkul\Product\Repositories\ProductRepository');
                $product = $repo->findOneByField('sku', $sku);

                if (! $product) {
                    return json_encode(['error' => "Product with SKU '{$sku}' not found."]);
                }

                // Resolve the source image path
                $sourceResult = $this->outer->resolveSourceImage($this->context, $product, $attribute, (int) ($image_index ?? 0));

                if (isset($sourceResult['error'])) {
                    return json_encode($sourceResult);
                }

                $imagePath = $sourceResult['path'];
                $resolvedAttribute = $sourceResult['attribute'];
                $resolvedIndex = $sourceResult['index'];
                $scope = $sourceResult['scope'];

                $aiProvider = AiProvider::from($this->context->platform->provider);

                if (! $aiProvider->supportsImages()) {
                    return json_encode([
                        'error' => "The current AI provider ({$aiProvider->label()}) does not support image editing. Switch to OpenAI, Gemini, or xAI.",
                    ]);
                }

                try {
                    $configKey = $aiProvider->configKey();
                    config([
                        "ai.providers.{$configKey}.key" => $this->context->platform->api_key,
                    ]);

                    if ($this->context->platform->api_url) {
                        config(["ai.providers.{$configKey}.url" => $this->context->platform->api_url]);
                    }

                    $imageModel = $this->outer->resolveImageModel($this->context);

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
                    $tempPath = storage_path('app/public/ai-agent/edited/'.$filename);

                    $dir = \dirname($tempPath);
                    if (! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    file_put_contents($tempPath, base64_decode($imageData->image));

                    // Save edited image back to the product
                    $saveResult = $this->outer->saveToProduct($product, $resolvedAttribute, $resolvedIndex, $scope, $tempPath, $repo);

                    $result = [
                        'edited'       => true,
                        'sku'          => $sku,
                        'attribute'    => $resolvedAttribute->code,
                        'instruction'  => $instruction,
                        'download_url' => asset('storage/ai-agent/edited/'.$filename),
                        'product_url'  => route('admin.catalog.products.edit', $product->id),
                    ];

                    if (isset($saveResult['error'])) {
                        $result['warning'] = $saveResult['error'];
                    } else {
                        $result['saved_to_product'] = true;
                        $result['stored_path'] = $saveResult['stored_path'];
                    }

                    return json_encode([
                        'result'       => $result,
                        'download_url' => $result['download_url'],
                        'product_url'  => $result['product_url'],
                    ]);
                } catch (\Throwable $e) {
                    return json_encode(['error' => 'Image editing failed: '.$e->getMessage()]);
                }
            }
        };
    }

    /**
     * Resolve the source image to edit — from uploaded image or product attribute.
     */
    public function resolveSourceImage(ChatContext $context, Product $product, ?string $attributeCode, int $imageIndex): array
    {
        // If user uploaded an image in chat, use that
        if ($context->hasImages()) {
            $path = $context->firstImagePath();

            if ($path && file_exists($path)) {
                $attr = $this->resolveImageAttribute($product, $attributeCode);

                return [
                    'path'      => $path,
                    'attribute' => $attr,
                    'index'     => $imageIndex,
                    'scope'     => $attr ? $this->getAttributeScope($attr) : 'common',
                ];
            }
        }

        // Otherwise fetch from product
        $attr = $this->resolveImageAttribute($product, $attributeCode);

        if (! $attr) {
            return ['error' => 'No image or gallery attribute found for this product. Specify the attribute code explicitly.'];
        }

        $scope = $this->getAttributeScope($attr);
        $channelCode = core()->getRequestedChannelCode();
        $localeCode = $context->locale ?? core()->getRequestedLocaleCode();

        $value = $attr->getValueFromProductValues(
            $product->values ?? [],
            $channelCode,
            $localeCode
        );

        if (empty($value)) {
            return ['error' => "No image found in attribute '{$attr->code}' for product '{$product->sku}'. Upload an image to the product first."];
        }

        // For gallery (array of paths), pick by index
        if ($attr->type === 'gallery') {
            $images = is_array($value) ? $value : explode(',', $value);
            $images = array_values(array_filter($images));

            if (empty($images)) {
                return ['error' => "Gallery attribute '{$attr->code}' has no images for product '{$product->sku}'."];
            }

            if ($imageIndex >= count($images)) {
                return ['error' => "Image index {$imageIndex} is out of range. Gallery '{$attr->code}' has ".count($images).' image(s) (0-indexed).'];
            }

            $value = $images[$imageIndex];
        }

        // Resolve to filesystem path
        $fullPath = Storage::disk('public')->path($value);

        if (! file_exists($fullPath)) {
            return ['error' => "Image file not found on disk for attribute '{$attr->code}'. Path: {$value}"];
        }

        return [
            'path'      => $fullPath,
            'attribute' => $attr,
            'index'     => $imageIndex,
            'scope'     => $scope,
        ];
    }

    /**
     * Resolve which image attribute to use.
     */
    public function resolveImageAttribute(Product $product, ?string $attributeCode): ?Attribute
    {
        if ($attributeCode) {
            return Attribute::where('code', $attributeCode)
                ->whereIn('type', ['image', 'gallery'])
                ->first();
        }

        // Auto-detect: find the first image/gallery attribute in the product's family
        $family = $product->attribute_family;

        if (! $family) {
            return null;
        }

        return $family->customAttributes()
            ->whereIn('type', ['image', 'gallery'])
            ->orderByRaw(GrammarQueryManager::getGrammar()->orderByField('type', ["'image'", "'gallery'"], 'text'))
            ->first();
    }

    /**
     * Get the scope key for the attribute value in product values.
     */
    public function getAttributeScope(Attribute $attribute): string
    {
        if ($attribute->isLocaleAndChannelBasedAttribute()) {
            return 'channel_locale_specific';
        }

        if ($attribute->isChannelBasedAttribute()) {
            return 'channel_specific';
        }

        if ($attribute->isLocaleBasedAttribute()) {
            return 'locale_specific';
        }

        return 'common';
    }

    /**
     * Save the edited image back to the product.
     */
    public function saveToProduct(Product $product, ?Attribute $attribute, int $imageIndex, string $scope, string $tempPath, $repo): array
    {
        if (! $attribute) {
            return ['error' => 'Cannot save — no attribute resolved.'];
        }

        $fileStorer = app(FileStorer::class);
        $targetPath = 'product'.DIRECTORY_SEPARATOR.$product->id.DIRECTORY_SEPARATOR.$attribute->code;

        $storedPath = $fileStorer->store(
            $targetPath,
            new File($tempPath),
            [FileStorer::HASHED_FOLDER_NAME_KEY => true],
        );

        if (! $storedPath) {
            return ['error' => 'Failed to store edited image.'];
        }

        $values = $product->values ?? [];
        $channelCode = core()->getRequestedChannelCode();
        $localeCode = core()->getRequestedLocaleCode();

        if ($attribute->type === 'gallery') {
            $currentValue = $attribute->getValueFromProductValues($values, $channelCode, $localeCode);
            $images = is_array($currentValue) ? $currentValue : ($currentValue ? explode(',', $currentValue) : []);
            $images = array_values(array_filter($images));

            if ($imageIndex < count($images)) {
                $images[$imageIndex] = $storedPath;
            } else {
                $images[] = $storedPath;
            }

            $newValue = $images;
        } else {
            $newValue = $storedPath;
        }

        // Write back to the correct scope
        match ($scope) {
            'channel_locale_specific' => $values['channel_locale_specific'][$channelCode][$localeCode][$attribute->code] = $newValue,
            'channel_specific'        => $values['channel_specific'][$channelCode][$attribute->code] = $newValue,
            'locale_specific'         => $values['locale_specific'][$localeCode][$attribute->code] = $newValue,
            default                   => $values['common'][$attribute->code] = $newValue,
        };

        $repo->updateWithValues(['values' => $values], $product->id);

        return ['stored_path' => $storedPath];
    }

    /**
     * Resolve an image-editing capable model for the provider.
     */
    public function resolveImageModel(ChatContext $context): string
    {
        $provider = $context->platform->provider;

        $imageModelPatterns = match ($provider) {
            'openai' => ['dall-e', 'gpt-image'],
            'gemini' => ['gemini-2', 'imagen'],
            'xai'    => ['grok'],
            default  => [],
        };

        if ($context->model) {
            foreach ($imageModelPatterns as $pattern) {
                if (stripos($context->model, $pattern) !== false) {
                    return $context->model;
                }
            }
        }

        $knownImageModels = match ($provider) {
            'openai' => ['gpt-image-1', 'gpt-image-1-mini', 'gpt-image-1.5', 'dall-e-3', 'dall-e-2'],
            'gemini' => ['gemini-2.0-flash-preview-image-generation', 'gemini-2.5-flash-image'],
            'xai'    => ['grok-2-image'],
            default  => [],
        };

        $models = $context->platform->model_list ?? [];

        foreach ($knownImageModels as $known) {
            if (in_array($known, $models, true)) {
                return $known;
            }
        }

        foreach ($models as $model) {
            foreach ($imageModelPatterns as $pattern) {
                if (stripos($model, $pattern) !== false) {
                    return $model;
                }
            }
        }

        return match ($provider) {
            'openai' => 'gpt-image-1',
            'gemini' => 'gemini-2.0-flash-preview-image-generation',
            'xai'    => 'grok-2-image',
            default  => $context->model,
        };
    }
}
