<?php

namespace Webkul\ElasticSearch\Console\Command;

use Illuminate\Console\Command;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

class Indexer extends Command
{
    protected $signature = 'indexer:index';

    protected $description = 'Index all into Elasticsearch';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if (env('ELASTICSEARCH_ENABLED', false)) {
            $start = microtime(true);

            // Indexing Products
            $products = Product::all();

            $productIndex = strtolower('products_'.core()->getRequestedChannelCode().'_'.core()->getRequestedLocaleCode().'_index');

            $dbProductIds = $products->pluck('id')->toArray();

            $elasticProductIds = collect(Elasticsearch::search([
                'index' => $productIndex,
                'body'  => [
                    '_source' => false, // Fetch only IDs
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                ],
            ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

            $productsToDelete = array_diff($elasticProductIds, $dbProductIds);

            foreach ($productsToDelete as $productId) {
                Elasticsearch::delete([
                    'index' => $productIndex,
                    'id'    => $productId,
                ]);
            }

            foreach ($products as $product) {
                Elasticsearch::index([
                    'index' => $productIndex,
                    'id'    => $product->id,
                    'body'  => $product->toArray(),
                ]);
            }

            $this->info('Products indexed successfully!');

            // Indexing Attributes
            $attributes = Attribute::all();

            $dbAttributeIds = $attributes->pluck('id')->toArray();

            $elasticAttributeIds = collect(Elasticsearch::search([
                'index' => 'attributes',
                'body'  => [
                    '_source' => false,
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                ],
            ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

            $attributesToDelete = array_diff($elasticAttributeIds, $dbAttributeIds);

            foreach ($attributesToDelete as $attributeId) {
                Elasticsearch::delete([
                    'index' => 'attributes',
                    'id'    => $attributeId,
                ]);
            }

            foreach ($attributes as $attribute) {
                Elasticsearch::index([
                    'index' => 'attributes',
                    'id'    => $attribute->id,
                    'body'  => $attribute->toArray(),
                ]);
            }

            $this->info('Attributes indexed successfully!');

            // Indexing AttributeFamilies
            $attributeFamilies = AttributeFamily::all();

            $dbAttributeFamilyIds = $attributeFamilies->pluck('id')->toArray();

            $elasticAttributeFamilyIds = collect(Elasticsearch::search([
                'index' => 'attribute_families',
                'body'  => [
                    '_source' => false,
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                ],
            ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

            $attributeFamilyToDelete = array_diff($elasticAttributeFamilyIds, $dbAttributeFamilyIds);

            foreach ($attributeFamilyToDelete as $attributeFamilyId) {
                Elasticsearch::delete([
                    'index' => 'attribute_families',
                    'id'    => $attributeFamilyId,
                ]);
            }

            foreach ($attributeFamilies as $attributeFamily) {
                Elasticsearch::index([
                    'index' => 'attribute_families',
                    'id'    => $attributeFamily->id,
                    'body'  => $attributeFamily->toArray(),
                ]);
            }

            $this->info('AttributeFamilies indexed successfully!');

            // Indexing Channels
            $channels = Channel::all();

            $dbChannelIds = $channels->pluck('id')->toArray();

            $elasticChannelIds = collect(Elasticsearch::search([
                'index' => 'channels',
                'body'  => [
                    '_source' => false,
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                ],
            ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

            $channelsToDelete = array_diff($elasticChannelIds, $dbChannelIds);

            foreach ($channelsToDelete as $channelId) {
                Elasticsearch::delete([
                    'index' => 'channels',
                    'id'    => $channelId,
                ]);
            }

            foreach ($channels as $channel) {
                Elasticsearch::index([
                    'index' => 'channels',
                    'id'    => $channel->id,
                    'body'  => $channel->toArray(),
                ]);
            }

            $this->info('Channels indexed successfully!');

            // Indexing Locales
            $locales = Locale::all();

            $dbLocaleIds = $locales->pluck('id')->toArray();

            $elasticLocaleIds = collect(Elasticsearch::search([
                'index' => 'locales',
                'body'  => [
                    '_source' => false,
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                ],
            ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

            $localesToDelete = array_diff($elasticLocaleIds, $dbLocaleIds);

            foreach ($localesToDelete as $localeId) {
                Elasticsearch::delete([
                    'index' => 'locales',
                    'id'    => $localeId,
                ]);
            }

            foreach ($locales as $locale) {
                Elasticsearch::index([
                    'index' => 'locales',
                    'id'    => $locale->id,
                    'body'  => $locale->toArray(),
                ]);
            }

            $this->info('Locales indexed successfully!');

            // Indexing Categories
            $categories = Category::all();

            $dbCategoryIds = $categories->pluck('id')->toArray();

            $elasticCategoryIds = collect(Elasticsearch::search([
                'index' => 'categories',
                'body'  => [
                    '_source' => false,
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                ],
            ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

            $categoriesToDelete = array_diff($elasticCategoryIds, $dbCategoryIds);

            foreach ($categoriesToDelete as $categoryId) {
                Elasticsearch::delete([
                    'index' => 'categories',
                    'id'    => $categoryId,
                ]);
            }

            foreach ($categories as $category) {
                Elasticsearch::index([
                    'index' => 'categories',
                    'id'    => $category->id,
                    'body'  => $category->toArray(),
                ]);
            }

            $this->info('Categories indexed successfully!');

            // Indexing CategoryFields
            $categoryFields = CategoryField::all();

            $dbCategoryFieldIds = $categoryFields->pluck('id')->toArray();

            $elasticCategoryFieldIds = collect(Elasticsearch::search([
                'index' => 'category_fields',
                'body'  => [
                    '_source' => false,
                    'query'   => [
                        'match_all' => new \stdClass,
                    ],
                ],
            ])['hits']['hits'])->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

            $categoryFieldsToDelete = array_diff($elasticCategoryFieldIds, $dbCategoryFieldIds);

            foreach ($categoryFieldsToDelete as $categoryFieldId) {
                Elasticsearch::delete([
                    'index' => 'category_fields',
                    'id'    => $categoryFieldId,
                ]);
            }

            foreach ($categoryFields as $categoryField) {
                Elasticsearch::index([
                    'index' => 'category_fields',
                    'id'    => $categoryField->id,
                    'body'  => $categoryField->toArray(),
                ]);
            }

            $this->info('CategoryField indexed successfully!');

            $end = microtime(true);

            echo 'The code took '.($end - $start)." seconds to complete.\n";
        } else {
            $this->info('ELASTICSEARCH IS NOT ENABLED.');
        }
    }
}
