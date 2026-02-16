<?php

namespace Webkul\Shopify\Helpers\Iterator;

use Webkul\Shopify\Traits\ShopifyGraphqlRequest;

class ProductIterator implements \Iterator
{
    use ShopifyGraphqlRequest;

    private $cursor;                // Tracks the current cursor for pagination

    private $currentPageData;       // Holds data for the current page

    private $currentKey;            // Tracks the current index within the current page

    private $credential;            // Credentials for Shopify API

    private $mergedOptions;

    public function __construct($credential)
    {
        $this->credential = $credential;
        $this->cursor = null;       // Start with no cursor (first page)
        $this->currentPageData = [];
        $this->currentKey = 0;
        $this->fetchByCursor();
    }

    public function current(): mixed
    {
        return $this->currentPageData[$this->currentKey] ?? null;
    }

    public function key(): mixed
    {
        return $this->currentKey;
    }

    public function next(): void
    {
        $this->currentKey++;
        if ($this->currentKey >= count($this->currentPageData)) {
            $this->fetchByCursor();
        }
    }

    public function rewind(): void
    {
        if ($this->currentKey == 0) {
            return;
        }
        $this->cursor = null;       // Reset to the first page
        $this->currentPageData = [];
        $this->currentKey = 0;
        $this->fetchByCursor();     // Fetch the first page again
    }

    public function valid(): bool
    {
        return ! empty($this->currentPageData);
    }

    public function setCursor($cursor): void
    {
        $this->cursor = $cursor;
        $this->fetchByCursor();     // Fetch data based on the provided cursor
    }

    public function getCursor(): ?string
    {
        return $this->cursor;
    }

    private function fetchByCursor(): void
    {
        $this->currentPageData = [];
        try {
            $variables = [];
            if ($this->cursor) {
                $variables = [
                    'first'       => 20,
                    'afterCursor' => $this->cursor,
                ];
            }

            $mutationType = $this->cursor ? 'productAllvalueGettingByCursor' : 'productAllvalueGetting';

            $graphResponse = $this->requestGraphQlApiAction($mutationType, $this->credential, $variables);

            $graphqlProducts = ! empty($graphResponse['body']['data']['products']['edges'])
            ? $graphResponse['body']['data']['products']['edges']
            : [];
            $this->currentPageData = $graphqlProducts;
            // Update the cursor for the next page
            $this->cursor = ! empty($graphqlProducts) ? end($graphqlProducts)['cursor'] : null;
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        $this->currentKey = 0;
    }
}
