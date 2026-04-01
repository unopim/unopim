<?php

use Webkul\Product\Services\ProductValueMapper;

beforeEach(function () {
    $this->mapper = new ProductValueMapper;
});

describe('getCommonFields', function () {
    it('returns common fields when present', function () {
        $data = [
            'values' => [
                'common' => [
                    'sku'  => 'TEST-001',
                    'name' => 'Test Product',
                ],
            ],
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toBe([
            'sku'  => 'TEST-001',
            'name' => 'Test Product',
        ]);
    });

    it('returns empty array when values key is missing', function () {
        $result = $this->mapper->getCommonFields([]);

        expect($result)->toBe([]);
    });

    it('returns empty array when common key is missing', function () {
        $data = [
            'values' => [
                'locale_specific' => ['en_US' => ['name' => 'Test']],
            ],
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toBe([]);
    });

    it('returns empty array when common is an empty array', function () {
        $data = [
            'values' => [
                'common' => [],
            ],
        ];

        $result = $this->mapper->getCommonFields($data);

        expect($result)->toBe([]);
    });
});

describe('getLocaleSpecificFields', function () {
    it('returns locale-specific fields for a given locale', function () {
        $data = [
            'values' => [
                'locale_specific' => [
                    'en_US' => ['name' => 'English Name'],
                    'fr_FR' => ['name' => 'Nom Français'],
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($result)->toBe(['name' => 'English Name']);
    });

    it('returns empty array when locale does not exist', function () {
        $data = [
            'values' => [
                'locale_specific' => [
                    'en_US' => ['name' => 'English Name'],
                ],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'de_DE');

        expect($result)->toBe([]);
    });

    it('returns empty array when values key is missing', function () {
        $result = $this->mapper->getLocaleSpecificFields([], 'en_US');

        expect($result)->toBe([]);
    });

    it('returns empty array when locale_specific key is missing', function () {
        $data = [
            'values' => [
                'common' => ['sku' => 'TEST'],
            ],
        ];

        $result = $this->mapper->getLocaleSpecificFields($data, 'en_US');

        expect($result)->toBe([]);
    });
});

describe('getChannelSpecificFields', function () {
    it('returns channel-specific fields for a given channel', function () {
        $data = [
            'values' => [
                'channel_specific' => [
                    'default' => ['price' => 99.99],
                    'retail'  => ['price' => 109.99],
                ],
            ],
        ];

        $result = $this->mapper->getChannelSpecificFields($data, 'default');

        expect($result)->toBe(['price' => 99.99]);
    });

    it('returns empty array when channel does not exist', function () {
        $data = [
            'values' => [
                'channel_specific' => [
                    'default' => ['price' => 99.99],
                ],
            ],
        ];

        $result = $this->mapper->getChannelSpecificFields($data, 'wholesale');

        expect($result)->toBe([]);
    });

    it('returns empty array when values key is missing', function () {
        $result = $this->mapper->getChannelSpecificFields([], 'default');

        expect($result)->toBe([]);
    });

    it('returns empty array when channel_specific key is missing', function () {
        $data = [
            'values' => [
                'common' => ['sku' => 'TEST'],
            ],
        ];

        $result = $this->mapper->getChannelSpecificFields($data, 'default');

        expect($result)->toBe([]);
    });
});

describe('getChannelLocaleSpecificFields', function () {
    it('returns channel-locale-specific fields for given channel and locale', function () {
        $data = [
            'values' => [
                'channel_locale_specific' => [
                    'default' => [
                        'en_US' => ['description' => 'English description for default channel'],
                        'fr_FR' => ['description' => 'Description française'],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->getChannelLocaleSpecificFields($data, 'default', 'en_US');

        expect($result)->toBe(['description' => 'English description for default channel']);
    });

    it('returns empty array when channel does not exist', function () {
        $data = [
            'values' => [
                'channel_locale_specific' => [
                    'default' => [
                        'en_US' => ['description' => 'Test'],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->getChannelLocaleSpecificFields($data, 'retail', 'en_US');

        expect($result)->toBe([]);
    });

    it('returns empty array when locale does not exist in channel', function () {
        $data = [
            'values' => [
                'channel_locale_specific' => [
                    'default' => [
                        'en_US' => ['description' => 'Test'],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->getChannelLocaleSpecificFields($data, 'default', 'ja_JP');

        expect($result)->toBe([]);
    });

    it('returns empty array when values key is missing', function () {
        $result = $this->mapper->getChannelLocaleSpecificFields([], 'default', 'en_US');

        expect($result)->toBe([]);
    });

    it('returns empty array when channel_locale_specific key is missing', function () {
        $data = [
            'values' => [
                'common' => ['sku' => 'TEST'],
            ],
        ];

        $result = $this->mapper->getChannelLocaleSpecificFields($data, 'default', 'en_US');

        expect($result)->toBe([]);
    });
});

describe('getCategories', function () {
    it('returns comma-separated categories when present', function () {
        $data = [
            'values' => [
                'categories' => ['electronics', 'gadgets', 'phones'],
            ],
        ];

        $result = $this->mapper->getCategories($data);

        expect($result)->toBe('electronics,gadgets,phones');
    });

    it('returns single category without comma', function () {
        $data = [
            'values' => [
                'categories' => ['electronics'],
            ],
        ];

        $result = $this->mapper->getCategories($data);

        expect($result)->toBe('electronics');
    });

    it('returns null when values key is missing', function () {
        $result = $this->mapper->getCategories([]);

        expect($result)->toBeNull();
    });

    it('returns null when categories key is missing', function () {
        $data = [
            'values' => [
                'common' => ['sku' => 'TEST'],
            ],
        ];

        $result = $this->mapper->getCategories($data);

        expect($result)->toBeNull();
    });

    it('returns empty string for empty categories array', function () {
        $data = [
            'values' => [
                'categories' => [],
            ],
        ];

        $result = $this->mapper->getCategories($data);

        expect($result)->toBe('');
    });

    it('returns null when categories is not an array', function () {
        $data = [
            'values' => [
                'categories' => 'not-an-array',
            ],
        ];

        $result = $this->mapper->getCategories($data);

        expect($result)->toBeNull();
    });
});

describe('getAssociations', function () {
    it('returns comma-separated associations for a given type', function () {
        $data = [
            'values' => [
                'associations' => [
                    'related'    => ['SKU-001', 'SKU-002', 'SKU-003'],
                    'cross_sell' => ['SKU-004'],
                ],
            ],
        ];

        $result = $this->mapper->getAssociations($data, 'related');

        expect($result)->toBe('SKU-001,SKU-002,SKU-003');
    });

    it('returns single association without comma', function () {
        $data = [
            'values' => [
                'associations' => [
                    'up_sell' => ['SKU-010'],
                ],
            ],
        ];

        $result = $this->mapper->getAssociations($data, 'up_sell');

        expect($result)->toBe('SKU-010');
    });

    it('returns null when values key is missing', function () {
        $result = $this->mapper->getAssociations([], 'related');

        expect($result)->toBeNull();
    });

    it('returns null when associations key is missing', function () {
        $data = [
            'values' => [
                'common' => ['sku' => 'TEST'],
            ],
        ];

        $result = $this->mapper->getAssociations($data, 'related');

        expect($result)->toBeNull();
    });

    it('returns null when requested type does not exist', function () {
        $data = [
            'values' => [
                'associations' => [
                    'related' => ['SKU-001'],
                ],
            ],
        ];

        $result = $this->mapper->getAssociations($data, 'cross_sell');

        expect($result)->toBeNull();
    });

    it('returns null when associations is not an array', function () {
        $data = [
            'values' => [
                'associations' => 'not-an-array',
            ],
        ];

        $result = $this->mapper->getAssociations($data, 'related');

        expect($result)->toBeNull();
    });

    it('returns empty string for empty associations array of given type', function () {
        $data = [
            'values' => [
                'associations' => [
                    'related' => [],
                ],
            ],
        ];

        $result = $this->mapper->getAssociations($data, 'related');

        expect($result)->toBe('');
    });
});
