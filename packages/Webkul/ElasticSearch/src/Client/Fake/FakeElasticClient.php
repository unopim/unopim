<?php

namespace Webkul\ElasticSearch\Client\Fake;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elastic\Elasticsearch\Traits\ClientEndpointsTrait;
use Elastic\Elasticsearch\Traits\EndpointTrait;
use Elastic\Elasticsearch\Traits\NamespaceTrait;
use Elastic\Transport\Transport;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/** Created for Test Cases for mocking the final class Elastic\Elasticsearch\Client */
class FakeElasticClient implements ClientInterface
{
    use ClientEndpointsTrait;
    use EndpointTrait;
    use NamespaceTrait;

    /**
     * Specify is the request is asyncronous
     */
    protected bool $async = false;

    /**
     * Enable or disable the x-elastic-meta-header
     */
    protected bool $elasticMetaHeader = true;

    /**
     * Enable or disable the response Exception
     */
    protected bool $responseException = true;

    /**
     * The endpoint namespace storage
     */
    protected array $namespace;

    public function __construct(
        protected $transport,
        protected $logger
    ) {
        $this->defaultTransportSettings($this->transport);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransport(): Transport
    {
        return $this->transport;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the default settings for Elasticsearch
     */
    protected function defaultTransportSettings($transport): void
    {
        $transport->setUserAgent('elasticsearch-php', Client::VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function setAsync(bool $async): self
    {
        $this->async = $async;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAsync(): bool
    {
        return $this->async;
    }

    /**
     * {@inheritdoc}
     */
    public function setElasticMetaHeader(bool $active): self
    {
        $this->elasticMetaHeader = $active;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getElasticMetaHeader(): bool
    {
        return $this->elasticMetaHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponseException(bool $active): self
    {
        $this->responseException = $active;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseException(): bool
    {
        return $this->responseException;
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        return new Elasticsearch;
    }
}
