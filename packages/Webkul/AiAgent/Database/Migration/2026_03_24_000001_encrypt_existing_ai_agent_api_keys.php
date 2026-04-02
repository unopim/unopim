<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Widen the column — encrypted values are much longer than plaintext.
        Schema::table('ai_agent_credentials', function (Blueprint $table) {
            $table->text('apiKey')->change();
        });

        // Encrypt every existing plaintext key.
        foreach (DB::table('ai_agent_credentials')->get(['id', 'apiKey']) as $row) {
            // Skip rows that are already encrypted (re-running migration).
            try {
                Crypt::decryptString($row->apiKey);

                continue; // already encrypted
            } catch (DecryptException) {
                // Not encrypted yet — proceed.
            }

            DB::table('ai_agent_credentials')
                ->where('id', $row->id)
                ->update(['apiKey' => Crypt::encryptString($row->apiKey)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decrypt keys back to plaintext.
        foreach (DB::table('ai_agent_credentials')->get(['id', 'apiKey']) as $row) {
            try {
                $plain = Crypt::decryptString($row->apiKey);
            } catch (DecryptException) {
                continue; // already plaintext
            }

            DB::table('ai_agent_credentials')
                ->where('id', $row->id)
                ->update(['apiKey' => $plain]);
        }

        Schema::table('ai_agent_credentials', function (Blueprint $table) {
            $table->string('apiKey')->change();
        });
    }
};
