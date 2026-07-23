<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Product\Repositories\ProductRepository;

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
                return 'Attach an uploaded image to a product by SKU.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku' => $schema->string()->description('Product SKU to attach the image to'),
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

                $repo = resolve(ProductRepository::class);
                $product = $repo->findOneByField('sku', $sku);

                if (! $product) {
                    return json_encode(['error' => "Product not found: {$sku}"]);
                }

                $imagePath = $this->context->firstImagePath();

                if (! $imagePath || ! file_exists($imagePath)) {
                    return json_encode(['error' => 'Uploaded image file not found on disk.']);
                }

                try {
                    $fileStorer = resolve(FileStorer::class);
                    $storagePath = 'product'.DIRECTORY_SEPARATOR.$product->id.DIRECTORY_SEPARATOR.'image';

                    $storedPath = $fileStorer->store(
                        $storagePath,
                        new File($imagePath),
                        [FileStorer::HASHED_FOLDER_NAME_KEY => true],
                    );

                    if (! $storedPath) {
                        return json_encode(['error' => 'Failed to store image.']);
                    }

                    $values = $product->values ?? [];
                    $values['common']['image'] = $storedPath;

                    $repo->updateWithValues(['values' => $values], $product->id);

                    return json_encode([
                        'result' => [
                            'attached' => true,
                            'sku'      => $sku,
                            'image'    => $storedPath,
                        ],
                        'product_url' => route('admin.catalog.products.edit', $product->id),
                    ]);
                } catch (\Throwable $e) {
                    return json_encode(['error' => 'Failed to attach image: '.$e->getMessage()]);
                }
            }
        };
    }
}
