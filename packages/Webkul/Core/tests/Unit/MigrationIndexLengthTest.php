<?php

use Illuminate\Support\Facades\File;

/**
 * MySQL limits identifier names (index, unique, foreign key) to 64 characters.
 * Laravel auto-generates these names as: {prefix}{table}_{column(s)}_{type}
 *
 * When a table prefix like "wk_" is configured (via DB_PREFIX), the prefix is
 * prepended to the table name in the generated identifier, which can push names
 * over the 64-character limit.
 *
 * This test scans every migration file and verifies that no auto-generated
 * index/unique/foreign name exceeds 64 characters with the "wk_" prefix.
 */
const MYSQL_IDENTIFIER_LIMIT = 64;
const TABLE_PREFIX = 'wk_';

it('does not generate index names exceeding MySQL 64-character limit with wk_ prefix', function () {
    $migrationDirs = [
        base_path('database/migrations'),
        ...glob(base_path('packages/Webkul/*/src/Database/Migrations')),
        ...glob(base_path('packages/Webkul/*/Database/Migration')),
    ];

    $violations = [];

    foreach ($migrationDirs as $dir) {
        if (! is_dir($dir)) {
            continue;
        }

        $files = File::glob($dir.'/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);

            // Extract all Schema::create calls with their table names and body
            preg_match_all(
                '/Schema::create\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*function\s*\(\s*Blueprint\s+\$(\w+)\s*\)\s*\{(.*?)\}\s*\)/s',
                $content,
                $createMatches,
                PREG_SET_ORDER
            );

            foreach ($createMatches as $createMatch) {
                $tableName = $createMatch[1];
                $varName = $createMatch[2];
                $body = $createMatch[3];

                // Find unique() calls without explicit name: $table->unique(['col1', 'col2']) or $table->unique('col')
                // We need to exclude calls that have an explicit name parameter
                preg_match_all(
                    '/\$'.$varName.'->unique\(\s*(\[[^\]]+\]|[\'"][^\'"]+[\'"])\s*\)/',
                    $body,
                    $uniqueMatches
                );

                foreach ($uniqueMatches[1] as $columnExpr) {
                    $columns = parseColumns($columnExpr);
                    $generatedName = TABLE_PREFIX.$tableName.'_'.implode('_', $columns).'_unique';

                    if (strlen($generatedName) > MYSQL_IDENTIFIER_LIMIT) {
                        $violations[] = sprintf(
                            '%s: table "%s" unique(%s) generates "%s" (%d chars)',
                            $filename,
                            $tableName,
                            implode(', ', $columns),
                            $generatedName,
                            strlen($generatedName)
                        );
                    }
                }

                // Find index() calls without explicit name: $table->index(['col1', 'col2']) or $table->index('col')
                preg_match_all(
                    '/\$'.$varName.'->index\(\s*(\[[^\]]+\]|[\'"][^\'"]+[\'"])\s*\)/',
                    $body,
                    $indexMatches
                );

                foreach ($indexMatches[1] as $columnExpr) {
                    $columns = parseColumns($columnExpr);
                    $generatedName = TABLE_PREFIX.$tableName.'_'.implode('_', $columns).'_index';

                    if (strlen($generatedName) > MYSQL_IDENTIFIER_LIMIT) {
                        $violations[] = sprintf(
                            '%s: table "%s" index(%s) generates "%s" (%d chars)',
                            $filename,
                            $tableName,
                            implode(', ', $columns),
                            $generatedName,
                            strlen($generatedName)
                        );
                    }
                }

                // Find foreign() calls without explicit name — only the simple form:
                // $table->foreign('col') — not $table->foreign('col', 'explicit_name')
                preg_match_all(
                    '/\$'.$varName.'->foreign\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
                    $body,
                    $foreignMatches
                );

                foreach ($foreignMatches[1] as $column) {
                    $generatedName = TABLE_PREFIX.$tableName.'_'.$column.'_foreign';

                    if (strlen($generatedName) > MYSQL_IDENTIFIER_LIMIT) {
                        $violations[] = sprintf(
                            '%s: table "%s" foreign(%s) generates "%s" (%d chars)',
                            $filename,
                            $tableName,
                            $column,
                            $generatedName,
                            strlen($generatedName)
                        );
                    }
                }
            }

            // Also check Schema::table (alter table) calls
            preg_match_all(
                '/Schema::table\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*function\s*\(\s*Blueprint\s+\$(\w+)\s*\)\s*\{(.*?)\}\s*\)/s',
                $content,
                $alterMatches,
                PREG_SET_ORDER
            );

            foreach ($alterMatches as $alterMatch) {
                $tableName = $alterMatch[1];
                $varName = $alterMatch[2];
                $body = $alterMatch[3];

                // unique() without explicit name
                preg_match_all(
                    '/\$'.$varName.'->unique\(\s*(\[[^\]]+\]|[\'"][^\'"]+[\'"])\s*\)/',
                    $body,
                    $uniqueMatches
                );

                foreach ($uniqueMatches[1] as $columnExpr) {
                    $columns = parseColumns($columnExpr);
                    $generatedName = TABLE_PREFIX.$tableName.'_'.implode('_', $columns).'_unique';

                    if (strlen($generatedName) > MYSQL_IDENTIFIER_LIMIT) {
                        $violations[] = sprintf(
                            '%s: table "%s" unique(%s) generates "%s" (%d chars)',
                            $filename,
                            $tableName,
                            implode(', ', $columns),
                            $generatedName,
                            strlen($generatedName)
                        );
                    }
                }

                // index() without explicit name
                preg_match_all(
                    '/\$'.$varName.'->index\(\s*(\[[^\]]+\]|[\'"][^\'"]+[\'"])\s*\)/',
                    $body,
                    $indexMatches
                );

                foreach ($indexMatches[1] as $columnExpr) {
                    $columns = parseColumns($columnExpr);
                    $generatedName = TABLE_PREFIX.$tableName.'_'.implode('_', $columns).'_index';

                    if (strlen($generatedName) > MYSQL_IDENTIFIER_LIMIT) {
                        $violations[] = sprintf(
                            '%s: table "%s" index(%s) generates "%s" (%d chars)',
                            $filename,
                            $tableName,
                            implode(', ', $columns),
                            $generatedName,
                            strlen($generatedName)
                        );
                    }
                }

                // foreign() without explicit name
                preg_match_all(
                    '/\$'.$varName.'->foreign\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
                    $body,
                    $foreignMatches
                );

                foreach ($foreignMatches[1] as $column) {
                    $generatedName = TABLE_PREFIX.$tableName.'_'.$column.'_foreign';

                    if (strlen($generatedName) > MYSQL_IDENTIFIER_LIMIT) {
                        $violations[] = sprintf(
                            '%s: table "%s" foreign(%s) generates "%s" (%d chars)',
                            $filename,
                            $tableName,
                            $column,
                            $generatedName,
                            strlen($generatedName)
                        );
                    }
                }
            }
        }
    }

    $this->assertEmpty(
        $violations,
        "Auto-generated index names exceed MySQL's 64-character limit with '".TABLE_PREFIX."' prefix. "
        ."Add explicit shorter names:\n".implode("\n", $violations)
    );
});

/**
 * Parse column names from a regex-captured expression.
 * Handles both single column ('col') and array (['col1', 'col2']) forms.
 */
function parseColumns(string $expr): array
{
    // Remove surrounding brackets if array
    $expr = trim($expr, '[] ');

    // Extract all quoted strings
    preg_match_all('/[\'"]([^\'"]+)[\'"]/', $expr, $matches);

    return $matches[1] ?? [];
}
