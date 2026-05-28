<?php

use Webkul\Installer\Database\Seeders\DemoExtrasTableSeeder;

/**
 * Regression: DatabaseSequenceHelper assumes every applied table has an
 * integer `id` column backed by a sequence. The demo dump includes two
 * shapes that break that assumption on pgsql
 */
class StubSequenceFilterSeeder extends DemoExtrasTableSeeder
{
    /** @var array<string, array<int, array{name: string, type_name?: string, type?: string}>> */
    public array $columnsByTable = [];

    protected function getTableColumns(string $table): array
    {
        return $this->columnsByTable[$table] ?? [];
    }

    public function publicHasIntegerIdSequence(string $table): bool
    {
        return $this->hasIntegerIdSequence($table);
    }
}

describe('DemoExtrasTableSeeder sequence-filter', function () {
    it('keeps tables whose id is a pgsql bigint/integer column', function () {
        $seeder = new StubSequenceFilterSeeder;
        $seeder->columnsByTable['locales'] = [
            ['name' => 'id', 'type_name' => 'int8', 'type' => 'bigint'],
            ['name' => 'code', 'type_name' => 'varchar', 'type' => 'varchar(255)'],
        ];

        expect($seeder->publicHasIntegerIdSequence('locales'))->toBeTrue();
    });

    it('keeps tables whose id is a mysql bigint(20) column', function () {
        $seeder = new StubSequenceFilterSeeder;
        $seeder->columnsByTable['attributes'] = [
            ['name' => 'id', 'type_name' => 'bigint', 'type' => 'bigint(20) unsigned'],
        ];

        expect($seeder->publicHasIntegerIdSequence('attributes'))->toBeTrue();
    });

    it('rejects join tables that have no id column at all', function () {
        $seeder = new StubSequenceFilterSeeder;
        $seeder->columnsByTable['channel_locales'] = [
            ['name' => 'channel_id', 'type_name' => 'int8', 'type' => 'bigint'],
            ['name' => 'locale_id', 'type_name' => 'int8', 'type' => 'bigint'],
        ];
        $seeder->columnsByTable['channel_currencies'] = [
            ['name' => 'channel_id', 'type_name' => 'int8', 'type' => 'bigint'],
            ['name' => 'currency_id', 'type_name' => 'int8', 'type' => 'bigint'],
        ];
        $seeder->columnsByTable['attribute_group_mappings'] = [
            ['name' => 'attribute_id', 'type_name' => 'int8', 'type' => 'bigint'],
            ['name' => 'attribute_group_id', 'type_name' => 'int8', 'type' => 'bigint'],
        ];

        expect($seeder->publicHasIntegerIdSequence('channel_locales'))->toBeFalse()
            ->and($seeder->publicHasIntegerIdSequence('channel_currencies'))->toBeFalse()
            ->and($seeder->publicHasIntegerIdSequence('attribute_group_mappings'))->toBeFalse();
    });

    it('rejects oauth_clients (id is a uuid after the AdminApi retype migration)', function () {
        $seeder = new StubSequenceFilterSeeder;
        $seeder->columnsByTable['oauth_clients'] = [
            ['name' => 'id', 'type_name' => 'uuid', 'type' => 'uuid'],
            ['name' => 'name', 'type_name' => 'varchar', 'type' => 'varchar(255)'],
        ];

        expect($seeder->publicHasIntegerIdSequence('oauth_clients'))->toBeFalse();
    });

    it('rejects any other non-integer id (varchar, char, text — defensive)', function () {
        $seeder = new StubSequenceFilterSeeder;
        $seeder->columnsByTable['some_string_pk'] = [
            ['name' => 'id', 'type_name' => 'varchar', 'type' => 'varchar(64)'],
        ];

        expect($seeder->publicHasIntegerIdSequence('some_string_pk'))->toBeFalse();
    });
});

describe('demo_extras.json payload guard', function () {
    it('demo_extras.json contains the three join tables that have no id column (channel_locales, channel_currencies, attribute_group_mappings)', function () {
        $jsonPath = __DIR__.'/../../src/Database/Data/demo_extras.json';
        $decoded = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        $joinTables = ['channel_locales', 'channel_currencies', 'attribute_group_mappings'];

        foreach ($joinTables as $table) {
            expect($decoded['tables'])->toHaveKey($table);

            $rows = $decoded['tables'][$table];
            if ($rows === []) {
                continue;
            }

            expect($rows[0])->not->toHaveKey('id');
        }
    });

    it('the keyed tables that the helper SHOULD process all carry an id column in the dump', function () {
        $jsonPath = __DIR__.'/../../src/Database/Data/demo_extras.json';
        $decoded = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);

        $idBearing = ['locales', 'currencies', 'channels', 'attributes', 'attribute_groups', 'attribute_families', 'core_config'];

        foreach ($idBearing as $table) {
            $rows = $decoded['tables'][$table] ?? [];
            expect($rows)->not->toBeEmpty();
            expect($rows[0])->toHaveKey('id');
        }
    });
});
