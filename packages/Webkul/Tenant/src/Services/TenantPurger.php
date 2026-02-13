<?php

namespace Webkul\Tenant\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Tenant\Models\Tenant;

class TenantPurger
{
    /**
     * Purge all data belonging to a tenant from every storage layer.
     */
    public function purge(Tenant $tenant): array
    {
        $tenantId = $tenant->id;
        $report = [
            'tenant_id'     => $tenantId,
            'tables'        => [],
            'cache'         => ['keys_cleared' => 0],
            'storage'       => ['paths_removed' => 0, 'files_removed' => 0],
            'elasticsearch' => ['indices_deleted' => 0],
        ];

        // 1. Purge database tables
        $tables = $this->findTenantScopedTables();

        $this->disableForeignKeys();

        foreach ($tables as $table) {
            $count = DB::table($table)->where('tenant_id', $tenantId)->count();
            if ($count > 0) {
                DB::table($table)->where('tenant_id', $tenantId)->delete();
            }
            $report['tables'][$table] = $count;
        }

        $this->enableForeignKeys();

        // 2. Clear tenant cache
        $report['cache']['keys_cleared'] = $this->clearTenantCache($tenantId);

        // 3. Remove tenant storage
        $report['storage'] = $this->removeTenantStorage($tenantId);

        // 4. Delete ES indices
        $report['elasticsearch']['indices_deleted'] = $this->deleteElasticsearchIndices($tenant);

        return $report;
    }

    /**
     * Find all tables that have a tenant_id column.
     */
    public function findTenantScopedTables(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return $this->findTenantTablesForSqlite();
        }

        return $this->findTenantTablesForMysql();
    }

    /**
     * Verify zero residual data for a deleted tenant.
     */
    public function verify(int $tenantId): array
    {
        $tables = $this->findTenantScopedTables();
        $residual = [];
        $status = 'COMPLETE';

        foreach ($tables as $table) {
            $count = DB::table($table)->where('tenant_id', $tenantId)->count();
            if ($count > 0) {
                $residual[$table] = $count;
                $status = 'INCOMPLETE';
            }
        }

        return [
            'status'   => $status,
            'residual' => $residual,
        ];
    }

    private function findTenantTablesForSqlite(): array
    {
        $tables = [];
        $allTables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        foreach ($allTables as $tableRow) {
            $columns = DB::select("PRAGMA table_info('{$tableRow->name}')");
            foreach ($columns as $column) {
                if ($column->name === 'tenant_id') {
                    $tables[] = $tableRow->name;
                    break;
                }
            }
        }

        return $tables;
    }

    private function findTenantTablesForMysql(): array
    {
        $database = DB::getDatabaseName();
        $results = DB::select(
            'SELECT DISTINCT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = ? AND TABLE_SCHEMA = ?',
            ['tenant_id', $database]
        );

        return array_map(fn ($row) => $row->TABLE_NAME, $results);
    }

    private function disableForeignKeys(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }
    }

    private function enableForeignKeys(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    private function clearTenantCache(int $tenantId): int
    {
        \Webkul\Tenant\Cache\TenantCache::flush($tenantId);

        return 1;
    }

    private function removeTenantStorage(int $tenantId): array
    {
        $removed = ['paths_removed' => 0, 'files_removed' => 0];
        $paths = [
            "tenant/{$tenantId}",
            "imports/tenant-{$tenantId}",
            "exports/tenant-{$tenantId}",
        ];

        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                $files = Storage::allFiles($path);
                $removed['files_removed'] += count($files);
                Storage::deleteDirectory($path);
                $removed['paths_removed']++;
            }
        }

        return $removed;
    }

    private function deleteElasticsearchIndices(Tenant $tenant): int
    {
        if (! $tenant->es_index_uuid) {
            return 0;
        }

        if (! config('elasticsearch.enabled')) {
            return 0;
        }

        $prefix = config('elasticsearch.prefix');
        $suffix = "_tenant_{$tenant->es_index_uuid}";
        $deleted = 0;

        $indices = [
            strtolower($prefix.$suffix.'_products'),
            strtolower($prefix.$suffix.'_categories'),
        ];

        foreach ($indices as $index) {
            try {
                \Webkul\Core\Facades\ElasticSearch::indices()->delete(['index' => $index]);
                $deleted++;
            } catch (\Throwable $e) {
                if (! str_contains($e->getMessage(), 'index_not_found_exception')) {
                    \Illuminate\Support\Facades\Log::channel('elasticsearch')->error(
                        "Failed to delete ES index {$index} for tenant {$tenant->id}: ".$e->getMessage()
                    );
                }
            }
        }

        return $deleted;
    }
}
