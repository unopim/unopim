<?php

use Webkul\Core\Models\Currency;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all currencies', function () {
    $currency = Currency::first();

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.currencies.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'status',
                    'label',
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
        ->assertJsonFragment(['total' => Currency::count()])
        ->json('data');

    $this->assertTrue(
        collect($response)->contains(['code' => $currency->code, 'status' => $currency->status, 'label' => core()->getCurrencyLabel($currency->code, core()->getCurrentLocale()->code)]),
    );
});

it('should fetch single currency by code', function () {
    $currency = Currency::inRandomOrder()->first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.currencies.get', $currency->code))
        ->assertOK()
        ->assertJsonStructure([
            'code',
            'status',
            'label',
        ])
        ->assertJson(['code' => $currency->code, 'status' => (int) $currency->status, 'label' => core()->getCurrencyLabel($currency->code, core()->getCurrentLocale()->code)]);
});

it('should filter the currencies based on status', function () {
    $currency = Currency::where('status', 1)->first();

    $filters = [
        'status' => [
            [
                'operator' => '=',
                'value'    => '1',
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('GET', route('admin.api.currencies.index', ['filters' => json_encode($filters)]))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'status',
                    'label',
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
        ->assertJsonFragment(['code' => $currency->code, 'status' => (int) $currency->status, 'label' => core()->getCurrencyLabel($currency->code, core()->getCurrentLocale()->code)])
        ->assertJsonFragment(['total' => Currency::where('status', true)->count()]);
});

it('should return validation error when filtering based on any other field than status', function () {
    $filters = [
        'label' => [
            [
                'operator' => 'LIKE',
                'value'    => 'U',
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('GET', route('admin.api.currencies.index', ['filters' => json_encode($filters)]))
        ->assertBadRequest()
        ->assertJsonStructure([
            'error',
        ]);
});

it('should return error message when code does not exists', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.currencies.get', ['code' => 'US2']))
        ->assertBadRequest()
        ->assertJsonStructure([
            'error',
        ]);
});
