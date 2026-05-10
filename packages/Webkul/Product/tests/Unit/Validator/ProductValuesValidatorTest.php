<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Type\AbstractType;
use Webkul\Product\Validator\ProductValuesValidator;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->validator = app(ProductValuesValidator::class);
    $this->defaultChannel = core()->getDefaultChannel();
    $this->defaultLocale = $this->defaultChannel->locales->first();
});

describe('sections whitelist', function () {
    it('throws when an unknown section key is present', function () {
        $this->validator->validate([
            'garbage_section' => ['foo' => 'bar'],
        ]);
    })->throws(ValidationException::class);

    it('throws when a typo is made on a known section key', function () {
        $this->validator->validate([
            'common_values' => ['sku' => 'TEST-SKU'],
        ]);
    })->throws(ValidationException::class);

    it('does not throw for an empty payload', function () {
        $this->validator->validate([]);
        expect(true)->toBeTrue();
    });
});

describe('channel_specific section', function () {
    it('throws when the channel code is unknown', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => true,
            'value_per_locale'  => false,
            'type'              => 'text',
        ]);

        $this->validator->validate([
            AbstractType::CHANNEL_VALUES_KEY => [
                'no_such_channel' => [
                    $attribute->code => 'value',
                ],
            ],
        ]);
    })->throws(ValidationException::class);

    it('passes with a known channel and a valid channel-scoped attribute', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => true,
            'value_per_locale'  => false,
            'type'              => 'text',
        ]);

        $this->validator->validate([
            AbstractType::CHANNEL_VALUES_KEY => [
                $this->defaultChannel->code => [
                    $attribute->code => 'channel value',
                ],
            ],
        ]);

        expect(true)->toBeTrue();
    });
});

describe('channel_locale_specific section', function () {
    it('throws when the channel is unknown', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => true,
            'value_per_locale'  => true,
            'type'              => 'text',
        ]);

        $this->validator->validate([
            AbstractType::CHANNEL_LOCALE_VALUES_KEY => [
                'no_such_channel' => [
                    $this->defaultLocale->code => [
                        $attribute->code => 'value',
                    ],
                ],
            ],
        ]);
    })->throws(ValidationException::class);

    it('throws when the locale is not assigned to the channel', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => true,
            'value_per_locale'  => true,
            'type'              => 'text',
        ]);

        $assignedLocaleCodes = $this->defaultChannel->locales->pluck('code')->toArray();

        $unassignedLocale = Locale::where('status', 1)
            ->whereNotIn('code', $assignedLocaleCodes)
            ->first();

        if (! $unassignedLocale) {
            $unassignedLocale = Locale::factory()->create(['status' => 1]);
        }

        $this->validator->validate([
            AbstractType::CHANNEL_LOCALE_VALUES_KEY => [
                $this->defaultChannel->code => [
                    $unassignedLocale->code => [
                        $attribute->code => 'value',
                    ],
                ],
            ],
        ]);
    })->throws(ValidationException::class);

    it('passes with a valid channel, assigned locale, and matching attribute', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => true,
            'value_per_locale'  => true,
            'type'              => 'text',
        ]);

        $this->validator->validate([
            AbstractType::CHANNEL_LOCALE_VALUES_KEY => [
                $this->defaultChannel->code => [
                    $this->defaultLocale->code => [
                        $attribute->code => 'value',
                    ],
                ],
            ],
        ]);

        expect(true)->toBeTrue();
    });
});

describe('locale_specific section', function () {
    it('throws when the locale is not assigned to any channel', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => false,
            'value_per_locale'  => true,
            'type'              => 'text',
        ]);

        $assignedLocaleCodes = Channel::with('locales')->get()
            ->pluck('locales')
            ->flatten()
            ->pluck('code')
            ->unique()
            ->toArray();

        $unassignedLocale = Locale::where('status', 1)
            ->whereNotIn('code', $assignedLocaleCodes)
            ->first();

        if (! $unassignedLocale) {
            $unassignedLocale = Locale::factory()->create(['status' => 1]);
        }

        $this->validator->validate([
            AbstractType::LOCALE_VALUES_KEY => [
                $unassignedLocale->code => [
                    $attribute->code => 'value',
                ],
            ],
        ]);
    })->throws(ValidationException::class);
});

describe('validateOnlyExistingSectionData', function () {
    it('does not fail when only the common section is present', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => false,
            'value_per_locale'  => false,
            'type'              => 'text',
        ]);

        $this->validator->validateOnlyExistingSectionData([
            AbstractType::COMMON_VALUES_KEY => [
                $attribute->code => 'common value',
            ],
        ]);

        expect(true)->toBeTrue();
    });

    it('does not validate channel sections when no channel-based sections are present', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => false,
            'value_per_locale'  => false,
            'type'              => 'text',
        ]);

        $this->validator->validateOnlyExistingSectionData([
            AbstractType::COMMON_VALUES_KEY => [
                $attribute->code => 'value',
            ],
            AbstractType::CATEGORY_VALUES_KEY => [],
        ]);

        expect(true)->toBeTrue();
    });

    it('still rejects unknown section keys in partial validation', function () {
        $this->validator->validateOnlyExistingSectionData([
            'garbage_section' => ['foo' => 'bar'],
        ]);
    })->throws(ValidationException::class);

    it('throws when the channel section has an unknown channel', function () {
        $attribute = Attribute::factory()->create([
            'value_per_channel' => true,
            'value_per_locale'  => false,
            'type'              => 'text',
        ]);

        $this->validator->validateOnlyExistingSectionData([
            AbstractType::CHANNEL_VALUES_KEY => [
                'no_such_channel' => [
                    $attribute->code => 'value',
                ],
            ],
        ]);
    })->throws(ValidationException::class);
});
