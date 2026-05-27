<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Filesystem\FileStorer;

class AttachImage implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'attach_image';
            }

            public function description(): string
            {
                return 'Attach an uploaded image to a product. Pass attribute code (default "image"). For gallery attributes (type=gallery) the image is APPENDED to the existing list; for single-image attributes it REPLACES the current value. Use the product family\'s actual attribute code (e.g. "image", "gallery", "media_gallery").';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'       => $schema->string()->description('Product SKU to attach the image to'),
                    'attribute' => $schema->string()->description('Target attribute code on the product. Defaults to "image". For multiple/gallery images, pass the gallery attribute code (e.g. "gallery").'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                if (! $this->context->hasImages()) {
                    return json_encode(['error' => 'No image was uploaded in this session. Ask the user to upload an image first.']);
                }

                $sku = $request->string('sku')->toString();
                $attributeCode = $request->string('attribute')->toString() ?: 'image';

                $repo = app('Webkul\Product\Repositories\ProductRepository');
                $product = $repo->findOneByField('sku', $sku);

                if (! $product) {
                    return json_encode(['error' => "Product not found: {$sku}"]);
                }

                $attribute = Attribute::where('code', $attributeCode)->first();

                if (! $attribute) {
                    return json_encode(['error' => "Attribute '{$attributeCode}' not found in this UnoPim instance."]);
                }

                if (! in_array($attribute->type, ['image', 'gallery'])) {
                    return json_encode(['error' => "Attribute '{$attributeCode}' is type '{$attribute->type}', not an image/gallery attribute."]);
                }

                // Verify attribute is on the product's family
                $familyHasAttribute = DB::table('attribute_family_groups_mappings as afgm')
                    ->join('attribute_group_mappings as agm', 'agm.attribute_group_id', '=', 'afgm.attribute_group_id')
                    ->where('afgm.attribute_family_id', $product->attribute_family_id)
                    ->where('agm.attribute_id', $attribute->id)
                    ->exists();

                if (! $familyHasAttribute) {
                    return json_encode(['error' => "Attribute '{$attributeCode}' is not assigned to this product's attribute family. Add it to the family first."]);
                }

                $imagePath = $this->context->firstImagePath();

                if (! $imagePath || ! file_exists($imagePath)) {
                    return json_encode(['error' => 'Uploaded image file not found on disk.']);
                }

                try {
                    $fileStorer = app(FileStorer::class);
                    $storagePath = 'product'.DIRECTORY_SEPARATOR.$product->id.DIRECTORY_SEPARATOR.$attribute->code;

                    $storedPath = $fileStorer->store(
                        $storagePath,
                        new File($imagePath),
                        [FileStorer::HASHED_FOLDER_NAME_KEY => true],
                    );

                    if (! $storedPath) {
                        return json_encode(['error' => 'Failed to store image.']);
                    }

                    $scope = $this->resolveScope($attribute);
                    $values = $product->values ?? [];
                    $channelCode = core()->getRequestedChannelCode();
                    $localeCode = core()->getRequestedLocaleCode();

                    if ($attribute->type === 'gallery') {
                        // Append to existing gallery instead of overwriting.
                        $currentValue = $attribute->getValueFromProductValues($values, $channelCode, $localeCode);
                        $images = is_array($currentValue) ? $currentValue : ($currentValue ? explode(',', $currentValue) : []);
                        $images = array_values(array_filter($images));
                        $images[] = $storedPath;
                        $newValue = $images;
                    } else {
                        $newValue = $storedPath;
                    }

                    match ($scope) {
                        'channel_locale_specific' => $values['channel_locale_specific'][$channelCode][$localeCode][$attribute->code] = $newValue,
                        'channel_specific'        => $values['channel_specific'][$channelCode][$attribute->code] = $newValue,
                        'locale_specific'         => $values['locale_specific'][$localeCode][$attribute->code] = $newValue,
                        default                   => $values['common'][$attribute->code] = $newValue,
                    };

                    $repo->updateWithValues(['values' => $values], $product->id);

                    return json_encode([
                        'result' => [
                            'attached'  => true,
                            'sku'       => $sku,
                            'attribute' => $attribute->code,
                            'type'      => $attribute->type,
                            'image'     => $storedPath,
                        ],
                        'product_url' => route('admin.catalog.products.edit', $product->id),
                    ]);
                } catch (\Throwable $e) {
                    return json_encode(['error' => 'Failed to attach image: '.$e->getMessage()]);
                }
            }

            private function resolveScope(Attribute $attribute): string
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
        };
    }
}
