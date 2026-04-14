<?php

namespace Webkul\Installer\Database\Data\Generators;

use Illuminate\Support\Facades\Storage;

/**
 * Produces a small (~2 KB) SVG placeholder per product with the product's
 * name, pack type, and brand colour baked into the visual. Used by every
 * reference-catalog seeder so the admin grid isn't full of grey squares
 * but we still ship zero binary image assets in git.
 *
 * The generator is pure — same input always produces the same SVG — so
 * it's safe to re-run and diff.
 */
class SvgPlaceholderGenerator
{
    /**
     * Minimal brand-code → hex palette. Unknown brands fall back to slate.
     *
     * @var array<string, array{0: string, 1: string}> [bg, accent]
     */
    protected const BRAND_PALETTE = [
        'globalstore_classic' => ['#1e1b4b', '#ef4444'],
        'globalstore_organic' => ['#14532d', '#84cc16'],
        'globalstore_premium' => ['#3f3f46', '#fbbf24'],
        'summit_dairy'        => ['#f1f5f9', '#0f172a'],
        'pure_springs'        => ['#cffafe', '#0891b2'],
        'orchard_valley'      => ['#fff7ed', '#ea580c'],
        'harvest_grove'       => ['#fefce8', '#ca8a04'],
        'crunch_co'           => ['#fff1f2', '#be123c'],
        'sunrise_cereals'     => ['#fef3c7', '#d97706'],
        'nova_bakery'         => ['#fef9c3', '#a16207'],
        'cocoa_house'         => ['#1c1917', '#92400e'],
        'verdant_kitchen'     => ['#f0fdf4', '#166534'],
        'mediterra'           => ['#fefce8', '#65a30d'],
        'alpine_roast'        => ['#1c1917', '#d97706'],
        'pasta_reale'         => ['#fef3c7', '#b91c1c'],
    ];

    /**
     * Simple pack-type → shape hint. The generator uses this to draw the
     * right silhouette (bottle vs can vs jar vs carton).
     */
    protected const PACK_SHAPES = [
        'bottle'    => 'M 80 40 L 120 40 L 120 70 L 130 80 L 130 180 L 70 180 L 70 80 L 80 70 Z',
        'can'       => 'M 60 50 L 140 50 L 140 180 L 60 180 Z',
        'carton'    => 'M 55 40 L 145 40 L 145 180 L 55 180 Z',
        'jar'       => 'M 65 55 L 135 55 L 135 70 L 140 80 L 140 180 L 60 180 L 60 80 L 65 70 Z',
        'box'       => 'M 50 45 L 150 45 L 150 180 L 50 180 Z',
        'pouch'     => 'M 60 40 L 140 40 L 150 180 L 50 180 Z',
        'tetra_pak' => 'M 55 40 L 145 40 L 145 180 L 55 180 Z',
    ];

    /**
     * Write one SVG to the Laravel public disk under
     * `products/{family}/{sku-slug}.svg` and return the relative path.
     */
    public function generate(
        string $sku,
        string $family,
        string $name,
        ?string $brand = null,
        ?string $packType = null,
        ?string $netWeightG = null,
        ?string $netVolumeMl = null,
    ): string {
        $slug = strtolower(str_replace([' ', '_'], '-', $sku));
        $relativePath = "products/{$family}/{$slug}.svg";

        $svg = $this->buildSvg($sku, $name, $brand, $packType, $netWeightG, $netVolumeMl);

        Storage::disk('public')->put($relativePath, $svg);

        return $relativePath;
    }

    /**
     * Stateless render. Exposed for unit testing.
     */
    public function buildSvg(
        string $sku,
        string $name,
        ?string $brand,
        ?string $packType,
        ?string $netWeightG,
        ?string $netVolumeMl,
    ): string {
        [$bg, $accent] = self::BRAND_PALETTE[$brand ?? ''] ?? ['#e2e8f0', '#475569'];

        $shape = self::PACK_SHAPES[$packType ?? ''] ?? self::PACK_SHAPES['box'];

        $displayName = $this->wrap($name, 22);
        $sizeLine = $this->formatSize($netWeightG, $netVolumeMl);

        $textLines = '';
        foreach ($displayName as $index => $line) {
            $y = 215 + ($index * 14);
            $escaped = htmlspecialchars($line, ENT_XML1, 'UTF-8');
            $textLines .= <<<XML
    <text x="100" y="{$y}" text-anchor="middle" font-family="system-ui, sans-serif" font-size="11" fill="#1f2937">{$escaped}</text>

XML;
        }

        $sizeText = '';
        if ($sizeLine !== '') {
            $escaped = htmlspecialchars($sizeLine, ENT_XML1, 'UTF-8');
            $sizeText = <<<XML
    <text x="100" y="270" text-anchor="middle" font-family="system-ui, sans-serif" font-size="10" font-weight="bold" fill="#475569">{$escaped}</text>

XML;
        }

        $skuEscaped = htmlspecialchars($sku, ENT_XML1, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 290" width="200" height="290">
  <rect width="200" height="290" fill="#ffffff" stroke="#e5e7eb" stroke-width="1"/>
  <rect x="10" y="10" width="180" height="180" fill="{$bg}" rx="4"/>
  <path d="{$shape}" fill="{$accent}" stroke="#0f172a" stroke-width="1" opacity="0.9"/>
  <rect x="70" y="95" width="60" height="6" fill="#ffffff" opacity="0.6"/>
  <rect x="70" y="105" width="45" height="4" fill="#ffffff" opacity="0.5"/>
{$textLines}{$sizeText}  <text x="100" y="283" text-anchor="middle" font-family="ui-monospace, monospace" font-size="8" fill="#94a3b8">{$skuEscaped}</text>
</svg>
SVG;
    }

    /**
     * Break a long product name into short display lines so the SVG card
     * stays legible.
     *
     * @return string[]
     */
    protected function wrap(string $text, int $width): array
    {
        $text = trim($text);
        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $buffer = '';

        foreach ($words as $word) {
            if (strlen($buffer) + strlen($word) + 1 > $width) {
                if ($buffer !== '') {
                    $lines[] = $buffer;
                }
                $buffer = $word;
            } else {
                $buffer = $buffer === '' ? $word : $buffer.' '.$word;
            }

            if (count($lines) >= 3) {
                break;
            }
        }

        if ($buffer !== '' && count($lines) < 3) {
            $lines[] = $buffer;
        }

        return $lines;
    }

    protected function formatSize(?string $weight, ?string $volume): string
    {
        if ($volume !== null && $volume !== '') {
            $v = (float) $volume;

            return $v >= 1000 ? sprintf('%.1fL', $v / 1000) : sprintf('%dml', (int) $v);
        }

        if ($weight !== null && $weight !== '') {
            $w = (float) $weight;

            return $w >= 1000 ? sprintf('%.1fkg', $w / 1000) : sprintf('%dg', (int) $w);
        }

        return '';
    }

    public function ensureDirectoryExists(string $family): void
    {
        $path = "products/{$family}";

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }
    }
}
