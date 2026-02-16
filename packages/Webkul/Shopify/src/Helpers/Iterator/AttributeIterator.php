<?php

namespace Webkul\Shopify\Helpers\Iterator;

use Webkul\Shopify\Traits\ShopifyGraphqlRequest;

class AttributeIterator implements \Iterator
{
    use ShopifyGraphqlRequest;

    private $cursor;

    private $currentPageData;

    private $currentKey;

    private $credential;

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
                    'first'       => 50,
                    'afterCursor' => $this->cursor,
                ];
            }

            $mutationType = $this->cursor ? 'productOptionByCursor' : 'productGettingOptions';
            $graphResponse = $this->requestGraphQlApiAction($mutationType, $this->credential, $variables);

            $edges = $graphResponse['body']['data']['products']['edges'] ?? [];
            $this->currentPageData = $this->formatedAttributeAndOption($edges);
            // Update the cursor for the next page
            $this->cursor = ! empty($edges) ? end($edges)['cursor'] : null;

        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        $this->currentKey = 0;
    }

    /**
     * Formating Attribute and attriute Option
     */
    public function formatedAttributeAndOption(array $options): array
    {
        $optionsArray = [];
        foreach ($options as $option) {
            $productOptions = $option['node']['options'];
            foreach ($productOptions as $productOption) {
                if ($productOption['name'] == 'Title' && in_array('Default Title', $productOption['values'])) {
                    continue;
                }

                $modified_array = array_map(function ($string) {
                    return trim(preg_replace('/[^A-Za-z0-9]+/', '-', $string), '-');
                }, $productOption['values'] ?? []);

                $optionsArray[] = [
                    'name' => trim(preg_replace('/[^A-Za-z0-9]+/', '_', $productOption['name'])),
                    'type' => 'select',
                    'code' => $modified_array,
                ];
            }
        }

        return $optionsArray;
    }
}
