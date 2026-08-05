<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates a synthetic catalog of arbitrary size for the scale benchmarks.
 *
 * Rows are built server-side by INSERT ... SELECT so the JSON payload never
 * crosses the PHP boundary, and sharded across forked workers over disjoint
 * sequence ranges — safe because every generated identity is a pure function
 * of the sequence number. All output is prefixed so `--fresh` can remove it
 * without touching the curated demo products it derives from.
 */
class BenchmarkCatalogSeeder extends Seeder
{
    public const PREFIX = 'bench-';

    public const CATEGORY_PREFIX = 'bench-cat-';

    public const FAMILY_PREFIX = 'bench-fam-';

    /**
     * Rows per INSERT ... SELECT. Large enough to amortise the round trip,
     * small enough that a shard stays interruptible and the undo log for any
     * single statement stays bounded.
     */
    public const BATCH = 10000;

    /**
     * Share of top-level products that are configurable, per §4.1 of the
     * methodology (85% simple / 15% configurable with 3-8 variants).
     */
    public const CONFIGURABLE_SHARE = 0.15;

    protected Closure $report;

    /**
     * Build the catalog.
     *
     * @param  int  $products  Target top-level product count (variants are additional).
     * @param  int  $categories  Size of the generated category tree.
     * @param  int  $workers  Parallel insert processes.
     */
    public function run(
        int $products = 100000,
        int $categories = 50000,
        int $workers = 8,
        bool $fresh = false,
        bool $reference = true,
        ?Closure $report = null,
    ): array {
        $this->report = $report ?? fn (string $m) => null;

        if ($fresh) {
            $this->purge();
        }

        $this->buildNumbersTable();
        $this->buildSourcePool();

        if ($reference) {
            $this->buildLocales();
            $this->buildFamilies();
            $this->buildCategories($categories);
        }

        $configurables = (int) round($products * self::CONFIGURABLE_SHARE);
        $simples = $products - $configurables;

        $started = microtime(true);

        $this->say(sprintf('Generating %s simple products across %d workers...', number_format($simples), $workers));
        $this->shard($simples, $workers, fn (int $from, int $to) => $this->insertSimple($from, $to, $categories));

        $this->say(sprintf('Generating %s configurable products...', number_format($configurables)));
        $this->shard($configurables, $workers, fn (int $from, int $to) => $this->insertConfigurable($from, $to, $categories));

        $this->say('Generating variants...');
        $variants = $this->insertVariants($workers, $categories);

        return [
            'simple'        => $simples,
            'configurable'  => $configurables,
            'variants'      => $variants,
            'total'         => $simples + $configurables + $variants,
            'seconds'       => round(microtime(true) - $started, 1),
        ];
    }

    /**
     * Remove every generated row. Products cascade to variants and
     * completeness through their foreign keys, so only the roots are deleted.
     */
    public function purge(): void
    {
        $this->say('Removing previously generated benchmark rows...');

        do {
            $deleted = DB::table('products')->where('sku', 'like', self::PREFIX.'%')->limit(50000)->delete();
        } while ($deleted > 0);

        DB::table('categories')->where('code', 'like', self::CATEGORY_PREFIX.'%')->delete();
        DB::table('attribute_families')->where('code', 'like', self::FAMILY_PREFIX.'%')->delete();
    }

    /**
     * A persistent 0..BATCH-1 numbers table. Cheaper and less fragile than a
     * recursive CTE, which is capped by cte_max_recursion_depth.
     */
    protected function buildNumbersTable(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS bench_seq (i INT UNSIGNED NOT NULL PRIMARY KEY)');

        if ((int) DB::table('bench_seq')->count() >= self::BATCH) {
            return;
        }

        DB::table('bench_seq')->truncate();

        foreach (array_chunk(range(0, self::BATCH - 1), 2000) as $chunk) {
            DB::table('bench_seq')->insert(array_map(fn (int $i) => ['i' => $i], $chunk));
        }
    }

    /**
     * Snapshot the curated products into a numbered pool so a shard can pick
     * its template by modulo without an ORDER BY over the live table.
     */
    protected function buildSourcePool(): void
    {
        DB::statement('DROP TABLE IF EXISTS bench_source');
        DB::statement('CREATE TABLE bench_source (
            rn INT UNSIGNED NOT NULL PRIMARY KEY,
            attribute_family_id INT UNSIGNED NULL,
            `values` JSON NULL
        )');

        DB::statement('INSERT INTO bench_source (rn, attribute_family_id, `values`)
            SELECT ROW_NUMBER() OVER (ORDER BY id), attribute_family_id, `values`
            FROM products
            WHERE parent_id IS NULL AND sku NOT LIKE ?', [self::PREFIX.'%']);

        $count = (int) DB::table('bench_source')->count();

        if ($count === 0) {
            throw new \RuntimeException('No curated products to derive the benchmark catalog from — seed the demo data first.');
        }

        $this->say(sprintf('Source pool: %d curated products.', $count));
    }

    /**
     * The methodology specifies 5 locales; the demo install activates 3.
     */
    protected function buildLocales(): void
    {
        DB::table('locales')->whereIn('code', ['es_ES', 'it_IT'])->update(['status' => 1]);

        $this->say(sprintf('Active locales: %d.', DB::table('locales')->where('status', 1)->count()));
    }

    /**
     * Pad the family count to the 50 the XL tier specifies by cloning existing
     * families together with their group and attribute mappings, so a
     * generated family resolves the same attribute set as its template.
     */
    protected function buildFamilies(int $target = 50): void
    {
        $sources = DB::table('attribute_families')->where('code', 'not like', self::FAMILY_PREFIX.'%')->get(['id', 'code']);
        $existing = (int) DB::table('attribute_families')->count();

        for ($i = $existing; $i < $target; $i++) {
            $source = $sources[($i - $existing) % $sources->count()];
            $code = self::FAMILY_PREFIX.($i + 1);

            $familyId = DB::table('attribute_families')->insertGetId(['code' => $code, 'status' => 1]);

            $groups = DB::table('attribute_family_group_mappings')->where('attribute_family_id', $source->id)->get();

            foreach ($groups as $group) {
                $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
                    'attribute_family_id' => $familyId,
                    'attribute_group_id'  => $group->attribute_group_id,
                    'position'            => $group->position,
                ]);

                DB::statement('INSERT INTO attribute_group_mappings (attribute_id, attribute_family_group_id, position)
                    SELECT attribute_id, ?, position FROM attribute_group_mappings WHERE attribute_family_group_id = ?', [$mappingId, $group->id]);
            }
        }

        $this->say(sprintf('Attribute families: %d.', DB::table('attribute_families')->count()));
    }

    /**
     * A flat tree of generated categories hung off a single generated root,
     * with nested-set bounds allocated after the existing tree so the curated
     * categories keep valid bounds.
     */
    protected function buildCategories(int $target): void
    {
        if ((int) DB::table('categories')->where('code', 'like', self::CATEGORY_PREFIX.'%')->count() >= $target) {
            return;
        }

        DB::table('categories')->where('code', 'like', self::CATEGORY_PREFIX.'%')->delete();

        $offset = (int) DB::table('categories')->max('_rgt') + 1;

        $rootId = DB::table('categories')->insertGetId([
            'code'       => self::CATEGORY_PREFIX.'root',
            '_lft'       => $offset,
            '_rgt'       => $offset + (2 * $target) + 1,
            'parent_id'  => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->say(sprintf('Generating %s categories...', number_format($target)));

        for ($from = 0; $from < $target; $from += self::BATCH) {
            $size = min(self::BATCH, $target - $from);

            DB::statement(sprintf(
                'INSERT INTO categories (code, _lft, _rgt, parent_id, created_at, updated_at)
                 SELECT CONCAT(%s, %d + n.i + 1), %d + (2 * (%d + n.i)) + 1, %d + (2 * (%d + n.i)) + 2, %d, NOW(), NOW()
                 FROM bench_seq n WHERE n.i < %d',
                DB::getPdo()->quote(self::CATEGORY_PREFIX),
                $from,
                $offset, $from,
                $offset, $from,
                $rootId,
                $size
            ));
        }
    }

    /**
     * Split a target count into contiguous per-worker ranges and run them in
     * forked children. Each child reconnects, because the parent's MySQL
     * socket cannot be shared across processes.
     *
     * $granularity is the smallest unit worth giving a worker; the variant
     * pass shards over parent-id ranges rather than row counts and so passes 1.
     */
    protected function shard(int $total, int $workers, Closure $work, int $granularity = self::BATCH): void
    {
        if ($total <= 0) {
            return;
        }

        $workers = max(1, min($workers, (int) ceil($total / $granularity)));
        $per = (int) ceil($total / $workers);

        if ($workers === 1 || ! function_exists('pcntl_fork')) {
            $work(0, $total);

            return;
        }

        $children = [];

        for ($w = 0; $w < $workers; $w++) {
            $from = $w * $per;
            $to = min($total, $from + $per);

            if ($from >= $to) {
                continue;
            }

            $pid = pcntl_fork();

            if ($pid === 0) {
                DB::purge();
                DB::reconnect();

                try {
                    $work($from, $to);
                    exit(0);
                } catch (\Throwable $e) {
                    fwrite(STDERR, 'worker '.$w.': '.$e->getMessage().PHP_EOL);
                    exit(1);
                }
            }

            $children[] = $pid;
        }

        $failed = 0;

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);

            if (pcntl_wexitstatus($status) !== 0) {
                $failed++;
            }
        }

        if ($failed > 0) {
            throw new \RuntimeException($failed.' generation worker(s) failed — see stderr above.');
        }
    }

    protected function insertSimple(int $from, int $to, int $categories): void
    {
        $this->insertTopLevel($from, $to, $categories, 'simple', 's');
    }

    protected function insertConfigurable(int $from, int $to, int $categories): void
    {
        $this->insertTopLevel($from, $to, $categories, 'configurable', 'c');
    }

    /**
     * The generating statement. Integers are interpolated rather than bound:
     * they are loop counters the caller controls, and binding them would
     * prevent MySQL from folding the arithmetic into the SELECT.
     */
    protected function insertTopLevel(int $from, int $to, int $categories, string $type, string $tag): void
    {
        $sourceCount = (int) DB::table('bench_source')->count();
        $prefix = DB::getPdo()->quote(self::PREFIX.$tag.'-');
        $catPrefix = DB::getPdo()->quote(self::CATEGORY_PREFIX);
        $quotedType = DB::getPdo()->quote($type);

        for ($offset = $from; $offset < $to; $offset += self::BATCH) {
            $size = min(self::BATCH, $to - $offset);

            DB::statement(sprintf(
                'INSERT INTO products (sku, status, type, parent_id, attribute_family_id, `values`, created_at, updated_at)
                 SELECT
                   CONCAT(%1$s, %2$d + n.i),
                   1,
                   %3$s,
                   NULL,
                   src.attribute_family_id,
                   JSON_SET(
                     JSON_REMOVE(src.`values`, "$.common.image", "$.common.gallery", "$.common.spec_sheet"),
                     "$.common.sku",     CONCAT(%1$s, %2$d + n.i),
                     "$.common.url_key", CONCAT(%1$s, %2$d + n.i),
                     "$.common.ean",     LPAD((%2$d + n.i) %% 10000000000000, 13, "0"),
                     "$.categories",     JSON_ARRAY(
                        CONCAT(%4$s, ((%2$d + n.i) %% %5$d) + 1),
                        CONCAT(%4$s, (((%2$d + n.i) * 7) %% %5$d) + 1)
                     )
                   ),
                   NOW(), NOW()
                 FROM bench_seq n
                 JOIN bench_source src ON src.rn = ((%2$d + n.i) %% %6$d) + 1
                 WHERE n.i < %7$d',
                $prefix,
                $offset,
                $quotedType,
                $catPrefix,
                max(1, $categories),
                $sourceCount,
                $size
            ));
        }
    }

    /**
     * Variants are generated in a second pass that reads the parent id back
     * from the table, so parentage is correct without pre-allocating ids or
     * relying on AUTO_INCREMENT arithmetic.
     *
     * Variant count per parent is 3-8, derived from the parent id so the
     * distribution is deterministic and reproducible across runs.
     */
    protected function insertVariants(int $workers, int $categories): int
    {
        $prefix = DB::getPdo()->quote(self::PREFIX.'v-');
        $parentPrefix = self::PREFIX.'c-%';

        $bounds = DB::table('products')
            ->where('sku', 'like', $parentPrefix)
            ->selectRaw('MIN(id) AS lo, MAX(id) AS hi, COUNT(*) AS n')
            ->first();

        if (! $bounds || $bounds->n == 0) {
            return 0;
        }

        $before = (int) DB::table('products')->count();

        $span = (int) ceil((($bounds->hi - $bounds->lo) + 1) / max(1, $workers));

        $this->shard($workers, $workers, function (int $from, int $to) use ($bounds, $span, $prefix): void {
            for ($w = $from; $w < $to; $w++) {
                $lo = $bounds->lo + ($w * $span);
                $hi = min($bounds->hi, $lo + $span - 1);

                for ($chunkLo = $lo; $chunkLo <= $hi; $chunkLo += 2000) {
                    $chunkHi = min($hi, $chunkLo + 1999);

                    DB::statement(sprintf(
                        'INSERT INTO products (sku, status, type, parent_id, attribute_family_id, `values`, created_at, updated_at)
                         SELECT
                           CONCAT(%1$s, p.id, "-", n.i),
                           1, "simple", p.id, p.attribute_family_id,
                           JSON_SET(p.`values`,
                             "$.common.sku",     CONCAT(%1$s, p.id, "-", n.i),
                             "$.common.url_key", CONCAT(%1$s, p.id, "-", n.i),
                             "$.common.ean",     LPAD((p.id * 10 + n.i) %% 10000000000000, 13, "0")
                           ),
                           NOW(), NOW()
                         FROM products p
                         JOIN bench_seq n ON n.i < 3 + (p.id %% 6)
                         WHERE p.id BETWEEN %2$d AND %3$d AND p.type = "configurable"',
                        $prefix,
                        $chunkLo,
                        $chunkHi
                    ));
                }
            }
        }, 1);

        return (int) DB::table('products')->count() - $before;
    }

    protected function say(string $message): void
    {
        ($this->report)($message);
    }
}
