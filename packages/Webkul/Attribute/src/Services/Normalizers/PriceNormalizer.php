<?php

namespace Webkul\Attribute\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeNormalizerInterface;
use Webkul\Core\Models\ChannelProxy;

class PriceNormalizer extends AbstractNormalizer implements AttributeNormalizerInterface
{
    /**
     * Normalize the given attribute value.
     */
    public function getData(mixed $data, ?Attribute $attribute = null, array $options = [])
    {
        $format = $options['format'] ?? 'default';

        switch ($format) {
            case 'datagrid':
                return $this->datagridFormat($data, $options);
            default:
                return $data;
        }
    }

    /**
     * Format the data for datagrid.
     */
    protected function datagridFormat(mixed $data, array $options = [])
    {
        if (! is_array($data)) {
            return $data;
        }

        $data = $this->filterByChannelCurrencies($data, $options['channel'] ?? null);

        $format = [];

        foreach ($data as $key => $value) {
            $format[] = core()->currencySymbol($key).' '.$value;
        }

        return implode(', ', $format);
    }

    /**
     * Restrict the price payload to the currencies active on the given channel.
     *
     * Falls back to the unfiltered payload when no channel context is available
     * or when the channel cannot be resolved, preserving legacy callers that
     * invoke the normalizer without channel metadata.
     */
    protected function filterByChannelCurrencies(array $data, ?string $channelCode): array
    {
        if (empty($channelCode)) {
            return $data;
        }

        $channel = ChannelProxy::modelClass()::with('currencies')
            ->where('code', $channelCode)
            ->first();

        if (! $channel) {
            return $data;
        }

        $allowed = $channel->currencies->pluck('code')->all();

        if (empty($allowed)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($allowed));
    }
}
