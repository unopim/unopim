<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SENSITIVE = ['password', 'api_token', 'remember_token', 'sso_identifier'];

    public function up(): void
    {
        DB::table('audits')
            ->where('auditable_type', 'like', '%\\\\Admin')
            ->orderBy('id')
            ->chunkById(500, function ($audits) {
                foreach ($audits as $audit) {
                    $old = $this->scrub($audit->old_values);
                    $new = $this->scrub($audit->new_values);

                    if (
                        $old === $audit->old_values
                        && $new === $audit->new_values
                    ) {
                        continue;
                    }

                    DB::table('audits')
                        ->where('id', $audit->id)
                        ->update([
                            'old_values' => $old,
                            'new_values' => $new,
                        ]);
                }
            });
    }

    public function down(): void {}

    private function scrub(?string $values): ?string
    {
        if (! $values) {
            return $values;
        }

        $decoded = json_decode($values, true);

        if (! is_array($decoded)) {
            return $values;
        }

        $remaining = array_diff_key($decoded, array_flip(self::SENSITIVE));

        if (count($remaining) === count($decoded)) {
            return $values;
        }

        return json_encode($remaining);
    }
};
