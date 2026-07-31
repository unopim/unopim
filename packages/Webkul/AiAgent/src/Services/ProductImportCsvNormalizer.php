<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Support\Str;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;

/**
 * Normalizes AI-uploaded product rows into a CSV the core DataTransfer
 * product importer can consume, so the AI import path reuses the core
 * batched/queued/resumable pipeline instead of a bespoke in-memory job.
 *
 * The core importer hard-fails the whole import on any unknown column and
 * on missing permanent columns, and expects specific cell formats (option
 * codes for selects, `true`/`false` for booleans, `code (CURRENCY)` price
 * columns, literal `true` to enable status). This class emits exactly that
 * contract and only ever writes known columns.
 */
class ProductImportCsvNormalizer
{
    /**
     * Field separator for the generated file.
     *
     * Semicolon (matching the shipped core sample) so comma-separated
     * multi-value cells — categories, multiselect, associations — never
     * collide with the delimiter.
     */
    public const DELIMITER = ';';

    /**
     * Structural columns emitted on every row. `attribute_family` carries the
     * family CODE; the core importer requires sku/locale/channel/type/parent/
     * attribute_family to all be present.
     */
    private const array STRUCTURAL_COLUMNS = ['sku', 'type', 'attribute_family', 'parent', 'channel', 'locale', 'status'];

    public function __construct(
        protected ProductWriterService $writerService,
    ) {}

    /**
     * Build the core-compatible CSV content for the given rows.
     *
     * @param  array<int, array<string, mixed>>  $rows  Lowercase-keyed rows, already SKU- and ACL-filtered
     * @param  array<string, array{type: string, value_per_locale: bool, value_per_channel: bool, attribute_id: int}>  $familyAttrs
     * @param  array<int, string>  $currencies  Active currency codes
     */
    public function toCsv(
        array $rows,
        array $familyAttrs,
        array $currencies,
        string $familyCode,
        string $channel,
        string $locale,
    ): string {
        $header = $this->buildHeader($rows, $familyAttrs, $currencies);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $header, self::DELIMITER, '"', '\\');

        foreach ($rows as $row) {
            $normalized = $this->normalizeKeys($row);

            $line = [];
            foreach ($header as $column) {
                // Escape spreadsheet formula operators the same way the core
                // exporters do; the core importer reverses it via
                // EscapeFormulaOperators::unescapeValue(). This keeps the
                // stored, downloadable CSV safe to open in a spreadsheet.
                $line[] = (string) EscapeFormulaOperators::escapeValue(
                    $this->cellValue($column, $normalized, $familyAttrs, $familyCode, $channel, $locale)
                );
            }

            fputcsv($handle, $line, self::DELIMITER, '"', '\\');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Compute the ordered header: structural columns, then attribute columns
     * present across the rows (price attributes expanded per currency), then
     * an auto url_key when the family defines one, then categories.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, array{type: string, value_per_locale: bool, value_per_channel: bool, attribute_id: int}>  $familyAttrs
     * @param  array<int, string>  $currencies
     * @return array<int, string>
     */
    protected function buildHeader(array $rows, array $familyAttrs, array $currencies): array
    {
        $reserved = array_merge(self::STRUCTURAL_COLUMNS, ['categories']);

        $attributeColumns = [];
        $seen = [];
        $hasCategories = false;

        foreach ($rows as $row) {
            foreach (array_keys($this->normalizeKeys($row)) as $column) {
                if ($column === 'categories') {
                    $hasCategories = true;

                    continue;
                }
                if (\in_array($column, $reserved, true)) {
                    continue;
                }
                if (isset($seen[$column])) {
                    continue;
                }
                if (! isset($familyAttrs[$column])) {
                    continue;
                }

                $seen[$column] = true;

                if ($familyAttrs[$column]['type'] === 'price') {
                    foreach ($currencies as $currency) {
                        $attributeColumns[] = "{$column} ({$currency})";
                    }
                } else {
                    $attributeColumns[] = $column;
                }
            }
        }

        // Ensure a url_key column exists when the family defines it; it is
        // auto-generated per row so imports do not fail on a required url_key.
        if (isset($familyAttrs['url_key']) && ! isset($seen['url_key'])) {
            $attributeColumns[] = 'url_key';
        }

        $header = self::STRUCTURAL_COLUMNS;

        foreach ($attributeColumns as $column) {
            $header[] = $column;
        }

        if ($hasCategories) {
            $header[] = 'categories';
        }

        return $header;
    }

    /**
     * Resolve the cell value for a header column on a single row.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, array{type: string, value_per_locale: bool, value_per_channel: bool, attribute_id: int}>  $familyAttrs
     */
    protected function cellValue(
        string $column,
        array $row,
        array $familyAttrs,
        string $familyCode,
        string $channel,
        string $locale,
    ): string {
        switch ($column) {
            case 'sku':
                return (string) ($row['sku'] ?? '');
            case 'type':
                return ((string) ($row['type'] ?? '')) ?: 'simple';
            case 'attribute_family':
                return $familyCode;
            case 'parent':
                return (string) ($row['parent'] ?? '');
            case 'channel':
                return $channel;
            case 'locale':
                return $locale;
            case 'status':
                return $this->formatStatus($row['status'] ?? null);
            case 'categories':
                return (string) ($row['categories'] ?? '');
            case 'url_key':
                return $this->buildUrlKey($row);
        }

        // Price column: "code (CURRENCY)" — read the single uploaded value.
        if (preg_match('/^(.+) \(([^)]+)\)$/', $column, $matches)) {
            $raw = $row[$matches[1]] ?? null;

            return is_numeric($raw) ? (string) round((float) $raw, 2) : '';
        }

        $raw = $row[$column] ?? null;

        if ($raw === null || $raw === '') {
            return '';
        }

        $meta = $familyAttrs[$column] ?? null;

        return $meta === null ? (string) $raw : $this->formatAttributeValue($column, $raw, $meta);
    }

    /**
     * Normalize a value for a typed attribute into the core CSV contract.
     *
     * @param  array{type: string, value_per_locale: bool, value_per_channel: bool, attribute_id: int}  $meta
     */
    protected function formatAttributeValue(string $code, mixed $raw, array $meta): string
    {
        if ($meta['type'] === 'boolean') {
            return \in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on', 'enabled'], true) ? 'true' : 'false';
        }

        if (\in_array($meta['type'], ['select', 'multiselect', 'checkbox'], true) && is_string($raw)) {
            if ($meta['type'] === 'select') {
                return $this->writerService->resolveSelectValuePublic($code, $raw, $meta['attribute_id']) ?? '';
            }

            $codes = [];
            foreach (explode(',', $raw) as $part) {
                $resolved = $this->writerService->resolveSelectValuePublic($code, trim($part), $meta['attribute_id']);

                if ($resolved !== null) {
                    $codes[] = $resolved;
                }
            }

            return implode(',', $codes);
        }

        return (string) $raw;
    }

    /**
     * Core enables a product only on the literal "true"; default to active so
     * an import does not silently produce a catalogue of disabled products.
     */
    protected function formatStatus(mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return 'true';
        }

        return \in_array(strtolower((string) $raw), ['1', 'true', 'active', 'yes', 'on', 'enabled'], true) ? 'true' : 'false';
    }

    /**
     * Build a url_key slug from an explicit value, falling back to the name
     * then the SKU.
     *
     * @param  array<string, mixed>  $row
     */
    protected function buildUrlKey(array $row): string
    {
        $source = $row['url_key'] ?? $row['name'] ?? $row['sku'] ?? '';

        return Str::slug((string) $source);
    }

    /**
     * Lowercase and trim every column key of a row.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeKeys(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[strtolower(trim((string) $key))] = $value;
        }

        return $normalized;
    }
}
