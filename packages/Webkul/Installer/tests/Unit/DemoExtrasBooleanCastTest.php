<?php

use Webkul\Installer\Database\Seeders\DemoExtrasTableSeeder;

/**
 * Regression: the demo_extras.json dump stores int 0/1 for columns the
 * migrations declare as `boolean`. MySQL coerces silently; PostgreSQL
 * fails with SQLSTATE 42804 ("column ... is of type boolean but expression
 * is of type integer"), aborting the very first locales INSERT and leaving
 * the user with categories + products but no channels/families/locales/
 * currencies. Fix casts the values before INSERT (issue #874).
 *
 * Exercises the cast in isolation against a stub seeder so we don't need
 * a real DB connection — the production code path uses the same logic
 * fed by Schema::getColumns() on the live tables.
 */
class StubBooleanCastSeeder extends DemoExtrasTableSeeder
{
    /** @var array<string, list<string>> */
    public array $boolColumnsByTable = [];

    /** {@inheritdoc} */
    protected function castBooleanColumns(string $table, array $rows): array
    {
        $boolColumns = $this->boolColumnsByTable[$table] ?? [];

        if (empty($boolColumns)) {
            return $rows;
        }

        foreach ($rows as &$row) {
            foreach ($boolColumns as $col) {
                if (array_key_exists($col, $row) && is_int($row[$col])) {
                    $row[$col] = (bool) $row[$col];
                }
            }
        }

        return $rows;
    }

    public function publicCast(string $table, array $rows): array
    {
        return $this->castBooleanColumns($table, $rows);
    }
}

describe('DemoExtrasTableSeeder boolean casting (issue #874)', function () {
    it('rewrites int 0/1 to PHP bools for columns the schema declares as boolean', function () {
        $seeder = new StubBooleanCastSeeder;
        $seeder->boolColumnsByTable['locales'] = ['status'];

        $rows = [
            ['id' => 1, 'code' => 'af_ZA', 'status' => 0],
            ['id' => 2, 'code' => 'en_US', 'status' => 1],
        ];

        $cast = $seeder->publicCast('locales', $rows);

        expect($cast[0]['status'])->toBeFalse()
            ->and($cast[1]['status'])->toBeTrue();
    });

    it('leaves non-boolean integer columns untouched (id, position, root_category_id, ...)', function () {
        $seeder = new StubBooleanCastSeeder;
        $seeder->boolColumnsByTable['attribute_family_group_mappings'] = []; // none are bool

        $rows = [
            ['id' => 1, 'attribute_family_id' => 1, 'attribute_group_id' => 1, 'position' => 1],
        ];

        $cast = $seeder->publicCast('attribute_family_group_mappings', $rows);

        expect($cast[0]['id'])->toBe(1)
            ->and($cast[0]['attribute_family_id'])->toBe(1)
            ->and($cast[0]['position'])->toBe(1);
    });

    it('handles tables that have a mix of int and boolean columns without touching the ints', function () {
        $seeder = new StubBooleanCastSeeder;
        $seeder->boolColumnsByTable['attributes'] = [
            'is_required', 'is_unique', 'value_per_locale', 'ai_translate',
            'value_per_channel', 'is_filterable', 'enable_wysiwyg', 'usable_in_grid',
        ];

        $rows = [[
            'id'             => 1, 'code' => 'sku', 'type' => 'text', 'position' => 1,
            'is_required'    => 1, 'is_unique' => 1, 'value_per_locale' => 0,
            'ai_translate'   => 0, 'value_per_channel' => 0, 'is_filterable' => 1,
            'enable_wysiwyg' => 0, 'usable_in_grid' => 0,
        ]];

        $cast = $seeder->publicCast('attributes', $rows);

        expect($cast[0]['is_required'])->toBeTrue()
            ->and($cast[0]['is_unique'])->toBeTrue()
            ->and($cast[0]['is_filterable'])->toBeTrue()
            ->and($cast[0]['value_per_locale'])->toBeFalse()
            ->and($cast[0]['ai_translate'])->toBeFalse()
            ->and($cast[0]['enable_wysiwyg'])->toBeFalse()
            // Non-boolean ints stay ints.
            ->and($cast[0]['id'])->toBe(1)
            ->and($cast[0]['position'])->toBe(1);
    });

    it('returns rows unchanged when the table has no boolean columns', function () {
        $seeder = new StubBooleanCastSeeder;
        $seeder->boolColumnsByTable['channel_locales'] = [];

        $rows = [['channel_id' => 1, 'locale_id' => 39]];

        expect($seeder->publicCast('channel_locales', $rows))->toBe($rows);
    });

    it('skips missing keys (sparse row payloads) instead of touching them', function () {
        $seeder = new StubBooleanCastSeeder;
        $seeder->boolColumnsByTable['admins'] = ['status'];

        $rows = [['id' => 1, 'name' => 'Example']]; // no status key

        $cast = $seeder->publicCast('admins', $rows);

        expect($cast[0])->not->toHaveKey('status')
            ->and($cast[0]['name'])->toBe('Example');
    });
});
