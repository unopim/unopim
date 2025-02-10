<?php

namespace Webkul\Core;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ElasticSearch
{
    /**
     * Map configuration array keys with ES ClientBuilder setters
     *
     * @var array
     */
    protected $configMappings = [
        'retries'  => 'setRetries',
        'caBundle' => 'setCABundle',
    ];

    /**
     * Make a new connection.
     *
     *
     * @return \Elasticsearch\Client
     */
    protected function makeConnection(?string $name = null): Client
    {
        if (! config('elasticsearch.enabled')) {
            throw new \Exception('ElasticSearch is disabled in the env file.');
        }

        $connection = $name ?: $this->getDefaultConnection();

        $config = $this->getConnectionConfig($connection);

        $clientBuilder = ClientBuilder::create();

        if ($connection == 'default') {
            /**
             * Build default connection
             */
            $clientBuilder->setHosts($config['hosts'])
                ->setBasicAuthentication($config['user'] ?: '', $config['pass'] ?: '');
        } elseif ($connection == 'api') {
            /**
             * Build API key connection
             */
            $clientBuilder->setHosts($config['hosts'])
                ->setApiKey($config['key']);
        } elseif ($connection == 'cloud') {
            /**
             * Build Elastic Cloud connection
             */
            $clientBuilder->setElasticCloudId($config['id']);

            if ($config['api_key']) {
                $clientBuilder->setApiKey($config['api_key']);
            } else {
                $clientBuilder->setBasicAuthentication($config['user'], $config['pass']);
            }
        }

        /**
         * Set additional client configuration
         */
        foreach ($this->configMappings as $key => $method) {
            $value = Arr::get(config('elasticsearch'), $key);

            if (! is_null($value)) {
                $clientBuilder->$method($value);
            }
        }

        $client = $clientBuilder->build();

        // Now, after establishing the connection, update the index settings
        $this->updateMaxResultWindow($client);

        return $client;
    }

    private function updateMaxResultWindow(Client $client)
    {
        // Setting the max_result_window to a large value
        $params = [
            'index' => '_all',
            'body'  => [
                'index' => [
                    'max_result_window' => 1000000000,
                ],
            ],
        ];

        try {
            $response = $client->indices()->putSettings($params);

            Log::error("Max result window updated successfully.\n");
        } catch (\Exception $e) {

            Log::error('Error updating max_result_window: '.$e->getMessage()."\n");
        }
    }

    /**
     * Get the default connection.
     */
    public function getDefaultConnection(): string
    {
        return config('elasticsearch.connection');
    }

    /**
     * Get the configuration for a named connection.
     *
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnectionConfig(string $name)
    {
        $connections = config('elasticsearch.connections');

        if (null === $config = Arr::get($connections, $name)) {
            throw new \InvalidArgumentException("Elasticsearch connection [$name] not configured.");
        }

        return $config;
    }

    public static function testConnection(): bool
    {
        try {
            $instance = new self;
            $client = $instance->makeConnection();
            $client->info();

            return true;
        } catch (\Exception $e) {
            Log::error('Elasticsearch connection test failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->makeConnection(), $method], $parameters);
    }
}
