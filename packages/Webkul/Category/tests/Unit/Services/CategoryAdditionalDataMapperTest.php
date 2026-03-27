<?php

use Webkul\Category\Services\CategoryAdditionalDataMapper;

beforeEach(function () {
    $this->mapper = new CategoryAdditionalDataMapper;
});

describe('getCommonFields', function () {
    it('returns common fields when they exist', function () {
        $data = [
            'additional_data' => [
                'common' => [
                    'description' => 'A test description',
                    'status'      => 'active',
                ],
            ],
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toBe([
            'description' => 'A test description',
            'status'      => 'active',
        ]);
    });

    it('returns empty array when common key is missing', function () {
        $data = [
            'additional_data' => [
                'locale_specific' => [
                    'en_US' => ['name' => 'Test'],
                ],
            ],
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toBe([]);
    });

    it('returns empty array when additional_data is empty array', function () {
        $data = [
            'additional_data' => [],
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toBe([]);
    });

    it('returns empty array when additional_data is not an array', function () {
        $data = [
            'additional_data' => 'not_an_array',
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toBe([]);
    });

    it('returns common fields with nested data', function () {
        $data = [
            'additional_data' => [
                'common' => [
                    'tags'     => 'electronics,gadgets',
                    'priority' => 1,
                    'visible'  => true,
                ],
            ],
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toHaveCount(3);
        expect($result['tags'])->toBe('electronics,gadgets');
        expect($result['priority'])->toBe(1);
        expect($result['visible'])->toBeTrue();
    });
});

describe('getLocaleSpecificFields', function () {
    it('returns locale-specific fields for a valid locale', function () {
        $data = [
            'additional_data' => [
                'locale_specific' => [
                    'en_US' => [
                        'name'        => 'Test Category',
                        'description' => 'English description',
                    ],
                    'fr_FR' => [
                        'name'        => 'Categorie Test',
                        'description' => 'Description en francais',
                    ],
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($result)->toBe([
            'name'        => 'Test Category',
            'description' => 'English description',
        ]);
    });

    it('returns correct fields for a different locale', function () {
        $data = [
            'additional_data' => [
                'locale_specific' => [
                    'en_US' => [
                        'name' => 'English Name',
                    ],
                    'fr_FR' => [
                        'name' => 'Nom Francais',
                    ],
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'fr_FR');

        expect($result)->toBe(['name' => 'Nom Francais']);
    });

    it('returns empty array for a non-existent locale', function () {
        $data = [
            'additional_data' => [
                'locale_specific' => [
                    'en_US' => [
                        'name' => 'Test',
                    ],
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'ja_JP');

        expect($result)->toBe([]);
    });

    it('returns empty array when locale_specific key is missing', function () {
        $data = [
            'additional_data' => [
                'common' => [
                    'status' => 'active',
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($result)->toBe([]);
    });

    it('returns empty array when additional_data is empty', function () {
        $data = [
            'additional_data' => [],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($result)->toBe([]);
    });

    it('returns empty array when additional_data is not an array', function () {
        $data = [
            'additional_data' => 'invalid_data',
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($result)->toBe([]);
    });

    it('handles multiple locales and returns only the requested one', function () {
        $data = [
            'additional_data' => [
                'locale_specific' => [
                    'en_US' => ['name' => 'English'],
                    'fr_FR' => ['name' => 'French'],
                    'de_DE' => ['name' => 'German'],
                    'ja_JP' => ['name' => 'Japanese'],
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'de_DE');

        expect($result)->toBe(['name' => 'German']);
    });

    it('validates nested data structure is preserved', function () {
        $data = [
            'additional_data' => [
                'locale_specific' => [
                    'en_US' => [
                        'name'             => 'Category',
                        'description'      => 'Full description here',
                        'meta_title'       => 'SEO Title',
                        'meta_description' => 'SEO Description',
                    ],
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($result)->toHaveCount(4);
        expect($result)->toHaveKeys(['name', 'description', 'meta_title', 'meta_description']);
    });
});

describe('combined data access', function () {
    it('extracts both common and locale-specific fields from the same data', function () {
        $data = [
            'additional_data' => [
                'common' => [
                    'status'   => 'active',
                    'position' => 5,
                ],
                'locale_specific' => [
                    'en_US' => [
                        'name'        => 'Electronics',
                        'description' => 'Electronic products',
                    ],
                ],
            ],
        ];

        $common = $this->mapper->getCommonFields($data);
        $locale = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($common)->toHaveCount(2);
        expect($common['status'])->toBe('active');

        expect($locale)->toHaveCount(2);
        expect($locale['name'])->toBe('Electronics');
    });

    it('returns empty arrays when additional_data has no common or locale keys', function () {
        $data = [
            'additional_data' => [
                'some_other_key' => 'some_value',
            ],
        ];

        $common = $this->mapper->getCommonFields($data);
        $locale = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($common)->toBe([]);
        expect($locale)->toBe([]);
    });
});
