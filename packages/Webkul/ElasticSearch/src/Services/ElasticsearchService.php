<?php

namespace Webkul\ElasticSearch\Services;

use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService
{
    private $client;

    private $isEnabled;

    public function __construct()
    {
        $this->isEnabled = env('ELASTICSEARCH_ENABLED', false);

        if ($this->isEnabled) {
            $this->client = ClientBuilder::create()
                ->setHosts([env('ELASTICSEARCH_HOST', 'localhost:9200')])
                ->build();
        }
    }

    public function index($index, $id, $data)
    {
        if (! $this->isEnabled) {
            return false;
        }

        return $this->client->index([
            'index' => $index,
            'id'    => $id,
            'body'  => $data,
        ]);
    }

    public function delete($index, $id)
    {
        if (! $this->isEnabled) {
            return false;
        }

        return $this->client->delete([
            'index' => $index,
            'id'    => $id,
        ]);
    }

    public function search($index, $query)
    {
        if (! $this->isEnabled) {
            return collect();
        }

        $response = $this->client->search([
            'index' => $index,
            'body'  => $query,
        ]);

        return collect($response['hits']['hits'])->pluck('_source');
    }
}
