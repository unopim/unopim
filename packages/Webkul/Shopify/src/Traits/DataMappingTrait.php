<?php

namespace Webkul\Shopify\Traits;

/**
 * data mapping
 */
trait DataMappingTrait
{
    /**
     * Check if the mapping exists in the database for the given item.
     */
    protected function checkMappingInDb(array $item, string $entity = self::UNOPIM_ENTITY_NAME): ?array
    {
        $code = $item['code'] ?? null;
        if ($code) {
            $mappingCheck = $this->shopifyMappingRepository->where('code', $code)
                ->where('entityType', $entity)
                ->where('apiUrl', $this?->credential?->shopUrl)
                ->get();

            return $mappingCheck->toArray();
        }

        return null;
    }

    /**
     * Check if the mapping exists in the database for an image.
     */
    protected function checkMappingInDbForImage(string $code, string $entity, string $productSku): ?array
    {
        if ($code) {
            $mappingCheck = $this->shopifyMappingRepository->where('code', $code)
                ->where('entityType', $entity)
                ->where('relatedSource', $productSku)
                ->where('apiUrl', $this?->credential?->shopUrl)
                ->get();

            return $mappingCheck->toArray();
        }

        return null;
    }

    /**
     * Check if the mapping exists in the database for an image.
     */
    protected function getAllImageMappingBySku(string $entity, string $productId, array $attr = [], $galleryAttr = true): ?array
    {
        $mappingCheck = $this->shopifyMappingRepository
            ->where('entityType', $entity)
            ->where('relatedId', $productId)
            ->where('apiUrl', $this?->credential?->shopUrl);
        if ($galleryAttr) {
            $mappingCheck = $mappingCheck->whereNot(function ($query) use ($attr) {
                foreach ($attr as $id) {
                    $query->orWhere('code', 'like', "%{$id}%");
                }
            });

        } else {
            $mappingCheck = $mappingCheck->whereNotIn('code', $attr);
        }

        return $mappingCheck->get()->toArray();
    }

    /**
     * Check if the mapping exists in the database for an image.
     */
    protected function checkMappingInDbForGallery(string $attributeCode, string $entity, string $productSku, $asset = false): ?array
    {
        if ($attributeCode) {
            $mappedData = $this->shopifyMappingRepository
                ->where('entityType', $entity)
                ->where('relatedSource', $productSku)
                ->where('apiUrl', $this?->credential?->shopUrl)
                ->get();
            $mappedData = $mappedData->toArray();
            $filteredMappedData = [];

            foreach ($mappedData as $data) {
                $code = explode('_', $data['code']);
                $endNumber = end($code);
                array_pop($code);
                $code = implode('_', $code);
                if ($code !== $attributeCode) {
                    continue;
                }
                if ($asset) {
                    $filteredMappedData[$endNumber] = $data;
                } else {
                    $filteredMappedData[] = $data;
                }
            }

            return $filteredMappedData;
        }

        return null;
    }

    /**
     * Handle the Shopify API response after an API request.
     *
     * @param  array  $formattedItem
     */
    protected function handleAfterApiRequest(array $item, array $responseData, ?array $mapping, int $exportId, array $formateItem = []): array
    {
        $response = [];
        $data = array_values($responseData['body']['data']);
        $entityFound = self::UNOPIM_ENTITY_NAME == 'product' ? 'product' : 'collection';
        if (empty($data[0]['userErrors']) && ! ($mapping)) {
            $mappingData = [
                'entityType'    => self::UNOPIM_ENTITY_NAME,
                'code'          => $item['code'],
                'externalId'    => $data[0][$entityFound]['id'],
                'jobInstanceId' => $exportId,
                'apiUrl'        => $this?->credential?->shopUrl,
            ];

            $this->shopifyMappingRepository->create($mappingData);
        } elseif (isset($data[0]['userErrors'][0]['message']) && $data[0]['userErrors'][0]['message'] == 'Collection does not exist' && $mapping) {
            $this->shopifyMappingRepository->delete($mapping[0]['id']);

            $credential = [
                'shopUrl'     => $this->credential->shopUrl,
                'accessToken' => $this->credential->accessToken,
                'apiVersion'  => $this->credential->apiVersion,
            ];

            unset($formateItem['id']);
            $response = $this->requestGraphQlApiAction('createCollection', $credential, ['input' => $formateItem]);

            $response = $response['body']['data']['collectionCreate'] ?? [];
            if (! empty($response['collection']['id'])) {
                $mappingData = [
                    'entityType'    => self::UNOPIM_ENTITY_NAME,
                    'code'          => $item['code'],
                    'externalId'    => $response['collection']['id'],
                    'jobInstanceId' => $exportId,
                    'apiUrl'        => $this->credential->shopUrl,
                ];

                $this->shopifyMappingRepository->create($mappingData);
            }
        } else {
            $mappingData = [
                'entityType'    => self::UNOPIM_ENTITY_NAME,
                'code'          => $item['code'],
                'externalId'    => $data[0][$entityFound]['id'],
                'jobInstanceId' => $exportId,
                'apiUrl'        => $this->credential->shopUrl,
            ];

            $this->shopifyMappingRepository->update($mappingData, $mapping[0]['id']);
        }

        return $response;
    }

    /**
     * Create a parent mapping.
     */
    protected function parentMapping(string $code, string $id, int $exportId, $productId = null): void
    {
        $mappingData = [
            'entityType'    => self::UNOPIM_ENTITY_NAME,
            'code'          => $code,
            'externalId'    => $id,
            'relatedId'     => $productId,
            'jobInstanceId' => $exportId,
            'apiUrl'        => $this->credential->shopUrl,
        ];

        $this->shopifyMappingRepository->create($mappingData);
    }

    /**
     * Update an existing mapping.
     */
    protected function updateMapping(string $code, string $id, int $exportId, ?int $mappingId = null): void
    {
        if ($mappingId) {
            $mappingData = [
                'entityType'    => self::UNOPIM_ENTITY_NAME,
                'code'          => $code,
                'externalId'    => $id,
                'jobInstanceId' => $exportId,
                'apiUrl'        => $this->credential->shopUrl,
            ];

            $this->shopifyMappingRepository->update($mappingData, $mappingId);
        }
    }

    /**
     * Create or update image mapping.
     */
    protected function imageMapping(
        string $entityType,
        string $code,
        string $externalId,
        int $jobInstanceId,
        string $productId,
        string $productSku,
        ?int $mappingId = null
    ): void {
        $mappingData = [
            'entityType'    => $entityType,
            'code'          => $code,
            'externalId'    => $externalId,
            'jobInstanceId' => $jobInstanceId,
            'relatedId'     => $productId,
            'relatedSource' => $productSku,
            'apiUrl'        => $this->credential->shopUrl,
        ];

        $this->shopifyMappingRepository->create($mappingData);
    }

    /**
     * Delete product mapping.
     */
    protected function deleteProductMapping(string $productId): void
    {
        $mappings = $this->shopifyMappingRepository->where('externalId', $productId)
            ->orWhere('relatedId', $productId)->delete();
    }

    /**
     * Delete productvariant mapping.
     */
    protected function deleteProductVariantMapping(string $variant, string $sku): void
    {
        $mappings = $this->shopifyMappingRepository->where('externalId', $variant)->delete();
    }

    /**
     * Delete productvariant mapping.
     */
    protected function deleteProductMediaMapping(array $mediaIds): void
    {
        $mappings = $this->shopifyMappingRepository->whereIN('externalId', $mediaIds)->delete();
    }

    /**
     * Delete media mapping.
     */
    protected function deleteProductMediaMappingById(string $productId, string $entityType): void
    {
        $this->shopifyMappingRepository
            ->where('relatedId', $productId)
            ->where('entityType', $entityType)
            ->delete();
    }
}
