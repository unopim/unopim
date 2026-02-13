<?php

namespace Webkul\Tenant\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Tenant\Models\Tenant;

/**
 * Seeds realistic demo data for a tenant — products, categories, users, etc.
 *
 * Designed to be run AFTER TenantSeeder has created the baseline data
 * (channel, locale, currency, root category, admin, attribute family).
 */
class TenantDemoSeeder
{
    /**
     * Seed demo data for all active tenants.
     */
    public function seedAll(): array
    {
        $results = [];

        $tenants = Tenant::where('status', Tenant::STATUS_ACTIVE)->get();

        foreach ($tenants as $tenant) {
            $results[$tenant->name] = $this->seed($tenant);
        }

        return $results;
    }

    /**
     * Seed demo data for a single tenant.
     */
    public function seed(Tenant $tenant): array
    {
        $now = now();
        $localeCode = 'en_US';

        // Find tenant's baseline entities
        $channel = DB::table('channels')->where('tenant_id', $tenant->id)->first();
        $locale = DB::table('locales')->where('tenant_id', $tenant->id)->where('code', $localeCode)->first();
        $family = DB::table('attribute_families')->where('tenant_id', $tenant->id)->first();
        $rootCategory = DB::table('categories')->where('tenant_id', $tenant->id)->whereNull('parent_id')->first();
        $role = DB::table('roles')->where('tenant_id', $tenant->id)->first();

        if (! $channel || ! $locale || ! $family || ! $rootCategory || ! $role) {
            return ['error' => 'Missing baseline data — run TenantSeeder first'];
        }

        $result = [
            'tenant'     => $tenant->name,
            'categories' => [],
            'products'   => [],
            'users'      => [],
        ];

        // ─── 1. Categories ───────────────────────────────────────────
        $categoryData = $this->getCategoryData($tenant->name);
        $categoryIds = [];

        foreach ($categoryData as $catDef) {
            // Skip if this category code already exists for this tenant
            $existing = DB::table('categories')
                ->where('tenant_id', $tenant->id)
                ->where('code', $catDef['code'])
                ->first();

            if ($existing) {
                $categoryIds[$catDef['code']] = $existing->id;
                continue;
            }

            $parentId = $catDef['parent'] === null ? $rootCategory->id : ($categoryIds[$catDef['parent']] ?? $rootCategory->id);

            $catId = DB::table('categories')->insertGetId([
                'code'            => $catDef['code'],
                'parent_id'       => $parentId,
                '_lft'            => 0,
                '_rgt'            => 0,
                'tenant_id'       => $tenant->id,
                'additional_data' => json_encode([
                    'locale_specific' => [
                        $localeCode => ['name' => $catDef['name']],
                    ],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $categoryIds[$catDef['code']] = $catId;
            $result['categories'][] = $catDef['name'];
        }

        // Rebuild nested set
        try {
            \Webkul\Category\Models\Category::fixTree();
        } catch (\Throwable $e) {
            // Non-critical — tree still works
        }

        // ─── 2. Attribute Groups + mappings for the family ───────────
        $this->ensureAttributeGroups($family->id, $tenant->id, $localeCode, $now);

        // ─── 3. Products ─────────────────────────────────────────────
        $productData = $this->getProductData($tenant->name);

        foreach ($productData as $prod) {
            // Skip if product SKU already exists for this tenant
            if (DB::table('products')->where('tenant_id', $tenant->id)->where('sku', $prod['sku'])->exists()) {
                $result['products'][] = $prod['sku'] . ' (exists)';
                continue;
            }

            $values = [
                'common' => [
                    'sku'            => $prod['sku'],
                    'name'           => $prod['name'],
                    'url_key'        => Str::slug($prod['name']),
                    'short_description' => $prod['short_description'],
                    'weight'         => $prod['weight'],
                    'status'         => true,
                ],
                'locale_specific' => [
                    $localeCode => [
                        'name'              => $prod['name'],
                        'description'       => $prod['description'],
                        'short_description' => $prod['short_description'],
                        'meta_title'        => $prod['name'],
                        'meta_description'  => $prod['short_description'],
                    ],
                ],
                'channel_specific' => [
                    'default' => [
                        'price' => $prod['price'],
                        'cost'  => round($prod['price'] * 0.6, 2),
                    ],
                ],
            ];

            $productId = DB::table('products')->insertGetId([
                'sku'                 => $prod['sku'],
                'type'                => $prod['type'],
                'attribute_family_id' => $family->id,
                'values'              => json_encode($values),
                'status'              => 1,
                'tenant_id'           => $tenant->id,
                'created_at'          => $now->copy()->subDays(rand(1, 90)),
                'updated_at'          => $now,
            ]);

            $result['products'][] = $prod['sku'];
        }

        // ─── 4. Additional Users ─────────────────────────────────────
        $userData = $this->getUserData($tenant);

        foreach ($userData as $user) {
            // Skip if user email already exists for this tenant
            if (DB::table('admins')->where('tenant_id', $tenant->id)->where('email', $user['email'])->exists()) {
                $result['users'][] = ['email' => $user['email'], 'password' => $user['password'] . ' (exists)'];
                continue;
            }

            $userId = DB::table('admins')->insertGetId([
                'name'         => $user['name'],
                'email'        => $user['email'],
                'password'     => bcrypt($user['password']),
                'role_id'      => $role->id,
                'status'       => 1,
                'ui_locale_id' => $locale->id,
                'timezone'     => 'UTC',
                'tenant_id'    => $tenant->id,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            $result['users'][] = ['email' => $user['email'], 'password' => $user['password']];
        }

        return $result;
    }

    /**
     * Category definitions vary by tenant to make each look unique.
     */
    private function getCategoryData(string $tenantName): array
    {
        $base = [
            ['code' => 'electronics',    'name' => 'Electronics',       'parent' => null],
            ['code' => 'smartphones',    'name' => 'Smartphones',       'parent' => 'electronics'],
            ['code' => 'laptops',        'name' => 'Laptops',           'parent' => 'electronics'],
            ['code' => 'accessories',    'name' => 'Accessories',       'parent' => 'electronics'],
            ['code' => 'clothing',       'name' => 'Clothing',          'parent' => null],
            ['code' => 'mens',           'name' => "Men's Wear",        'parent' => 'clothing'],
            ['code' => 'womens',         'name' => "Women's Wear",      'parent' => 'clothing'],
            ['code' => 'home-garden',    'name' => 'Home & Garden',     'parent' => null],
            ['code' => 'furniture',      'name' => 'Furniture',         'parent' => 'home-garden'],
            ['code' => 'kitchen',        'name' => 'Kitchen',           'parent' => 'home-garden'],
        ];

        if ($tenantName === 'Brisk') {
            $base[] = ['code' => 'sports', 'name' => 'Sports & Outdoors', 'parent' => null];
            $base[] = ['code' => 'fitness', 'name' => 'Fitness Equipment', 'parent' => 'sports'];
        } elseif ($tenantName === 'TestCorp') {
            $base[] = ['code' => 'books', 'name' => 'Books & Media', 'parent' => null];
            $base[] = ['code' => 'fiction', 'name' => 'Fiction', 'parent' => 'books'];
        }

        return $base;
    }

    /**
     * Product definitions — realistic SKUs, prices, descriptions.
     */
    private function getProductData(string $tenantName): array
    {
        $prefix = strtoupper(substr($tenantName, 0, 3));

        return [
            // Electronics
            [
                'sku'               => "{$prefix}-PHONE-001",
                'name'              => 'Pro Max Smartphone 256GB',
                'type'              => 'simple',
                'price'             => 999.99,
                'weight'            => '0.22',
                'category'          => 'smartphones',
                'short_description' => 'Flagship smartphone with 6.7" OLED display, 48MP camera, 5G connectivity.',
                'description'       => '<p>Experience the ultimate mobile performance with the Pro Max Smartphone. Featuring a stunning 6.7-inch Super OLED display, advanced triple-camera system with 48MP main sensor, and blazing-fast 5G connectivity. Powered by the latest A18 chip with 8GB RAM for seamless multitasking.</p><ul><li>256GB Internal Storage</li><li>5000mAh Battery</li><li>IP68 Water Resistant</li><li>Wireless Charging</li></ul>',
            ],
            [
                'sku'               => "{$prefix}-PHONE-002",
                'name'              => 'Budget Smart Phone 128GB',
                'type'              => 'simple',
                'price'             => 349.99,
                'weight'            => '0.19',
                'category'          => 'smartphones',
                'short_description' => 'Affordable smartphone with great features for everyday use.',
                'description'       => '<p>Get the best value with the Budget Smart Phone. Features a 6.1-inch LCD display, 12MP camera, and 128GB storage. Perfect for social media, streaming, and daily communication.</p>',
            ],
            [
                'sku'               => "{$prefix}-LAP-001",
                'name'              => 'UltraBook Pro 15" Laptop',
                'type'              => 'simple',
                'price'             => 1499.99,
                'weight'            => '1.8',
                'category'          => 'laptops',
                'short_description' => '15-inch laptop with M3 chip, 16GB RAM, 512GB SSD for professional use.',
                'description'       => '<p>The UltraBook Pro delivers desktop-class performance in a sleek, portable design. With the M3 chip, 16GB unified memory, and 512GB SSD, tackle any creative or professional workflow with ease.</p><ul><li>15.3" Liquid Retina XDR Display</li><li>Up to 22 hours battery life</li><li>Thunderbolt 4 ports</li><li>Backlit Magic Keyboard</li></ul>',
            ],
            [
                'sku'               => "{$prefix}-LAP-002",
                'name'              => 'ChromeBook Lite 14"',
                'type'              => 'simple',
                'price'             => 299.99,
                'weight'            => '1.4',
                'category'          => 'laptops',
                'short_description' => 'Lightweight 14-inch Chromebook ideal for students and everyday browsing.',
                'description'       => '<p>Stay productive with the ChromeBook Lite. This lightweight laptop features a 14-inch FHD display, Intel Celeron processor, 4GB RAM, and 64GB eMMC storage. Boots in seconds and provides all-day battery life.</p>',
            ],
            [
                'sku'               => "{$prefix}-ACC-001",
                'name'              => 'Wireless Earbuds Pro',
                'type'              => 'simple',
                'price'             => 149.99,
                'weight'            => '0.06',
                'category'          => 'accessories',
                'short_description' => 'Premium wireless earbuds with active noise cancellation and spatial audio.',
                'description'       => '<p>Immerse yourself in sound with Wireless Earbuds Pro. Featuring adaptive active noise cancellation, spatial audio, and up to 30 hours total battery life with the charging case.</p>',
            ],
            [
                'sku'               => "{$prefix}-ACC-002",
                'name'              => 'USB-C Hub 7-in-1',
                'type'              => 'simple',
                'price'             => 59.99,
                'weight'            => '0.12',
                'category'          => 'accessories',
                'short_description' => '7-in-1 USB-C hub with HDMI, USB 3.0, SD card reader, and PD charging.',
                'description'       => '<p>Expand your connectivity with this versatile 7-in-1 USB-C hub. Includes HDMI 4K@60Hz, 2x USB 3.0, USB-C PD 100W, SD/microSD slots, and Gigabit Ethernet.</p>',
            ],
            // Clothing
            [
                'sku'               => "{$prefix}-CLO-001",
                'name'              => 'Premium Cotton T-Shirt',
                'type'              => 'simple',
                'price'             => 29.99,
                'weight'            => '0.25',
                'category'          => 'mens',
                'short_description' => '100% organic cotton crew-neck t-shirt, available in multiple colors.',
                'description'       => '<p>Crafted from 100% organic cotton for ultimate comfort. Features a classic crew-neck design, pre-shrunk fabric, and reinforced stitching for durability. Machine washable.</p>',
            ],
            [
                'sku'               => "{$prefix}-CLO-002",
                'name'              => 'Slim Fit Jeans',
                'type'              => 'simple',
                'price'             => 69.99,
                'weight'            => '0.65',
                'category'          => 'mens',
                'short_description' => 'Modern slim-fit jeans with stretch denim for comfortable all-day wear.',
                'description'       => '<p>Classic slim-fit jeans crafted from premium stretch denim. Features a mid-rise waist, 5-pocket design, and just the right amount of stretch for comfort throughout the day.</p>',
            ],
            [
                'sku'               => "{$prefix}-CLO-003",
                'name'              => 'Floral Summer Dress',
                'type'              => 'simple',
                'price'             => 89.99,
                'weight'            => '0.35',
                'category'          => 'womens',
                'short_description' => 'Elegant floral print midi dress, perfect for summer occasions.',
                'description'       => '<p>A beautiful floral print midi dress that transitions effortlessly from day to evening. Made from lightweight, breathable fabric with a flattering A-line silhouette and adjustable waist tie.</p>',
            ],
            // Home & Garden
            [
                'sku'               => "{$prefix}-HOM-001",
                'name'              => 'Ergonomic Office Chair',
                'type'              => 'simple',
                'price'             => 399.99,
                'weight'            => '18.5',
                'category'          => 'furniture',
                'short_description' => 'Fully adjustable ergonomic office chair with lumbar support and mesh back.',
                'description'       => '<p>Work in comfort with this premium ergonomic office chair. Features adjustable lumbar support, breathable mesh back, 4D armrests, and synchro-tilt mechanism. Supports up to 300 lbs and includes a 10-year warranty.</p>',
            ],
            [
                'sku'               => "{$prefix}-HOM-002",
                'name'              => 'Standing Desk Electric',
                'type'              => 'simple',
                'price'             => 599.99,
                'weight'            => '35.0',
                'category'          => 'furniture',
                'short_description' => 'Electric sit-stand desk with programmable height presets and cable management.',
                'description'       => '<p>Transform your workspace with this electric standing desk. Features dual motors for smooth height adjustment (28-48"), 4 programmable presets, anti-collision technology, and built-in cable management tray. Top measures 60" x 30".</p>',
            ],
            [
                'sku'               => "{$prefix}-KIT-001",
                'name'              => 'Chef Knife Set 8-Piece',
                'type'              => 'simple',
                'price'             => 199.99,
                'weight'            => '2.8',
                'category'          => 'kitchen',
                'short_description' => 'Professional 8-piece knife set with German steel blades and wooden block.',
                'description'       => '<p>Equip your kitchen with this professional-grade 8-piece knife set. Made from high-carbon German stainless steel with ergonomic handles. Includes chef knife, bread knife, santoku, utility, paring, steak knives (x2), and acacia wood block.</p>',
            ],
            [
                'sku'               => "{$prefix}-KIT-002",
                'name'              => 'Smart Coffee Maker',
                'type'              => 'simple',
                'price'             => 129.99,
                'weight'            => '3.2',
                'category'          => 'kitchen',
                'short_description' => 'WiFi-enabled programmable coffee maker with built-in grinder.',
                'description'       => '<p>Start your morning right with the Smart Coffee Maker. Features built-in conical burr grinder, programmable brew schedule via smartphone app, 12-cup capacity, and auto-shutoff. Compatible with Alexa and Google Home.</p>',
            ],
        ];
    }

    /**
     * Additional users per tenant with realistic names.
     */
    private function getUserData(Tenant $tenant): array
    {
        $domain = explode('.', $tenant->domain)[0] ?? $tenant->name;

        return [
            [
                'name'     => 'Sarah Johnson',
                'email'    => "sarah@{$domain}.com",
                'password' => 'Demo@1234',
            ],
            [
                'name'     => 'Michael Chen',
                'email'    => "michael@{$domain}.com",
                'password' => 'Demo@1234',
            ],
            [
                'name'     => 'Emily Rodriguez',
                'email'    => "emily@{$domain}.com",
                'password' => 'Demo@1234',
            ],
        ];
    }

    /**
     * Ensure the tenant's attribute family has attribute groups with attribute mappings.
     *
     * Schema: attribute_groups (id, code, is_user_defined, tenant_id)
     *         attribute_family_group_mappings (id, attribute_family_id, attribute_group_id, position)
     *         attribute_group_mappings (attribute_id, attribute_family_group_id, position)
     */
    private function ensureAttributeGroups(int $familyId, int $tenantId, string $locale, $now): void
    {
        $existingMappings = DB::table('attribute_family_group_mappings')
            ->where('attribute_family_id', $familyId)
            ->count();

        if ($existingMappings > 0) {
            return;
        }

        $groups = [
            ['code' => 'general',     'name' => 'General',     'position' => 1],
            ['code' => 'description', 'name' => 'Description', 'position' => 2],
            ['code' => 'meta',        'name' => 'Meta',        'position' => 3],
            ['code' => 'price',       'name' => 'Price',       'position' => 4],
            ['code' => 'shipping',    'name' => 'Shipping',    'position' => 5],
        ];

        // Map attribute codes to groups
        $attrGroupMap = [
            'general'     => ['sku', 'name', 'url_key', 'product_number', 'color', 'size', 'brand'],
            'description' => ['short_description', 'description'],
            'meta'        => ['meta_title', 'meta_keywords', 'meta_description'],
            'price'       => ['price', 'cost', 'tax_category_id'],
            'shipping'    => ['weight', 'length', 'width', 'height'],
        ];

        // Get all attributes for this tenant (or base attributes with tenant_id=NULL)
        $allAttributes = DB::table('attributes')
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->get()
            ->keyBy('code');

        foreach ($groups as $group) {
            // Find or create the attribute group
            $existingGroup = DB::table('attribute_groups')
                ->where('code', $group['code'])
                ->where('tenant_id', $tenantId)
                ->first();

            if ($existingGroup) {
                $groupId = $existingGroup->id;
            } else {
                $groupId = DB::table('attribute_groups')->insertGetId([
                    'code'            => $group['code'],
                    'is_user_defined' => 0,
                    'tenant_id'       => $tenantId,
                ]);

                DB::table('attribute_group_translations')->insert([
                    'attribute_group_id' => $groupId,
                    'locale'             => $locale,
                    'name'               => $group['name'],
                ]);
            }

            // Link group to family
            $familyGroupId = DB::table('attribute_family_group_mappings')->insertGetId([
                'attribute_family_id' => $familyId,
                'attribute_group_id'  => $groupId,
                'position'            => $group['position'],
            ]);

            // Map attributes to this family-group
            if (isset($attrGroupMap[$group['code']])) {
                $position = 1;

                foreach ($attrGroupMap[$group['code']] as $attrCode) {
                    $attr = $allAttributes->get($attrCode);

                    if ($attr) {
                        DB::table('attribute_group_mappings')->insert([
                            'attribute_id'              => $attr->id,
                            'attribute_family_group_id' => $familyGroupId,
                            'position'                  => $position++,
                        ]);
                    }
                }
            }
        }
    }
}
