<?php

use Illuminate\Support\Facades\URL;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Models\PassportTemplate;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Models\PublicationVersionPayload;
use Webkul\Publication\Services\Publisher;

/**
 * Each field renders once, under its own section heading, at every tier.
 */
beforeEach(function (): void {
    $this->publishTwoSectionPassport = function (): PublicationVersion {
        $family = AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();

        $template = PassportTemplate::create(['code' => 'tpl_two_sections', 'is_enabled' => true]);
        $template->families()->attach($family->id);

        $values = [];

        $sections = [
            'general'   => ['General Info', ['brand' => ['Brand', 'PERFECT Sports', 'consumer']]],
            'nutrition' => ['Supplement Facts', ['serving' => ['Serving Size', '30 g', 'consumer']]],
            'supply'    => ['Supply Chain', ['supplier' => ['Supplier', 'Whey Co-op New Zealand', 'operator']]],
        ];

        foreach (array_values($sections) as $position => [$sectionName, $fields]) {
            $section = $template->sections()->create([
                'code'     => array_keys($sections)[$position],
                'position' => $position,
                'en_US'    => ['name' => $sectionName],
            ]);

            foreach ($fields as $code => [$label, $value, $tier]) {
                $attribute = $this->passportSourceAttribute(['type' => 'textarea', 'value_per_locale' => 1]);

                $template->fields()->create([
                    'code'                         => $code,
                    'passport_template_section_id' => $section->id,
                    'source_type'                  => 'attribute',
                    'attribute_id'                 => $attribute->id,
                    'tier'                         => $tier,
                    'position'                     => $position,
                    'en_US'                        => ['label' => $label],
                ]);

                $values[$attribute->code] = $value;
            }
        }

        $channel = Channel::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $product = Product::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['locale_specific' => [$locale->code => $values]],
        ]);

        $this->enablePublicTier($channel->code);
        $this->enablePassportPublishing($channel->code);

        return resolve(Publisher::class)->publish($product, $channel, $locale, 'dpp');
    };

    $this->sectionsOf = function (string $html): array {
        preg_match_all('#<section class="card">\s*<h2>(.*?)</h2>(.*?)</section>#s', $html, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $match): array => [
            html_entity_decode($match[1]) => array_map('html_entity_decode', preg_match_all('#<dt>(.*?)</dt>#', $match[2], $labels) ? $labels[1] : []),
        ])->filter()->all();
    };
});

it('renders each consumer field once under its own section', function (): void {
    $version = ($this->publishTwoSectionPassport)();

    $html = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->assertOk()->getContent();

    expect(($this->sectionsOf)($html))->toBe([
        'General Info'     => ['Brand'],
        'Supplement Facts' => ['Serving Size'],
    ]);
});

it('renders an elevated field under its own section on a signed tier url', function (): void {
    $version = ($this->publishTwoSectionPassport)();

    $url = URL::temporarySignedRoute('publication.public.dpp.show.locale', now()->addHour(), [
        'uuid'   => $version->publication->uuid,
        'locale' => $version->locale->code,
        'tier'   => 'operator',
    ]);

    expect(($this->sectionsOf)($this->get($url)->assertOk()->getContent()))->toBe([
        'General Info'     => ['Brand'],
        'Supplement Facts' => ['Serving Size'],
        'Supply Chain'     => ['Supplier'],
    ]);
});

it('lists every visible field once in the negotiated json-ld', function (): void {
    $version = ($this->publishTwoSectionPassport)();

    $properties = $this->withHeaders(['Accept' => 'application/ld+json'])
        ->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk()
        ->json('additionalProperty.*.name');

    expect($properties)->toBe(['Brand', 'Serving Size']);
});

it('places fields correctly on a version published before every section was listed', function (): void {
    $version = ($this->publishTwoSectionPassport)();

    $payload = $version->payload;
    $payload['sections'] = array_values(array_filter($payload['sections'], fn (array $section): bool => $section['fields'] !== []));
    $stored = (new PublicationVersionPayload)->forceFill(['payload' => $payload])->getAttributes()['payload'];

    PublicationVersionPayload::query()->where('publication_version_id', $version->id)->toBase()->update(['payload' => $stored]);

    $consumer = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->assertOk()->getContent();

    expect(($this->sectionsOf)($consumer))->toBe([
        'General Info'     => ['Brand'],
        'Supplement Facts' => ['Serving Size'],
    ]);

    $url = URL::temporarySignedRoute('publication.public.dpp.show.locale', now()->addHour(), [
        'uuid'   => $version->publication->uuid,
        'locale' => $version->locale->code,
        'tier'   => 'operator',
    ]);

    expect(($this->sectionsOf)($this->get($url)->assertOk()->getContent()))->toBe([
        'General Info'     => ['Brand'],
        'Supplement Facts' => ['Serving Size'],
        'Supply'           => ['Supplier'],
    ]);
});
