<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Core\Repositories\CoreConfigRepository;

return new class extends Migration
{
    /**
     * Encrypt password-type settings that were stored in plain text before
     * secrets were encrypted on save. Values that already decrypt are skipped.
     */
    public function up(): void
    {
        $this->rewrite(fn (CoreConfigRepository $repository, string $value): ?string => $repository->isEncryptedPayload($value)
            ? null
            : $repository->encryptSecret($value));
    }

    /**
     * Store the password-type settings in plain text again.
     */
    public function down(): void
    {
        $this->rewrite(fn (CoreConfigRepository $repository, string $value): ?string => $repository->isEncryptedPayload($value)
            ? $repository->decryptSecret($value)
            : null);
    }

    /**
     * Apply the callback to every stored password-type value; a null result leaves the row alone.
     */
    protected function rewrite(Closure $callback): void
    {
        if (! Schema::hasTable('core_config')) {
            return;
        }

        $repository = app(CoreConfigRepository::class);

        $rows = DB::table('core_config')
            ->whereIn('code', $repository->passwordFieldCodes())
            ->where('value', '!=', '')
            ->get(['id', 'value']);

        foreach ($rows as $row) {
            $value = $callback($repository, (string) $row->value);

            if ($value !== null) {
                DB::table('core_config')->where('id', $row->id)->update(['value' => $value]);
            }
        }
    }
};
