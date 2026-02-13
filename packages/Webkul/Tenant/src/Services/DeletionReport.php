<?php

namespace Webkul\Tenant\Services;

class DeletionReport
{
    public function __construct(
        private array $purgeResult,
        private array $verificationResult,
    ) {}

    public function toConsoleOutput(): array
    {
        $lines = [];
        $lines[] = '=== Tenant Deletion Completeness Report ===';
        $lines[] = "Tenant ID: {$this->purgeResult['tenant_id']}";
        $lines[] = '';

        $lines[] = '--- Database Tables ---';
        $totalRows = 0;
        foreach ($this->purgeResult['tables'] as $table => $count) {
            $lines[] = "  {$table}: {$count} rows deleted";
            $totalRows += $count;
        }
        $lines[] = "  Total: {$totalRows} rows deleted across ".count($this->purgeResult['tables']).' tables';
        $lines[] = '';

        $lines[] = '--- Cache ---';
        $lines[] = "  Keys cleared: {$this->purgeResult['cache']['keys_cleared']}";
        $lines[] = '';

        $lines[] = '--- Storage ---';
        $lines[] = "  Paths removed: {$this->purgeResult['storage']['paths_removed']}";
        $lines[] = "  Files removed: {$this->purgeResult['storage']['files_removed']}";
        $lines[] = '';

        $lines[] = '--- Elasticsearch ---';
        $lines[] = "  Indices deleted: {$this->purgeResult['elasticsearch']['indices_deleted']}";
        $lines[] = '';

        $lines[] = '--- Verification ---';
        $lines[] = "  Status: {$this->verificationResult['status']}";
        if (! empty($this->verificationResult['residual'])) {
            foreach ($this->verificationResult['residual'] as $table => $count) {
                $lines[] = "  RESIDUAL: {$table} has {$count} remaining rows";
            }
        }

        return $lines;
    }

    public function toJson(): string
    {
        return json_encode([
            'purge'        => $this->purgeResult,
            'verification' => $this->verificationResult,
        ], JSON_PRETTY_PRINT);
    }

    public function isComplete(): bool
    {
        return $this->verificationResult['status'] === 'COMPLETE';
    }
}
