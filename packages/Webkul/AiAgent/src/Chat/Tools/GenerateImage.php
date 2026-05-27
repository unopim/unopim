<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Image;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\MagicAI\Enums\AiProvider;

class GenerateImage implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        $outer = $this;

        return new class($context, $outer) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected GenerateImage $outer)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'generate_image';
            }

            public function description(): string
            {
                return 'Generate an image from text and optionally attach to a product.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'prompt'    => $schema->string()->description('Detailed description of the image to generate (e.g. "Professional product photo of a red leather handbag on white background")'),
                    'sku'       => $schema->string()->description('Optional: Product SKU to attach the generated image to'),
                    'attribute' => $schema->string()->description('Optional: target attribute code on the product (default "image"). For multiple/gallery images pass the gallery attribute code (e.g. "gallery") — gallery images are APPENDED, single-image attributes are REPLACED.'),
                    'size'      => $schema->string()->enum(['1024x1024', '1024x1792', '1792x1024'])->description('Image size/aspect ratio'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                $prompt = $request->string('prompt')->toString();
                $sku = $request->string('sku')->toString() ?: null;
                $attributeCode = $request->string('attribute')->toString() ?: 'image';
                $size = $request->string('size')->toString() ?: '1024x1024';

                $aiProvider = AiProvider::from($this->context->platform->provider);

                if (! $aiProvider->supportsImages()) {
                    return json_encode([
                        'error' => "The current AI provider ({$aiProvider->label()}) does not support image generation. Switch to OpenAI, Gemini, or xAI to generate images.",
                    ]);
                }

                try {
                    // Configure the AI provider
                    $configKey = $aiProvider->configKey();
                    config([
                        "ai.providers.{$configKey}.key" => $this->context->platform->api_key,
                    ]);

                    if ($this->context->platform->api_url) {
                        config(["ai.providers.{$configKey}.url" => $this->context->platform->api_url]);
                    }

                    // Find an image-generation capable model
                    $imageModel = $this->outer->resolveImageModel($this->context);

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

                        if (! $product) {
                            $result['warning'] = "Product SKU '{$sku}' not found. Image generated but not attached.";
                        } else {
                            $attribute = Attribute::where('code', $attributeCode)->first();

                            if (! $attribute) {
                                $result['warning'] = "Attribute '{$attributeCode}' not found. Image generated but not attached.";
                            } elseif (! in_array($attribute->type, ['image', 'gallery'])) {
                                $result['warning'] = "Attribute '{$attributeCode}' is type '{$attribute->type}', not image/gallery. Image generated but not attached.";
                            } else {
                                $familyHasAttribute = DB::table('attribute_family_groups_mappings as afgm')
                                    ->join('attribute_group_mappings as agm', 'agm.attribute_group_id', '=', 'afgm.attribute_group_id')
                                    ->where('afgm.attribute_family_id', $product->attribute_family_id)
                                    ->where('agm.attribute_id', $attribute->id)
                                    ->exists();

                                if (! $familyHasAttribute) {
                                    $result['warning'] = "Attribute '{$attributeCode}' is not assigned to this product's family. Image generated but not attached.";
                                } else {
                                    $fileStorer = app(FileStorer::class);
                                    $targetPath = 'product'.DIRECTORY_SEPARATOR.$product->id.DIRECTORY_SEPARATOR.$attribute->code;

                                    $storedImage = $fileStorer->store(
                                        $targetPath,
                                        new File($fullPath),
                                        [FileStorer::HASHED_FOLDER_NAME_KEY => true],
                                    );

                                    if ($storedImage) {
                                        $scope = $this->outer->resolveScope($attribute);
                                        $values = $product->values ?? [];
                                        $channelCode = core()->getRequestedChannelCode();
                                        $localeCode = core()->getRequestedLocaleCode();

                                        if ($attribute->type === 'gallery') {
                                            $currentValue = $attribute->getValueFromProductValues($values, $channelCode, $localeCode);
                                            $images = is_array($currentValue) ? $currentValue : ($currentValue ? explode(',', $currentValue) : []);
                                            $images = array_values(array_filter($images));
                                            $images[] = $storedImage;
                                            $newValue = $images;
                                        } else {
                                            $newValue = $storedImage;
                                        }

                                        match ($scope) {
                                            'channel_locale_specific' => $values['channel_locale_specific'][$channelCode][$localeCode][$attribute->code] = $newValue,
                                            'channel_specific'        => $values['channel_specific'][$channelCode][$attribute->code] = $newValue,
                                            'locale_specific'         => $values['locale_specific'][$localeCode][$attribute->code] = $newValue,
                                            default                   => $values['common'][$attribute->code] = $newValue,
                                        };

                                        $repo->updateWithValues(['values' => $values], $product->id);

                                        $result['attached_to'] = $sku;
                                        $result['attribute'] = $attribute->code;
                                        $result['attribute_type'] = $attribute->type;
                                        $result['product_url'] = route('admin.catalog.products.edit', $product->id);
                                    }
                                }
                            }
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
            }
        };
    }

    /**
     * Resolve which scope key to write a value under, based on whether the
     * attribute is per-channel and/or per-locale.
     */
    public function resolveScope(Attribute $attribute): string
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
     * Resolve an image-generation capable model for the provider.
     *
     * Priority: user-selected model (if image-capable) → known valid models
     * from the platform list → fallback defaults.
     */
    public function resolveImageModel(ChatContext $context): string
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
