<?php

use Webkul\Core\Models\Channel;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all channels', function () {
    $channel = Channel::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.channels.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'labels',
                    'root_category',
                    'locales',
                    'currencies',
                ],
            ],
            'current_page',
            'last_page',
            'total',
            'links' => [
                'first',
                'last',
                'next',
                'prev',
            ],
        ])
        ->assertJsonFragment(['code'  => $channel->code])
        ->assertJsonFragment(['total' => Channel::count()]);
});

it('should return the channel using the code', function () {
    $channel = Channel::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.channels.get', ['code' => $channel->code]))
        ->assertOK()
        ->assertJsonStructure([
            'code',
            'labels',
            'root_category',
            'locales',
            'currencies',
        ])
        ->assertJsonFragment(['code' => $channel->code]);
});

it('should return the message when code does not exists', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.channels.get', ['code' => 'abcxyz']))
        ->assertBadRequest()
        ->assertJsonStructure([
            'error',
        ]);
});

it('should return validation error for unsupported channel filter field', function () {
    $filters = [
        'code' => [
            [
                'operator' => 'LIKE',
                'value'    => 'default',
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('GET', route('admin.api.channels.index', ['filters' => json_encode($filters)]))
        ->assertBadRequest()
        ->assertJsonStructure([
            'error',
        ]);
});

it('should return all channels with pagination parameters', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.channels.index', ['limit' => 100, 'page' => 1]))
        ->assertOK()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'links',
        ])
        ->assertJsonFragment(['current_page' => 1]);
});
