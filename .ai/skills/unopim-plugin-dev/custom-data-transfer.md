# Custom Data Transfer — UnoPim Plugins

---

## Custom Importer

### 1. Create Importer Class

```php
<?php

namespace Webkul\Example\Helpers\Importers\Example;

use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;

class Importer extends AbstractImporter
{
    /**
     * Validate a single row.
     */
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        if (empty($rowData['code'])) {
            $this->skipRow($rowNumber, 'code_required', 'code');
            return false;
        }

        return true;
    }

    /**
     * Import a batch of rows.
     */
    public function importBatch(array $batch): bool
    {
        foreach ($batch as $rowData) {
            // Process each row
            $this->createOrUpdate($rowData);
        }

        return true;
    }
}
```

### 2. Register in Config (`Config/importer.php`)

```php
<?php

return [
    'example' => [
        'title'    => 'example::app.importers.example.title',
        'importer' => \Webkul\Example\Helpers\Importers\Example\Importer::class,
        'sample'   => 'example::data/sample.csv',
    ],
];
```

### 3. Merge in ServiceProvider

```php
$this->mergeConfigFrom(dirname(__DIR__) . '/Config/importer.php', 'importers');
```

---

## Custom Exporter

### 1. Create Exporter Class

```php
<?php

namespace Webkul\Example\Helpers\Exporters\Example;

use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;

class Exporter extends AbstractExporter
{
    /**
     * Export a batch of records.
     */
    public function exportBatch(array $batch): void
    {
        foreach ($batch as $record) {
            $this->writer->writeRow([
                'code'   => $record['code'],
                'name'   => $record['name'],
                'status' => $record['status'],
            ]);
        }
    }

    /**
     * Get export results summary.
     */
    public function getResults(): array
    {
        return [
            'exported' => $this->exportedCount,
        ];
    }
}
```

### 2. Register in Config (`Config/exporter.php`)

```php
<?php

return [
    'example' => [
        'title'    => 'example::app.exporters.example.title',
        'exporter' => \Webkul\Example\Helpers\Exporters\Example\Exporter::class,
    ],
];
```

### 3. Merge in ServiceProvider

```php
$this->mergeConfigFrom(dirname(__DIR__) . '/Config/exporter.php', 'exporters');
```

---

## Custom Job Validators

Validate import/export job configuration:

```php
namespace Webkul\Example\Validators\JobInstances\Import;

use Webkul\DataTransfer\Validators\JobValidator;

class ExampleValidator extends JobValidator
{
    public function getRules(): array
    {
        return [
            'file_path' => 'required|string',
            'mapping'   => 'required|array',
        ];
    }
}
```

---

## Import/Export Filter Types

Available filter types for job forms:

| Type | Description |
|---|---|
| `file` | File upload selector |
| `boolean` | Toggle switch |
| `select` | Dropdown (with async option) |
| `multiselect` | Multi-select dropdown |
| `date` | Date picker |
| `datetime` | Date-time picker |
| `textarea` | Text area |
