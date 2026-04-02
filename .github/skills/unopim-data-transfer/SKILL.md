---
name: unopim-data-transfer
description: "Import/export pipeline for UnoPim. Activates when configuring imports, exports, debugging job pipelines, or creating data transfer profiles; or when the user mentions import, export, CSV, Excel, job, queue, batch, or data transfer."
license: MIT
metadata:
  author: unopim
---

# UnoPim Data Transfer

The import/export pipeline uses a queued job system with state tracking.

## When to Use This Skill

Invoke this skill when:

- Configuring or running imports/exports
- Debugging failed import/export jobs
- Creating custom importer/exporter classes
- Understanding the job pipeline

## Job Pipeline

### Import States

```
PENDING → VALIDATED → PROCESSING → PROCESSED → LINKING → LINKED → INDEXING → INDEXED → COMPLETED
                                                                                          ↓
                                                                                        FAILED
```

### Export States

```
PENDING → PROCESSING → COMPLETED
                         ↓
                       FAILED
```

## Import Architecture

| Class | Purpose |
|---|---|
| `Helpers/Import.php` | Import orchestrator — validates, batches, processes |
| `Helpers/Importers/AbstractImporter.php` | Base importer with batch processing |
| `Helpers/Importers/Product/Importer.php` | Product-specific import logic |
| `Helpers/Importers/Category/Importer.php` | Category-specific import logic |
| `Helpers/Sources/CSV.php` | CSV file reader |
| `Helpers/Sources/Excel.php` | Excel file reader |

## Export Architecture

| Class | Purpose |
|---|---|
| `Helpers/Export.php` | Export orchestrator |
| `Helpers/Exporters/AbstractExporter.php` | Base exporter |
| `Helpers/Exporters/Product/Exporter.php` | Product export |
| `Helpers/Exporters/Category/Exporter.php` | Category export |

## Queued Jobs

| Stage | Job Class |
|---|---|
| Import track | `ImportTrackBatch` |
| Import batch | `ImportBatch` |
| Link batch | `LinkBatch` |
| Linking | `Linking` |
| Index batch | `IndexBatch` |
| Indexing | `Indexing` |
| Completed | `Completed` |
| Export track | `ExportTrackBatch` |
| Export batch | `ExportBatch` |
| Upload | `UploadFile` |

## CLI Commands

```bash
# Start queue worker (required for import/export)
php artisan queue:work --queue="default,system"

# Run a specific job
php artisan unopim:queue:work {jobId} {userEmail}

# Restart workers after code changes
php artisan queue:restart
```

## Models

| Model | Purpose |
|---|---|
| `JobInstances` | Job definition (type, entity, file path, settings) |
| `JobTrack` | Single job run (state, stats, errors) |
| `JobTrackBatch` | Individual batch within a job |

## Troubleshooting

### Import stuck in PENDING

Queue worker not running:

```bash
php artisan queue:work --queue="default,system"
```

### Import fails with validation errors

Check `JobTrack.errors` for detailed row-level errors.

### Export produces empty file

Verify data exists and filters match records.

## Creating Custom Importers/Exporters

See the `unopim-plugin-dev` skill → [custom-data-transfer.md](../unopim-plugin-dev/custom-data-transfer.md).
