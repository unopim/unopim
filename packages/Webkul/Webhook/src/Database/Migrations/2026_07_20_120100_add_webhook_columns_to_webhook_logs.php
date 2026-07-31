<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('webhook_id')->nullable()->after('id');
            $table->string('event')->nullable()->after('sku');
            $table->unsignedSmallInteger('http_code')->nullable()->after('status');

            $table->index('webhook_id');
            $table->index('event');
            $table->index('http_code');
            $table->index(['webhook_id', 'created_at']);
            $table->index(['status', 'created_at']);

            $table->foreign('webhook_id')->references('id')->on('webhooks')->nullOnDelete();
        });

        $this->backfillHttpCode();
    }

    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table): void {
            $table->dropForeign(['webhook_id']);
            $table->dropIndex(['webhook_id']);
            $table->dropIndex(['event']);
            $table->dropIndex(['http_code']);
            $table->dropIndex(['webhook_id', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['webhook_id', 'event', 'http_code']);
        });
    }

    /**
     * Lift the HTTP status out of the existing JSON blob into the indexed
     * column so historical rows filter as fast as new ones. Chunked to stay
     * within memory on tables that have grown to millions of rows.
     */
    protected function backfillHttpCode(): void
    {
        DB::table('webhook_logs')
            ->select('id', 'extra')
            ->whereNotNull('extra')
            ->orderBy('id')
            ->chunkById(1000, function ($rows): void {
                foreach ($rows as $row) {
                    $extra = is_string($row->extra) ? json_decode($row->extra, true) : $row->extra;
                    $code = $extra['response']['status'] ?? null;

                    if (is_numeric($code) && (int) $code > 0) {
                        DB::table('webhook_logs')->where('id', $row->id)->update([
                            'http_code' => (int) $code,
                        ]);
                    }
                }
            });
    }
};
