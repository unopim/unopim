<?php

$identity = ['sku', 'name', 'url_key', 'brand', 'product_number', 'ean'];
$copy = ['short_description', 'description', 'highlights'];
$seo = ['meta_title', 'meta_keywords', 'meta_description'];
$pricing = ['price', 'cost'];
$media = ['image', 'gallery', 'spec_sheet'];
$shipping = ['weight', 'length', 'width', 'height', 'country_of_origin', 'hs_code', 'lead_time_days', 'warranty_months', 'is_freeshipping', 'available_from'];
$green = ['certifications', 'recycled_content_percent', 'is_organic', 'energy_class'];
$promo = ['style', 'season', 'is_featured', 'release_date'];

return [
    'families' => [
        [
            'code'   => 'audio_electronics',
            'labels' => [
                'en_US' => 'Audio & Electronics',
                'de_DE' => 'Audio & Elektronik',
                'fr_FR' => 'Audio et électronique',
            ],
            'groups' => [
                'general'          => [...$identity, 'color'],
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => $media,
                'technical'        => ['material', 'features', 'power_output', 'battery_life_hours', 'capacity'],
                'marketing'        => $promo,
                'logistics'        => $shipping,
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'features', 'power_output', 'weight'],
        ],
        [
            'code'   => 'apparel',
            'labels' => [
                'en_US' => 'Apparel',
                'de_DE' => 'Bekleidung',
                'fr_FR' => 'Prêt-à-porter',
            ],
            'groups' => [
                'general'          => [...$identity, 'color', 'size'],
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => $media,
                'technical'        => ['material', 'features', 'care_instructions'],
                'marketing'        => $promo,
                'logistics'        => $shipping,
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'material', 'care_instructions', 'color'],
        ],
        [
            'code'   => 'home_kitchen',
            'labels' => [
                'en_US' => 'Home & Kitchen',
                'de_DE' => 'Haus & Küche',
                'fr_FR' => 'Maison et cuisine',
            ],
            'groups' => [
                'general'          => [...$identity, 'color'],
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => $media,
                'technical'        => ['material', 'capacity', 'features', 'power_output', 'care_instructions', 'finish'],
                'marketing'        => [...$promo, 'room'],
                'logistics'        => [...$shipping, 'assembly_required'],
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'material', 'capacity'],
        ],
        [
            'code'   => 'outdoor',
            'labels' => [
                'en_US' => 'Outdoor & Travel',
                'de_DE' => 'Outdoor & Reisen',
                'fr_FR' => 'Outdoor et voyage',
            ],
            'groups' => [
                'general'          => [...$identity, 'color', 'size'],
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => $media,
                'technical'        => ['material', 'features', 'capacity', 'care_instructions'],
                'marketing'        => $promo,
                'logistics'        => $shipping,
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'material', 'features', 'weight'],
        ],
        [
            'code'   => 'beauty_personal_care',
            'labels' => [
                'en_US' => 'Beauty & Personal Care',
                'de_DE' => 'Beauty & Körperpflege',
                'fr_FR' => 'Beauté et soins',
            ],
            'groups' => [
                'general'          => $identity,
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => $media,
                'technical'        => ['skin_type', 'ingredients', 'fragrance_free', 'capacity', 'features'],
                'marketing'        => $promo,
                'logistics'        => [...$shipping, 'shelf_life_months', 'best_before', 'storage_instructions'],
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'ingredients', 'skin_type', 'capacity'],
        ],
        [
            'code'   => 'food_grocery',
            'labels' => [
                'en_US' => 'Food & Grocery',
                'de_DE' => 'Lebensmittel',
                'fr_FR' => 'Épicerie',
            ],
            'groups' => [
                'general'          => $identity,
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => $media,
                'technical'        => ['allergens', 'energy_kcal_100g', 'capacity', 'ingredients'],
                'marketing'        => $promo,
                'logistics'        => [...$shipping, 'best_before', 'shelf_life_months', 'storage_instructions'],
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'allergens', 'energy_kcal_100g', 'best_before'],
        ],
        [
            'code'   => 'sports_fitness',
            'labels' => [
                'en_US' => 'Sports & Fitness',
                'de_DE' => 'Sport & Fitness',
                'fr_FR' => 'Sport et fitness',
            ],
            'groups' => [
                'general'          => [...$identity, 'color', 'size'],
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => $media,
                'technical'        => ['material', 'features', 'resistance_level', 'max_user_weight', 'care_instructions'],
                'marketing'        => $promo,
                'logistics'        => [...$shipping, 'assembly_required'],
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'material', 'max_user_weight'],
        ],
        [
            'code'   => 'furniture_lighting',
            'labels' => [
                'en_US' => 'Furniture & Lighting',
                'de_DE' => 'Möbel & Leuchten',
                'fr_FR' => 'Mobilier et luminaires',
            ],
            'groups' => [
                'general'          => [...$identity, 'color'],
                'description'      => $copy,
                'meta_description' => $seo,
                'price'            => $pricing,
                'media'            => [...$media, 'assembly_manual'],
                'technical'        => ['material', 'finish', 'seat_height', 'power_output', 'features', 'care_instructions'],
                'marketing'        => [...$promo, 'room'],
                'logistics'        => [...$shipping, 'assembly_required'],
                'sustainability'   => $green,
            ],
            'completeness' => ['name', 'short_description', 'description', 'image', 'price', 'material', 'finish', 'length', 'width', 'height'],
        ],
    ],

    'completeness_channels' => ['default', 'ecommerce'],
];
