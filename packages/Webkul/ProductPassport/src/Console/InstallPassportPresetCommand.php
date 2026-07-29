<?php

namespace Webkul\ProductPassport\Console;

use Illuminate\Console\Command;
use Webkul\ProductPassport\Database\Seeders\PassportPresetSeeder;

class InstallPassportPresetCommand extends Command
{
    protected $signature = 'unopim:passport:install-preset {--preset=* : Preset codes to install, defaults to every configured preset}';

    protected $description = 'Install ready-made passport templates from the passport_presets config, idempotently.';

    public function handle(PassportPresetSeeder $seeder): int
    {
        $requested = (array) $this->option('preset');

        $codes = $requested !== [] ? $requested : $seeder->available();

        if ($codes === []) {
            $this->components->warn(trans('passport::app.console.install-preset.none'));

            return self::SUCCESS;
        }

        foreach ($codes as $code) {
            $template = $seeder->run((string) $code);

            if ($template === null) {
                $this->components->error(trans('passport::app.console.install-preset.unknown', ['code' => $code]));

                continue;
            }

            $this->components->info(trans('passport::app.console.install-preset.installed', [
                'code'   => $template->code,
                'fields' => $template->fields()->count(),
            ]));
        }

        $this->components->info(trans('passport::app.console.install-preset.next-step'));

        return self::SUCCESS;
    }
}
