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
     * Mask the header values and URL credentials in both value columns of a webhook audit row.
     *
     * @return array<string, string>
     */
    private function maskedColumns(object $audit): array
    {
        $changes = [];

        foreach (self::COLUMNS as $column) {
            $values = json_decode((string) $audit->{$column}, true);

            if (! is_array($values)) {
                continue;
            }

            $masked = $values;

            if (array_key_exists('headers', $values)) {
                $masked['headers'] = Webhook::maskHeaders($values['headers']);
            }

            if (array_key_exists('url', $values)) {
                $masked['url'] = Webhook::maskUrl($values['url']);
            }

            if ($masked !== $values) {
                $changes[$column] = json_encode($masked, JSON_UNESCAPED_UNICODE);
            }
        }

        return $changes;
    }
};
