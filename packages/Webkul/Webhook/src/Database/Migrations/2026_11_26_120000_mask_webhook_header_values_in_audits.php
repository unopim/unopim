<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\Webhook\Models\Webhook;

return new class extends Migration
{
    private const array COLUMNS = ['old_values', 'new_values'];

    public function up(): void
    {
        DB::table('audits')
            ->where('auditable_type', Webhook::class)
            ->lazyById()
            ->each(function (object $audit): void {
                $changes = $this->maskedColumns($audit);

                if ($changes !== []) {
                    DB::table('audits')->where('id', $audit->id)->update($changes);
                }
            });
    }

    public function down(): void {}

    /**
     * @return array<string, string>
     */
    private function maskedColumns(object $audit): array
    {
        $changes = [];

        foreach (self::COLUMNS as $column) {
            $values = json_decode((string) $audit->{$column}, true);

            if (! is_array($values) || ! array_key_exists('headers', $values)) {
                continue;
            }

            $masked = [...$values, 'headers' => Webhook::maskHeaders($values['headers'])];

            if ($masked !== $values) {
                $changes[$column] = json_encode($masked, JSON_UNESCAPED_UNICODE);
            }
        }

        return $changes;
    }
};
