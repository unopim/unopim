<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Webkul\Installer\Database\Seeders\Demo\BenchmarkCatalogSeeder;

/**
 * Dataset generator for the published scale benchmarks.
 *
 * The "UnoPim at Scale" methodology defines four tiers — S (100k), M (1M),
 * L (5M) and XL (10M). Because the generator only ever appends, a single
 * database can be grown through every tier in turn and measured at each
 * checkpoint, rather than built four times.
 */
#[Description('Generate a synthetic benchmark catalog of arbitrary size from the curated demo products.')]
#[Signature('unopim:benchmark:seed
        { --products=100000 : Target top-level product count; variants are generated on top. }
        { --categories=50000 : Size of the generated category tree. }
        { --workers=8 : Parallel insert processes. }
        { --fresh : Delete previously generated benchmark rows first. }
        { --skip-reference : Do not touch locales, families or categories. }
        { --force : Run without confirmation. }')]
class SeedBenchmarkCatalog extends Command
{
    use ConfirmableTrait;

    /**
     * Execute the console command.
     */
    public function handle(BenchmarkCatalogSeeder $seeder): int
    {
        $products = (int) $this->option('products');
        $workers = (int) $this->option('workers');

        if ($products < 1) {
            $this->error('--products must be at least 1.');

            return self::FAILURE;
        }

        $this->components->warn(sprintf(
            'Generating ~%s products with %d workers. This writes directly to the products table.',
            number_format($products),
            $workers
        ));

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $result = $seeder->run(
            $products,
            (int) $this->option('categories'),
            $workers,
            (bool) $this->option('fresh'),
            ! $this->option('skip-reference'),
            fn (string $message) => $this->line('  '.$message),
        );

        $this->newLine();
        $this->components->twoColumnDetail('Simple products', number_format($result['simple']));
        $this->components->twoColumnDetail('Configurable products', number_format($result['configurable']));
        $this->components->twoColumnDetail('Variants', number_format($result['variants']));
        $this->components->twoColumnDetail('<fg=green>Total generated</>', '<fg=green>'.number_format($result['total']).'</>');
        $this->components->twoColumnDetail('Elapsed', $result['seconds'].'s');
        $this->components->twoColumnDetail('Throughput', number_format((int) ($result['total'] / max(0.1, $result['seconds']))).' rows/sec');

        return self::SUCCESS;
    }
}
