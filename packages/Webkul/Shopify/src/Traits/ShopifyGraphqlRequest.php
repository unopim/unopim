<?php

namespace Webkul\Shopify\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Shopify\Exceptions\InvalidCredential;
use Webkul\Shopify\Http\Client\GraphQLApiClient;

/**
 * Trait for making GraphQL API requests to Shopify.
 */
trait ShopifyGraphqlRequest
{
    /**
     * Sends a GraphQL API request to Shopify based on the provided mutation type and credentials.
     *
     * @param  string  $mutationType  The GraphQL mutation type or query to execute.
     * @param  array|null  $credential  Optional. Shopify credentials including 'shopUrl', 'accessToken', and 'apiVersion'.
     * @param  array|null  $formatedVariable  Optional. Variables to be sent with the GraphQL query or mutation.
     * @return array The response from Shopify's GraphQL API.
     */
    protected function requestGraphQlApiAction(string $mutationType, ?array $credential = [], ?array $formatedVariable = []): array
    {
        if (! $credential || ! isset($credential['shopUrl'], $credential['accessToken'], $credential['apiVersion'])) {
            throw new \InvalidArgumentException('Invalid Shopify credentials provided.');
        }

        $client = new GraphQLApiClient($credential['shopUrl'], $credential['accessToken'], $credential['apiVersion']);

        $response = $client->request($mutationType, $formatedVariable);

        if (
            (! $response['code'] || in_array($response['code'], [401, 404]))
            && property_exists($this, 'export')
            && $this->export instanceof JobTrack
        ) {
            $this->export->state = ExportHelper::STATE_FAILED;
            $this->export->errors = [trans('shopify::app.shopify.export.errors.invalid-credential')];
            $this->export->save();

            throw new InvalidCredential;
        }

        return $response;
    }

    /**
     * Attempts to download the image from the provided URL.
     */
    public function handleUrlField(mixed $imageUrl, string $imagePath): string|bool
    {

        try {
            $parsedUrl = parse_url($imageUrl);
            $host = $parsedUrl['host'] ?? '';
            if (! $host || filter_var(gethostbyname($host), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return $imageUrl; // Return original URL if we can't safely fetch
            }

            $response = Http::get($imageUrl);

            if ($response->failed()) {
                return false;
            }

            $imageContents = $response->body();

            $path = parse_url($imageUrl, PHP_URL_PATH);

            $fileName = basename($path);

            if (! preg_match('/\.[a-zA-Z0-9]+$/', $fileName)) {
                $fileName .= '.png';
            }

            $path = $imagePath.$fileName;

            StorageFacade::disk('public')->put($path, $imageContents);

            return $path;
        } catch (\Exception $e) {
            return false;
        }
    }
}
