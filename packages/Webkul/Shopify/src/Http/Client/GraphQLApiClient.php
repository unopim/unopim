<?php

namespace Webkul\Shopify\Http\Client;

use Illuminate\Support\Facades\Http;

class GraphQLApiClient
{
    protected $url;

    protected $accessToken;

    protected $apiVersion;

    protected $options;

    /**
     * Create object of this class
     */
    public function __construct(string $url, string $accessToken, string $apiVersion, array $options = [])
    {
        $this->apiVersion = $apiVersion;
        $this->accessToken = $accessToken;
        $this->options = $options;
        $this->url = $this->buildApiUrl($url);

    }

    /**
     * Build the API URL for making requests to the GraphQL endpoint.
     */
    protected function buildApiUrl(string $url): string
    {
        // Validate URL to prevent SSRF attacks
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL provided.');
        }

        // Ensure HTTPS for security
        $url = str_replace(['http://'], ['https://'], $url);

        // Extract host from URL to prevent path traversal
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['host'])) {
            throw new \InvalidArgumentException('Invalid URL host.');
        }

        return rtrim($url, '/').'/admin/api/'.$this->apiVersion.'/graphql.json';
    }

    /**
     * Retrieve the headers for making API requests to Shopify.
     */
    protected function getRequestHeaders(): array
    {
        return [
            'Accept'                 => 'application/json',
            'Content-type'           => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
        ];
    }

    /**
     * Create a request array for a specific API endpoint.
     */
    protected function createRequest(string $endpoint, array $parameters = [], array $data = [], $logger = null)
    {
        if (! array_key_exists($endpoint, $this->endpoints)) {
            return null;
        }

        $method = $this->endpoints[$endpoint]['method'];
        $query = $this->endpoints[$endpoint]['query'];
        $variables = $parameters;

        $body = ['query' => $query];

        if (! empty($variables)) {
            $body['variables'] = $variables;
        }

        return [
            'url'    => $this->url,
            'method' => $method,
            'body'   => json_encode($body),
        ];

    }

    /**
     * Send the HTTP request and create a response array.
     */
    protected function createResponse(array $request): array
    {
        try {
            $response = Http::withHeaders($this->getRequestHeaders())
                ->timeout($this->options['timeout'] ?? 120)
                ->retry(3, 100)
                ->send($request['method'], $request['url'], [
                    'body' => $request['body'],
                ]);

            return [
                'code' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ];
        }
    }

    /**
     * Make an API request to a specific endpoint with given parameters and payload.
     */
    public function request(string $endpoint, array $parameters = [], array $payload = [], $logger = null): array
    {
        $request = $this->createRequest($endpoint, $parameters, $payload, $logger);

        if (! $request) {
            return ['error' => 'Invalid endpoint'];
        }

        $response = $this->createResponse($request);

        // Rate Limit Handling
        if (isset($response['body']['errors'])) {
            $error = array_column($response['body']['errors'], 'message');
            if (in_array('Throttled', $error)) {
                $this->rateLimitRetryCount++;

                if ($this->rateLimitRetryCount > 5) {
                    $this->rateLimitRetryCount = 0;
                    return $response;
                }

                usleep(min(1_000_000 * (2 ** $this->rateLimitRetryCount), 30_000_000)); // Exponential backoff, max 30s
                $result = $this->request($endpoint, $parameters, $payload, $logger);
                $this->rateLimitRetryCount = 0;
                return $result;
            }
        }

        return $response;
    }

    /**
     * Stores Grapql mutations
     */
    protected $endpoints = [
        'getShopPublishedLocales' => [
            'query'  => '{shopLocales (published: true) {locale name primary published } }',
            'method' => 'POST',
        ],
        'createTranslation' => [
            'query'  => 'mutation CreateTranslation($id: ID!, $translations: [TranslationInput!]!) { translationsRegister(resourceId: $id, translations: $translations) {  userErrors { message field }  translations {  locale key value }, }}',
            'method' => 'POST',
        ],
        'createCollection' => [
            'query'  => 'mutation CollectionCreate($input: CollectionInput!) { collectionCreate(input: $input) { userErrors { field message } collection { id title descriptionHtml handle resourcePublications(first: 30) { edges { node { publication { id } } } } } } }',
            'method' => 'POST',
        ],

        'manualCollectionGetting' => [
            'query'  => 'query MyCollections($first: Int!) { collections(first: $first) { pageInfo { hasNextPage hasPreviousPage } edges { cursor node { id title handle} } } }',
            'method' => 'POST',
        ],

        'GetCollectionsByCursor' => [
            'query'  => 'query GetCollections($first: Int!, $afterCursor: String!) { collections(first: $first, after: $afterCursor) { pageInfo { hasNextPage hasPreviousPage } edges { cursor node { id title handle} } } }',
            'method' => 'POST',
        ],

        'updateCollection' => [
            'query'  => 'mutation updateCollectionTitle($input: CollectionInput!) { collectionUpdate(input: $input) { userErrors { field message } collection { id title descriptionHtml resourcePublications(first: 30) { edges { node { publication { id } } } } } } }',
            'method' => 'POST',
        ],

        'metafieldDefinitionCreate' => [
            'query'  => 'mutation MetafieldDefinitionCreateMutation($input: MetafieldDefinitionInput!) {  metafieldDefinitionCreate(definition: $input) { createdDefinition { id key namespace name ownerType validations { name value } } userErrors { code message field } } }',
            'method' => 'POST',
        ],

        'metafieldDefinitionUpdate' => [
            'query'  => 'mutation UpdateMetafieldDefinition($input: MetafieldDefinitionUpdateInput!) { metafieldDefinitionUpdate(definition: $input) { updatedDefinition { id name } userErrors { field message code } } }',
            'method' => 'POST',
        ],

        'getOneProduct' => [
            'query'  => '{ products(first: 1) { edges { node { id title descriptionHtml createdAt updatedAt } } } }',
            'method' => 'POST',
        ],

        'createProduct' => [
            'query'  => 'mutation ProductCreate($product: ProductCreateInput!, $media: [CreateMediaInput!] ) { productCreate(product: $product, media: $media) { product { id title handle resourcePublications(first: 30) { edges { node { publication { id } } } } media(first: 60) { nodes { id } } options { id name values optionValues { id name } } variants(first: 30) { edges { node { id }  } } } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'productPublish' => [
            'query'  => 'mutation productPublish($input: ProductPublishInput!) { productPublish(input: $input) { product { id title } shop { name } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'productUnpublish' => [
            'query'  => 'mutation productUnPublish($input: ProductUnpublishInput!) { productUnpublish(input: $input) { product { id title } shop { name } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'CreateProductVariantsDefault' => [
            'query'  => 'mutation CreateProductVariants($productId: ID!, $strategy: ProductVariantsBulkCreateStrategy, $variantsInput: [ProductVariantsBulkInput!]!) { productVariantsBulkCreate(productId: $productId, strategy: $strategy, variants: $variantsInput) { productVariants { id title inventoryItem { id inventoryLevels(first: 10) { edges { node { id location { id name address { address1 city province country zip } } } } } } selectedOptions { name value } } userErrors { field message } product { id options { id name values optionValues { id name hasVariants } } } } }',
            'method' => 'POST',
        ],

        'productVariantsBulkUpdate' => [
            'query'  => 'mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) { productVariantsBulkUpdate(productId: $productId, variants: $variants) { product { id } productVariants { id inventoryQuantity inventoryItem { id inventoryLevels(first: 10) { edges { node { id location { id name } } } } } metafields(first: 2) { edges { node { namespace key value } } } } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'productVariantsBulkUpdatewithproduct' => [
            'query'  => 'mutation productVariantsBulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!, $product: ProductUpdateInput) { productVariantsBulkUpdate(productId: $productId, variants: $variants) { product { id } productVariants { id inventoryItem { id inventoryLevels(first: 10) { edges { node { id location { id name } } } } } metafields(first: 2) { edges { node { namespace key value } } } } userErrors { field message } } productUpdate(product: $product) { product { id } } }',
            'method' => 'POST',
        ],

        'productVariantCreate' => [
            'query'  => 'mutation ProductVariantCreate($input: ProductVariantInput!) { productVariantCreate(input: $input) { productVariant { id price } userErrors { field message } }  }',
            'method' => 'POST',
        ],

        'productVariantDelete' => [
            'query'  => 'mutation ProductVariantDelete($id: ID!) { productVariantDelete(id: $id) { product { id } } }',
            'method' => 'POST',
        ],

        'UpdateCostPerItem' => [
            'query'  => 'mutation inventoryItemUpdate($id: ID!, $input: InventoryItemUpdateInput!) { inventoryItemUpdate(id: $id, input: $input) { inventoryItem { id inventoryLevels(first: 10) { edges { node { id location { id name address { address1 city province country zip } } } } } unitCost { amount } tracked countryCodeOfOrigin provinceCodeOfOrigin harmonizedSystemCode countryHarmonizedSystemCodes(first: 1) { edges { node { harmonizedSystemCode countryCode } } } } userErrors { message } } }',
            'method' => 'POST',
        ],

        'inventoryAdjustQuantities' => [
            'query'  => 'mutation inventoryAdjustQuantities($input: InventoryAdjustQuantitiesInput!) { inventoryAdjustQuantities(input: $input) { userErrors { field message } inventoryAdjustmentGroup { createdAt reason referenceDocumentUri changes { name delta } } } }',
            'method' => 'POST',
        ],

        'updateImageToProduct' => [
            'query'  => 'mutation productAppendImages($inputImg: ProductAppendImagesInput! ) { productAppendImages(input: $inputImg) { newImages { id altText } userErrors { field message } }  }',
            'method' => 'POST',
        ],

        'productUpdate' => [
            'query'  => 'mutation ProductUpdate($product: ProductUpdateInput!, $media: [CreateMediaInput!]) { productUpdate(product: $product, media: $media) { product { id title handle productType vendor tags descriptionHtml resourcePublications(first: 30) { edges { node { publication { id } } } } options { id name values optionValues { id name hasVariants } } media(first: 30) { nodes { id } } collections(first: 10) { edges { node { id handle title } } } variants(first: 10) { edges { node { id }  } } } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'productUpdateWithVariantGetting' => [
            'query'  => 'mutation ProductUpdate($product: ProductUpdateInput!, $media: [CreateMediaInput!]) { productUpdate(product: $product, media: $media) { product { id title handle productType vendor tags descriptionHtml resourcePublications(first: 30) { edges { node { publication { id } } } } options { id name values optionValues { id name hasVariants } } media(first: 30) { nodes { id } } collections(first: 10) { edges { node { id handle title } } } variants(first: 10) { edges { node { id }  } } } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'productImageUpdate' => [
            'query'  => 'mutation productImageUpdate($productId: ID!, $image: ImageInput!) { productImageUpdate(productId: $productId, image: $image) { image { id altText src } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'getPublications' => [
            'query'  => 'query publications { publications(first: 250) { pageInfo { hasNextPage hasPreviousPage } edges  { cursor node { id name supportsFuturePublishing app { id title description developerName } } } } }',
            'method' => 'POST',
        ],

        'createOptions' => [
            'query'  => 'mutation createOptions($productId: ID!, $options: [OptionCreateInput!]!) { productOptionsCreate(productId: $productId, options: $options) { userErrors { field message code } product { id variants(first: 5) { nodes { id title selectedOptions { name value } } } options { id name values position optionValues { id name hasVariants } } } } }',
            'method' => 'POST',
        ],

        'CreateProductVariants' => [
            'query'  => 'mutation CreateProductVariants($productId: ID!, $strategy: ProductVariantsBulkCreateStrategy, $variantsInput: [ProductVariantsBulkInput!]!, $media: [CreateMediaInput!]!) { productVariantsBulkCreate(productId: $productId, strategy: $strategy, variants: $variantsInput, media: $media) { productVariants { id title inventoryItem { id inventoryLevels(first: 10) { edges { node { id location { id name address { address1 city province country zip } } } } } } selectedOptions { name value } } userErrors { field message } product { id media(first: 30) { nodes { id } }  options { id name values optionValues { id name hasVariants } } } } }',
            'method' => 'POST',
        ],

        'getFullfillmentAndLocation' => [
            'query'  => '{ locations(first: 10) { edges { node { id name } } } shop { fulfillmentServices { id serviceName handle inventoryManagement } } }',
            'method' => 'POST',
        ],

        'inventoryBulkToggleActivation' => [
            'query'  => 'mutation InventoryBulkToggleActivation($inventoryItemId: ID!, $inventoryItemUpdates: [InventoryBulkToggleActivationInput!]!) { inventoryBulkToggleActivation(inventoryItemId: $inventoryItemId   inventoryItemUpdates: $inventoryItemUpdates ) {   userErrors {  message     __typename    }   __typename }}',
            'method' => 'POST',
        ],

        'productGettingOptions' => [
            'query'  => 'query { products(first: 50, reverse: true) { edges { cursor node { id productType vendor options { id name position values } variants(first: 30) { edges { node { id title price sku compareAtPrice selectedOptions { name value } } } } } } } }',
            'method' => 'POST',
        ],

        'productOptionByCursor' => [
            'query'  => 'query GetProducts($first: Int!, $afterCursor: String!) { products(first: $first, after: $afterCursor, reverse: true) { edges { cursor node { id productType vendor options { id name position values } variants(first: 30) { edges { node { id title price sku compareAtPrice selectedOptions { name value } } } } } } } }',
            'method' => 'POST',
        ],

        'productAllvalueGetting' => [
            'query'  => 'query { products(first: 20, reverse: true) { edges { cursor node {  id title description resourcePublications(first: 10) { nodes { isPublished publication { name id } } } descriptionHtml productType vendor tags status handle publishedAt createdAt updatedAt  collections(first: 10) { edges { node { handle id title } } } media(first: 30) { nodes { id __typename ... on MediaImage { image { altText url } } } } options { id name values } variants(first: 10) { pageInfo { hasNextPage } edges { cursor node { id title price sku compareAtPrice barcode taxable  inventoryQuantity inventoryPolicy metafields(first: 100) { edges { cursor node  {  id namespace key value type } } } inventoryItem { unitCost { amount } id tracked requiresShipping measurement { weight { value unit } } inventoryLevels(first: 10) { edges { node { id location { id name address { address1 city province country zip } } } } } } selectedOptions { name value } media(first: 10) { nodes { id __typename ... on MediaImage { image { altText url } } } } } } } seo { title description } metafields(first: 100) { edges { node { id namespace type key value } } } } } } }',
            'method' => 'POST',
        ],

        'gettingRemaingVariant' => [
            'query'  => 'query GetProductVariants($productId: ID!, $after: String) { product(id: $productId) { title variants(first: 30, after: $after) { edges { cursor node { id title price sku compareAtPrice barcode taxable  inventoryQuantity inventoryPolicy metafields(first: 100) { edges { cursor node  {  id namespace key value type } } } inventoryItem { unitCost { amount } id tracked requiresShipping measurement { weight { value unit } } inventoryLevels(first: 10) { edges { node { id location { id name address { address1 city province country zip } } } } } } selectedOptions { name value } media(first: 10) { nodes { id __typename ... on MediaImage { image { altText url } } } } } } pageInfo { hasNextPage } } } }',
            'method' => 'POST',
        ],

        'productAllvalueGettingByCursor' => [
            'query'  => 'query GetProducts($first: Int!, $afterCursor: String!) { products(first: $first, after: $afterCursor, reverse: true) { edges { cursor node {  id title description resourcePublications(first: 10) { nodes { isPublished publication { name id } } } descriptionHtml productType vendor tags status handle publishedAt createdAt updatedAt  collections(first: 10) { edges { node { handle id title } } } media(first: 30) { nodes { id __typename ... on MediaImage { image { altText url } } } } options { id name values } variants(first: 10) { pageInfo { hasNextPage } edges { cursor node { id title price sku compareAtPrice barcode taxable inventoryQuantity inventoryPolicy metafields(first: 100) { edges { cursor node  {  id namespace key value type } } } inventoryItem { unitCost { amount } id tracked requiresShipping measurement { weight { value unit } } inventoryLevels(first: 10) { edges { node { id location { id name address { address1 city province country zip } } } } } }  selectedOptions { name value } media(first: 10) { nodes { id __typename ... on MediaImage { image { altText url } } } } image { id originalSrc altText } } } } seo { title description } metafields(first: 100) { edges { node { id namespace key type value } } } } } } }',
            'method' => 'POST',
        ],

        'productMetafields' => [
            'query'  => 'query GetProduct($id: ID!, $first: Int!) { product(id: $id) { metafields(first: $first) { edges { cursor node  {  id namespace key value type } } } } }',
            'method' => 'POST',
        ],

        'productMetafieldsByCursor' => [
            'query'  => 'query GetProduct($id: ID!, $first: Int!, $afterCursor: String!) { product(id: $id) { metafields(first: $first, after: $afterCursor) { edges { cursor node  {  id namespace key value type } } } } }',
            'method' => 'POST',
        ],

        'deleteMetafield' => [
            'query'  => 'mutation metafieldDelete($input: MetafieldDeleteInput!) { metafieldDelete(input: $input) { deletedId userErrors { field message } } }',
            'method' => 'POST',
        ],

        'productVariantMetafield' => [
            'query'  => 'query productVariant($id: ID!, $first: Int!) { productVariant(id: $id) { metafields(first: $first) { edges { cursor node  {  id namespace key value type } } } } }',
            'method' => 'POST',
        ],

        'productVariantMetafieldByCursor' => [
            'query'  => 'query productVariant($id: ID!, $first: Int!, $afterCursor: String!) { productVariant(id: $id) {   metafields(first: $first, after: $afterCursor) { edges { cursor node  {  id namespace key value type } } } } }',
            'method' => 'POST',
        ],

        'productOptionUpdated' => [
            'query'  => 'mutation UpdateOptionNameAndPosition($productId: ID!, $optionInput: OptionUpdateInput!, $optionValuesToUpdate: [OptionValueUpdateInput!], $optionValuesToDelete: [ID!], $optionValuesToAdd: [OptionValueCreateInput!]) { productOptionUpdate(productId: $productId, option: $optionInput, optionValuesToUpdate: $optionValuesToUpdate, optionValuesToDelete: $optionValuesToDelete, optionValuesToAdd: $optionValuesToAdd) { product { options { id name position optionValues { id name hasVariants } } } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'productUpdateMedia' => [
            'query'  => 'mutation productUpdateMedia($media: [UpdateMediaInput!]!, $productId: ID!) { productUpdateMedia(media: $media, productId: $productId) { media { alt id } } }',
            'method' => 'POST',
        ],

        'productFileUpdate' => [
            'query'  => 'mutation FileUpdate($input: [FileUpdateInput!]!) { fileUpdate(files: $input) { userErrors { code field message } files { alt } } }',
            'method' => 'POST',
        ],

        'productDeleteMedia' => [
            'query'  => 'mutation productDeleteMedia($mediaIds: [ID!]!, $productId: ID!) { productDeleteMedia(mediaIds: $mediaIds, productId: $productId) { deletedMediaIds deletedProductImageIds mediaUserErrors { field message } product { id title media(first: 25) { nodes { alt mediaContentType status } } } } }',
            'method' => 'POST',
        ],

        'productCreateMedia' => [
            'query'  => 'mutation productCreateMedia($media: [CreateMediaInput!]!, $productId: ID!) { productCreateMedia(media: $media, productId: $productId) { media { alt id mediaContentType status } mediaUserErrors { field message } product { id title } } }',
            'method' => 'POST',
        ],

        'getignLocations' => [
            'query'  => '{ locations(first: 80, includeLegacy: true) { edges { node { id name  fulfillmentService { id } } } } }',
            'method' => 'POST',
        ],

        'productDelete' => [
            'query'  => 'mutation productDelete($input: ProductDeleteInput!) { productDelete(input: $input) { deletedProductId userErrors { field message } } }',
            'method' => 'POST',
        ],

        'metafieldDefinitionsProductVariantType' => [
            'query'  => 'query getMetafieldDefinitions($first: Int!, $after: String) { metafieldDefinitions(first: $first, after: $after, ownerType: PRODUCTVARIANT, constraintStatus: UNCONSTRAINED_ONLY) { edges { cursor node { namespace key name type { name category }} } } }',
            'method' => 'POST',
        ],

        'metafieldDefinitionsProductType' => [
            'query'  => 'query getMetafieldDefinitions($first: Int!, $after: String) { metafieldDefinitions(first: $first, after: $after, ownerType: PRODUCT, constraintStatus: UNCONSTRAINED_ONLY) { edges { cursor node { namespace key name type { name category }} } } }',
            'method' => 'POST',
        ],

        'publishablePublish' => [
            'query'  => 'mutation PublishablePublish($collectionId: ID!, $input: [PublicationInput!]!) { publishablePublish(id: $collectionId, input: $input) { userErrors { field message } } }',
            'method' => 'POST',
        ],

        'unpublishableUnpublish' => [
            'query' => 'mutation PublishableUnpublish($collectionId: ID!, $input: [PublicationInput!]!) {
            publishableUnpublish(id: $collectionId, input: $input){ userErrors { field message } } }',
            'method' => 'POST',
        ],

        'getTotalProductCount'  => [
            'query'  => 'query { productsCount(query: "id:>=1") { count } }',
            'method' => 'POST',
        ],

        'stagedUploadsCreate' => [
            'query'  => 'mutation stagedUploadsCreate($input: [StagedUploadInput!]!) { stagedUploadsCreate(input: $input) { stagedTargets { url resourceUrl parameters { name value } } userErrors { field message } } }',
            'method' => 'POST',
        ],

        'getProductById' => [
            'query'  => 'query GetProduct($id: ID!) { product(id: $id) { id title descriptionHtml vendor productType tags status variants(first: 1) { edges { node { price compareAtPrice sku barcode inventoryQuantity inventoryItem { measurement { weight { value unit } } } } } } } }',
            'method' => 'POST',
        ],

        'webhookSubscriptionCreate' => [
            'query'  => 'mutation WebhookSubscriptionCreate($topic: WebhookSubscriptionTopic!, $webhookSubscription: WebhookSubscriptionInput!) { webhookSubscriptionCreate(topic: $topic, webhookSubscription: $webhookSubscription) { webhookSubscription { id } userErrors { field message } } }',
            'method' => 'POST',
        ],
    ];
}
