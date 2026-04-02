<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Http\File;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Core\Filesystem\FileStorer;

class AttachImage implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('attach_image')
            ->for('Attach an uploaded image to a product by SKU.')
            ->withStringParameter('sku', 'Product SKU to attach the image to')
            ->using(function (string $sku) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products.edit')) {
                    return $denied;
                }

                if (! $context->hasImages()) {
                    return json_encode(['error' => 'No image was uploaded in this session. Ask the user to upload an image first.']);
                }

                $repo = app('Webkul\Product\Repositories\ProductRepository');
                $product = $repo->findOneByField('sku', $sku);

                if (! $product) {
                    return json_encode(['error' => "Product not found: {$sku}"]);
                }

                $imagePath = $context->firstImagePath();

                if (! $imagePath || ! file_exists($imagePath)) {
                    return json_encode(['error' => 'Uploaded image file not found on disk.']);
                }

                try {
                    $fileStorer = app(FileStorer::class);
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
            });
    }
}
