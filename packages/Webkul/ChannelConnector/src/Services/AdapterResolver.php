<?php

namespace Webkul\ChannelConnector\Services;

use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Models\ChannelConnector;

class AdapterResolver
{
    protected array $adapters = [];

    public function register(string $channelType, string $adapterClass): void
    {
        $this->adapters[$channelType] = $adapterClass;
    }

    public function resolve(ChannelConnector $connector): ChannelAdapterContract
    {
        $channelType = $connector->channel_type;

        if (! isset($this->adapters[$channelType])) {
            throw new \InvalidArgumentException(
                trans('channel_connector::app.errors.CHN-001')." [{$channelType}]"
            );
        }

        $adapterClass = $this->adapters[$channelType];

        $adapter = app($adapterClass);

        if (! $adapter instanceof ChannelAdapterContract) {
            throw new \InvalidArgumentException(
                "Adapter [{$adapterClass}] must implement ChannelAdapterContract."
            );
        }

        $credentials = $connector->credentials ?? [];

        $adapter->setCredentials($credentials);

        return $adapter;
    }

    public function resolveByType(string $channelType, array $credentials): ChannelAdapterContract
    {
        if (! isset($this->adapters[$channelType])) {
            throw new \InvalidArgumentException(
                trans('channel_connector::app.errors.CHN-001')." [{$channelType}]"
            );
        }

        $adapter = app($this->adapters[$channelType]);
        $adapter->setCredentials($credentials);

        return $adapter;
    }

    public function getRegisteredTypes(): array
    {
        return array_keys($this->adapters);
    }
}
