<?php

use Webkul\Admin\Fields\FieldConfig;

it('normalizes a config field into what the browser consumes', function () {
    $field = app(FieldConfig::class)->field([
        'name'       => 'channels',
        'title'      => 'admin::app.settings.data-transfer.exports.create.code',
        'type'       => 'multiselect',
        'required'   => true,
        'async'      => true,
        'list_route' => 'admin.settings.data_transfer.exports.filters.channels',
        'track_by'   => 'code',
    ]);

    expect($field['name'])->toBe('channels')
        ->and($field['type'])->toBe('multiselect')
        ->and($field['required'])->toBeTrue()
        ->and($field['label'])->not->toContain('::')
        ->and($field['list_route'])->toStartWith('http');
});

it('falls back to text for a field with no type', function () {
    expect(app(FieldConfig::class)->field(['name' => 'sku'])['type'])->toBe('text');
});

it('preserves the original file path label for custom field configurations', function (string $locale, string $legacyLabel) {
    app()->setLocale($locale);
    $config = app(FieldConfig::class);

    $legacy = $config->field([
        'name'  => 'file_path',
        'title' => 'data_transfer::app.exporters.fields.file-path',
        'info'  => 'data_transfer::app.exporters.fields.file-path-info',
    ]);
    $current = $config->field([
        'name'  => 'file_path',
        'title' => 'data_transfer::app.exporters.fields.file-name',
        'info'  => 'data_transfer::app.exporters.fields.file-name-info',
    ]);

    expect($legacy['label'])->toBe($legacyLabel);
    expect($current['label'])->not->toContain('::')->not->toBe($legacyLabel);
    expect($legacy['info'])->not->toContain('::')->toBe($current['info']);
})->with([
    'en_US' => ['en_US', 'File Path'],
    'ar_AE' => ['ar_AE', 'مسار الملف'],
    'ca_ES' => ['ca_ES', 'Camí del Fitxer'],
    'da_DK' => ['da_DK', 'Filsti'],
    'de_DE' => ['de_DE', 'Dateipfad'],
    'en_AU' => ['en_AU', 'File Path'],
    'en_GB' => ['en_GB', 'File Path'],
    'en_NZ' => ['en_NZ', 'File Path'],
    'es_ES' => ['es_ES', 'Ruta de archivo'],
    'es_VE' => ['es_VE', 'Ruta del Archivo'],
    'fi_FI' => ['fi_FI', 'Tiedostopolku'],
    'fr_FR' => ['fr_FR', 'Chemin du fichier'],
    'hi_IN' => ['hi_IN', 'दस्तावेज पथ'],
    'hr_HR' => ['hr_HR', 'Putanja Datoteke'],
    'id_ID' => ['id_ID', 'Jalur file'],
    'it_IT' => ['it_IT', 'Percorso File'],
    'ja_JP' => ['ja_JP', 'ファイル パス'],
    'ko_KR' => ['ko_KR', '파일 경로'],
    'mn_MN' => ['mn_MN', 'Файлын зам'],
    'nl_NL' => ['nl_NL', 'Bestandspad'],
    'no_NO' => ['no_NO', 'Filsti'],
    'pl_PL' => ['pl_PL', 'Ścieżka pliku'],
    'pt_BR' => ['pt_BR', 'Caminho do Arquivo'],
    'pt_PT' => ['pt_PT', 'Caminho do Arquivo'],
    'ro_RO' => ['ro_RO', 'Calea Fișierului'],
    'ru_RU' => ['ru_RU', 'Путь к файлу'],
    'sv_SE' => ['sv_SE', 'Fil Sökväg'],
    'tl_PH' => ['tl_PH', 'Daan ng File'],
    'tr_TR' => ['tr_TR', 'Dosya Yolu'],
    'uk_UA' => ['uk_UA', 'Шлях до файлу'],
    'vi_VN' => ['vi_VN', 'Đường dẫn tệp'],
    'zh_CN' => ['zh_CN', '文件路径'],
    'zh_TW' => ['zh_TW', '文件路徑'],
]);

it('labels the product export file name while preserving the saved setting name', function () {
    app()->setLocale('en_US');

    $sets = app(FieldConfig::class)->payload(config('exporters'))['sets'];
    $field = collect($sets['products'])->firstWhere('name', 'file_path');

    expect($field)->not->toBeNull();
    expect($field['label'])->toBe('File Name');
    expect($field['info'])->toBe('File name pattern. Tokens: [code], [date], [time], [entity_type]');
});

it('carries depends_on through, so scoping stays config driven', function () {
    $sets = app(FieldConfig::class)->payload(config('exporters'))['sets'];

    $byName = collect($sets['products'])->keyBy('name');

    expect($byName['locales']['depends_on'])->toBe(['field' => 'channels', 'as' => 'channels'])
        ->and($byName['channels']['depends_on'])->toBeNull();
});

it('declares attribute conditions as an ordinary config field, route and exclusions resolved', function () {
    $payload = app(FieldConfig::class)->payload(config('exporters'));

    $field = collect($payload['sets']['products'])->firstWhere('name', 'custom_attributes');

    expect($field)->not->toBeNull()
        ->and($field['type'])->toBe('attribute-conditions')
        ->and($field['list_route'])->toStartWith('http')
        ->and($field['list_route'])->toContain('filters/attributes')
        ->and($field['query_params'])->toBe(['exclude' => ['sku']])
        ->and($payload['types'])->toContain('attribute-conditions');
});

it('keeps attribute conditions off the entities whose config does not ask for them', function () {
    $sets = app(FieldConfig::class)->payload(config('exporters'))['sets'];

    expect(array_column($sets['categories'], 'name'))->not->toContain('custom_attributes');
});

it('memoizes the payload, so five cards do not rebuild it five times', function () {
    $config = app(FieldConfig::class);

    expect($config->payload(config('exporters')))->toBe($config->payload(config('exporters')));
});

it('keys the memo by content, so a different config cannot hit a stale entry', function () {
    $config = app(FieldConfig::class);

    $first = $config->payload(['products' => ['filters' => ['fields' => [['name' => 'sku']]]]]);
    $second = $config->payload(['products' => ['filters' => ['fields' => [['name' => 'status']]]]]);

    expect($first['sets']['products'][0]['name'])->toBe('sku')
        ->and($second['sets']['products'][0]['name'])->toBe('status');
});

it('takes the config as an array or as a json string', function () {
    $config = app(FieldConfig::class);
    $exporters = config('exporters');

    expect($config->payload(json_encode($exporters)))->toBe($config->payload($exporters))
        ->and($config->payload('')['sets'])->toBe([]);
});

it('reports the types a config needs, so only those widgets get loaded', function () {
    $types = app(FieldConfig::class)->payload(config('exporters'))['types'];

    expect($types)->toContain('multiselect')
        ->and($types)->toContain('boolean');
});

it('is a singleton, or the memo would be thrown away each time', function () {
    expect(app(FieldConfig::class))->toBe(app(FieldConfig::class));
});
