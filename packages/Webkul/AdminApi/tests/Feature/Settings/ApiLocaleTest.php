<?php

use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all locales', function () {
    $locale = Locale::first();

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.locales.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'status',
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
        ->assertJsonFragment(['total' => Locale::count()])
        ->json('data');

    $this->assertTrue(
        collect($response)->contains(['code' => $locale->code, 'status' => $locale->status]),
    );
});

it('should fetch single locale by code', function () {
    $locale = Locale::inRandomOrder()->first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.locales.get', $locale->code))
        ->assertOK()
        ->assertJsonStructure([
            'code',
            'status',
        ])
        ->assertJson(['code' => $locale->code]);
});

it('should filter the locale based on status', function () {
    $locale = Locale::where('status', 1)->first();

    $filters = [
        'status' => [
            [
                'operator' => '=',
                'value'    => '1',
            ],
        ],
    ];

    $driver = DB::getDriverName();

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.locales.index', ['filters' => json_encode($filters)]))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'status',
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
        ]);

    $response
        ->assertJsonFragment(['code' => $locale->code, 'status' => (int) $locale->status])
        ->assertJsonFragment(['total' => Locale::where('status', 1)->count()]);
});

it('should return validation error when filtering based on any other field than status', function () {
    $filters = [
        'code' => [
            [
                'operator' => 'LIKE',
                'value'    => 'U',
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('GET', route('admin.api.locales.index', ['filters' => json_encode($filters)]))
        ->assertBadRequest()
        ->assertJsonStructure([
            'error',
        ]);
});

it('should return error message when code does not exists', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.locales.get', ['code' => 'en_IN2']))
        ->assertBadRequest()
        ->assertJsonStructure([
            'error',
        ]);
});
