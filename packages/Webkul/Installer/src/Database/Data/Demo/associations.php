<?php

return [
    'types' => [
        [
            'code'   => 'related_products',
            'labels' => ['en_US' => 'Related products', 'de_DE' => 'Ähnliche Produkte', 'fr_FR' => 'Produits associés'],
        ],
        [
            'code'   => 'up_sells',
            'labels' => ['en_US' => 'Up-sells', 'de_DE' => 'Up-Sells', 'fr_FR' => 'Montées en gamme'],
        ],
        [
            'code'   => 'cross_sells',
            'labels' => ['en_US' => 'Cross-sells', 'de_DE' => 'Cross-Sells', 'fr_FR' => 'Ventes croisées'],
        ],
        [
            'code'            => 'spare_parts',
            'is_user_defined' => true,
            'labels'          => ['en_US' => 'Spare parts', 'de_DE' => 'Ersatzteile', 'fr_FR' => 'Pièces détachées'],
            'fields'          => [
                [
                    'code'   => 'part_role',
                    'type'   => 'text',
                    'labels' => ['en_US' => 'Part role', 'de_DE' => 'Teilerolle', 'fr_FR' => 'Rôle de la pièce'],
                ],
                [
                    'code'        => 'is_covered_by_warranty',
                    'type'        => 'boolean',
                    'labels'      => ['en_US' => 'Covered by warranty', 'de_DE' => 'Von der Garantie abgedeckt', 'fr_FR' => 'Couvert par la garantie'],
                ],
                [
                    'code'   => 'service_interval',
                    'type'   => 'text',
                    'labels' => ['en_US' => 'Service interval', 'de_DE' => 'Wartungsintervall', 'fr_FR' => 'Intervalle d’entretien'],
                ],
                [
                    'code'             => 'fitting_note',
                    'type'             => 'text',
                    'value_per_locale' => true,
                    'labels'           => ['en_US' => 'Fitting note', 'de_DE' => 'Einbauhinweis', 'fr_FR' => 'Note de montage'],
                ],
            ],
        ],
        [
            'code'            => 'bundle_items',
            'is_user_defined' => true,
            'labels'          => ['en_US' => 'Bundle items', 'de_DE' => 'Bundle-Artikel', 'fr_FR' => 'Articles du lot'],
            'fields'          => [
                [
                    'code'   => 'quantity',
                    'type'   => 'text',
                    'labels' => ['en_US' => 'Quantity', 'de_DE' => 'Menge', 'fr_FR' => 'Quantité'],
                ],
                [
                    'code'   => 'bundle_from',
                    'type'   => 'text',
                    'labels' => ['en_US' => 'In bundle from', 'de_DE' => 'Im Bundle ab', 'fr_FR' => 'Dans le lot à partir du'],
                ],
                [
                    'code'   => 'is_substitutable',
                    'type'   => 'boolean',
                    'labels' => ['en_US' => 'Substitutable', 'de_DE' => 'Austauschbar', 'fr_FR' => 'Substituable'],
                ],
            ],
        ],
    ],

    'links' => [
        'aurex-halo-over-ear' => [
            'related_products' => ['aurex-halo-studio', 'aurex-nimbus-tws', 'aurex-orbit-smartwatch'],
            'up_sells'         => ['aurex-vertex-shelf-speaker', 'aurex-echo-soundbar'],
            'cross_sells'      => ['aurex-halo-carry-case', 'aurex-usb-c-cable-2m', 'aurex-desk-stand'],
            'spare_parts'      => [
                ['sku' => 'aurex-halo-ear-pads', 'data' => ['part_role' => 'Wear part', 'is_covered_by_warranty' => false, 'service_interval' => '12 months'], 'locale_data' => [
                    'en_US' => ['fitting_note' => 'Twist the old pad anticlockwise to release, align the new pad with the driver notch and twist until it clicks.'],
                    'de_DE' => ['fitting_note' => 'Altes Polster gegen den Uhrzeigersinn lösen, neues an der Treibernut ausrichten und bis zum Klicken drehen.'],
                ]],
                ['sku' => 'aurex-halo-cable-spare', 'data' => ['part_role' => 'Accessory', 'is_covered_by_warranty' => true, 'service_interval' => '—']],
            ],
        ],
        'aurex-nimbus-tws' => [
            'related_products' => ['aurex-nimbus-air', 'aurex-halo-over-ear'],
            'cross_sells'      => ['aurex-usb-c-cable-2m', 'aurex-usb-c-adapter'],
        ],
        'aurex-echo-soundbar' => [
            'related_products' => ['aurex-vertex-shelf-speaker'],
            'up_sells'         => ['aurex-vertex-subwoofer'],
            'bundle_items'     => [
                ['sku' => 'aurex-echo-rear-kit', 'data' => ['quantity' => '1', 'bundle_from' => '2026-05-19', 'is_substitutable' => false]],
                ['sku' => 'aurex-vertex-subwoofer', 'data' => ['quantity' => '1', 'bundle_from' => '2026-05-19', 'is_substitutable' => true]],
            ],
        ],
        'aurex-orbit-smartwatch' => [
            'related_products' => ['aurex-orbit-active', 'aurex-nimbus-tws'],
            'cross_sells'      => ['aurex-usb-c-cable-2m'],
        ],
        'aurex-pulse-portable-speaker' => [
            'related_products' => ['aurex-pulse-mini'],
            'up_sells'         => ['aurex-vertex-shelf-speaker'],
            'cross_sells'      => ['nordvale-fjell-28-daypack'],
        ],

        'verano-terra-merino-crew' => [
            'related_products' => ['verano-orkney-cardigan', 'verano-coastline-tee'],
            'up_sells'         => ['verano-harbour-overshirt'],
            'cross_sells'      => ['verano-daily-scarf', 'verano-alpine-beanie', 'verano-workday-chino'],
        ],
        'verano-atlas-rain-jacket' => [
            'related_products' => ['nordvale-storm-shell', 'verano-marine-quilted-jacket'],
            'up_sells'         => ['nordvale-storm-shell'],
            'cross_sells'      => ['verano-liner-gloves', 'verano-packable-cap'],
            'spare_parts'      => [
                ['sku' => 'nordvale-repair-kit', 'data' => ['part_role' => 'Consumable', 'is_covered_by_warranty' => false, 'service_interval' => 'As needed'], 'locale_data' => [
                    'en_US' => ['fitting_note' => 'Clean the area with alcohol, round the patch corners and press for 30 seconds. Cures fully in 24 hours.'],
                ]],
            ],
        ],
        'verano-summit-fleece' => [
            'related_products' => ['verano-terra-merino-crew', 'nordvale-storm-shell'],
            'cross_sells'      => ['verano-trail-socks-pack', 'verano-liner-gloves'],
        ],
        'verano-workday-chino' => [
            'related_products' => ['verano-coastline-tee', 'verano-harbour-overshirt'],
            'cross_sells'      => ['verano-canvas-belt', 'verano-trail-socks-pack'],
        ],

        'casaluna-terra-frying-pan' => [
            'related_products' => ['casaluna-terra-saucepan-set', 'casaluna-forma-dutch-oven'],
            'up_sells'         => ['casaluna-terra-saucepan-set'],
            'cross_sells'      => ['casaluna-oak-chopping-board', 'casaluna-chef-knife', 'casaluna-linen-tea-towels'],
            'spare_parts'      => [
                ['sku' => 'casaluna-terra-pan-lid', 'data' => ['part_role' => 'Accessory', 'is_covered_by_warranty' => true, 'service_interval' => '—'], 'locale_data' => [
                    'en_US' => ['fitting_note' => 'Fits the 28 cm pan only. Check the rim diameter before ordering.'],
                    'fr_FR' => ['fitting_note' => 'Compatible uniquement avec la poêle de 28 cm. Vérifier le diamètre du bord avant de commander.'],
                ]],
            ],
        ],
        'casaluna-brew-pour-over' => [
            'related_products' => ['casaluna-brew-grinder'],
            'up_sells'         => ['casaluna-brew-grinder'],
            'cross_sells'      => ['terrafina-single-origin-coffee', 'casaluna-lume-carafe'],
            'spare_parts'      => [
                ['sku' => 'casaluna-brew-filters', 'data' => ['part_role' => 'Consumable', 'is_covered_by_warranty' => false, 'service_interval' => 'Every brew']],
            ],
            'bundle_items' => [
                ['sku' => 'casaluna-brew-grinder', 'data' => ['quantity' => '1', 'bundle_from' => '2026-02-10', 'is_substitutable' => false]],
                ['sku' => 'terrafina-single-origin-coffee', 'data' => ['quantity' => '2', 'bundle_from' => '2026-03-15', 'is_substitutable' => true]],
            ],
        ],
        'casaluna-lume-glass-tumbler' => [
            'related_products' => ['casaluna-lume-carafe'],
            'cross_sells'      => ['terrafina-sparkling-water-case'],
        ],
        'casaluna-chef-knife' => [
            'related_products' => ['casaluna-oak-chopping-board'],
            'cross_sells'      => ['casaluna-linen-tea-towels'],
        ],

        'nordvale-fjell-28-daypack' => [
            'related_products' => ['nordvale-fjell-45-travel-pack'],
            'up_sells'         => ['nordvale-fjell-45-travel-pack'],
            'cross_sells'      => ['nordvale-vann-bottle', 'nordvale-dry-bag-set', 'nordvale-headlamp'],
            'spare_parts'      => [
                ['sku' => 'nordvale-repair-kit', 'data' => ['part_role' => 'Wear part', 'is_covered_by_warranty' => false, 'service_interval' => 'As needed']],
            ],
        ],
        'nordvale-skare-2p-tent' => [
            'related_products' => ['nordvale-lumen-sleeping-bag', 'nordvale-camp-stove'],
            'cross_sells'      => ['nordvale-trail-poles', 'nordvale-headlamp'],
            'spare_parts'      => [
                ['sku' => 'nordvale-repair-kit', 'data' => ['part_role' => 'Wear part', 'is_covered_by_warranty' => false, 'service_interval' => 'Per season'], 'locale_data' => [
                    'en_US' => ['fitting_note' => 'The pole splint slides over a broken section and clamps with the supplied tape; replace the section at home afterwards.'],
                ]],
            ],
            'bundle_items' => [
                ['sku' => 'nordvale-lumen-sleeping-bag', 'data' => ['quantity' => '2', 'bundle_from' => '2026-04-14', 'is_substitutable' => true]],
                ['sku' => 'nordvale-camp-stove', 'data' => ['quantity' => '1', 'bundle_from' => '2026-04-14', 'is_substitutable' => false]],
            ],
        ],
        'nordvale-vann-bottle' => [
            'related_products' => ['nordvale-vann-filter-cap'],
            'cross_sells'      => ['nordvale-fjell-28-daypack'],
            'spare_parts'      => [
                ['sku' => 'nordvale-vann-filter-cap', 'data' => ['part_role' => 'Accessory', 'is_covered_by_warranty' => true, 'service_interval' => '1000 litres']],
            ],
        ],
        'nordvale-storm-shell' => [
            'related_products' => ['verano-atlas-rain-jacket', 'verano-summit-fleece'],
            'cross_sells'      => ['nordvale-fjell-28-daypack', 'verano-liner-gloves'],
        ],

        'lumea-clarify-gel-cleanser' => [
            'related_products' => ['lumea-barrier-moisturiser', 'lumea-niacinamide-serum'],
            'up_sells'         => ['lumea-vitamin-c-serum'],
            'cross_sells'      => ['lumea-mineral-spf50'],
            'spare_parts'      => [
                ['sku' => 'lumea-clarify-refill', 'data' => ['part_role' => 'Consumable', 'is_covered_by_warranty' => false, 'service_interval' => 'Every 2 bottles'], 'locale_data' => [
                    'en_US' => ['fitting_note' => 'Rinse the bottle with warm water before refilling; do not top up over old product.'],
                    'de_DE' => ['fitting_note' => 'Die Flasche vor dem Nachfüllen mit warmem Wasser ausspülen, nicht über Restprodukt auffüllen.'],
                ]],
            ],
        ],
        'lumea-barrier-moisturiser' => [
            'related_products' => ['lumea-niacinamide-serum', 'lumea-body-lotion'],
            'cross_sells'      => ['lumea-mineral-spf50'],
            'bundle_items'     => [
                ['sku' => 'lumea-clarify-gel-cleanser', 'data' => ['quantity' => '1', 'bundle_from' => '2026-01-15', 'is_substitutable' => false]],
                ['sku' => 'lumea-niacinamide-serum', 'data' => ['quantity' => '1', 'bundle_from' => '2026-03-24', 'is_substitutable' => true]],
            ],
        ],
        'lumea-safety-razor' => [
            'related_products' => ['lumea-body-lotion'],
            'spare_parts'      => [
                ['sku' => 'lumea-razor-blades', 'data' => ['part_role' => 'Consumable', 'is_covered_by_warranty' => false, 'service_interval' => 'Every 5–7 shaves']],
            ],
        ],

        'terrafina-single-origin-coffee' => [
            'related_products' => ['terrafina-espresso-blend', 'terrafina-cold-brew-concentrate'],
            'up_sells'         => ['terrafina-espresso-blend'],
            'cross_sells'      => ['casaluna-brew-pour-over', 'casaluna-brew-grinder', 'casaluna-brew-filters'],
        ],
        'terrafina-olive-oil-extra-virgin' => [
            'related_products' => ['terrafina-balsamic-vinegar'],
            'cross_sells'      => ['terrafina-bronze-spaghetti', 'terrafina-almond-crackers'],
            'bundle_items'     => [
                ['sku' => 'terrafina-balsamic-vinegar', 'data' => ['quantity' => '1', 'bundle_from' => '2025-11-28', 'is_substitutable' => false]],
                ['sku' => 'terrafina-bronze-spaghetti', 'data' => ['quantity' => '2', 'bundle_from' => '2025-11-28', 'is_substitutable' => true]],
            ],
        ],
        'terrafina-bronze-spaghetti' => [
            'related_products' => ['terrafina-arborio-rice'],
            'cross_sells'      => ['terrafina-olive-oil-extra-virgin'],
        ],

        'kinetiq-forge-adjustable-dumbbell' => [
            'related_products' => ['kinetiq-forge-kettlebell', 'kinetiq-forge-barbell'],
            'up_sells'         => ['kinetiq-forge-barbell'],
            'cross_sells'      => ['kinetiq-training-gloves', 'kinetiq-gym-towel', 'kinetiq-flow-yoga-mat'],
            'spare_parts'      => [
                ['sku' => 'kinetiq-forge-selector-spare', 'data' => ['part_role' => 'Wear part', 'is_covered_by_warranty' => true, 'service_interval' => '5 years'], 'locale_data' => [
                    'en_US' => ['fitting_note' => 'Remove the four M5 bolts on the cradle side, lift out the dial assembly and reverse. Torque to 4 Nm.'],
                    'de_DE' => ['fitting_note' => 'Die vier M5-Schrauben an der Ablageseite lösen, die Wähleinheit herausnehmen und in umgekehrter Reihenfolge montieren. Mit 4 Nm anziehen.'],
                ]],
            ],
        ],
        'kinetiq-pulse-rowing-machine' => [
            'related_products' => ['kinetiq-pulse-exercise-bike'],
            'cross_sells'      => ['kinetiq-gym-towel', 'kinetiq-recovery-roller'],
        ],
        'kinetiq-flow-yoga-mat' => [
            'related_products' => ['kinetiq-flow-resistance-bands', 'kinetiq-recovery-roller'],
            'cross_sells'      => ['kinetiq-gym-towel'],
            'bundle_items'     => [
                ['sku' => 'kinetiq-flow-resistance-bands', 'data' => ['quantity' => '1', 'bundle_from' => '2026-04-21', 'is_substitutable' => false]],
                ['sku' => 'kinetiq-recovery-roller', 'data' => ['quantity' => '1', 'bundle_from' => '2026-04-21', 'is_substitutable' => true]],
            ],
        ],

        'halden-linea-desk' => [
            'related_products' => ['halden-linea-dining-table', 'halden-grid-shelving'],
            'up_sells'         => ['halden-arc-office-chair'],
            'cross_sells'      => ['halden-cable-tray', 'halden-lumen-table-lamp', 'aurex-desk-stand'],
            'spare_parts'      => [
                ['sku' => 'halden-cable-tray', 'data' => ['part_role' => 'Accessory', 'is_covered_by_warranty' => false, 'service_interval' => '—'], 'locale_data' => [
                    'en_US' => ['fitting_note' => 'Clamps fit tops from 25 to 32 mm. No drilling required.'],
                    'fr_FR' => ['fitting_note' => 'Les pinces conviennent aux plateaux de 25 à 32 mm. Aucun perçage nécessaire.'],
                ]],
            ],
        ],
        'halden-arc-office-chair' => [
            'related_products' => ['halden-arc-stool'],
            'cross_sells'      => ['halden-linea-desk', 'halden-lumen-floor-lamp'],
        ],
        'halden-grid-shelving' => [
            'related_products' => ['halden-linea-desk'],
            'up_sells'         => ['halden-grid-extension'],
            'cross_sells'      => ['casaluna-storage-basket', 'halden-lumen-table-lamp'],
            'bundle_items'     => [
                ['sku' => 'halden-grid-extension', 'data' => ['quantity' => '1', 'bundle_from' => '2025-08-12', 'is_substitutable' => false]],
                ['sku' => 'casaluna-storage-basket', 'data' => ['quantity' => '3', 'bundle_from' => '2025-08-12', 'is_substitutable' => true]],
            ],
        ],
        'halden-lumen-floor-lamp' => [
            'related_products' => ['halden-lumen-table-lamp', 'halden-lumen-pendant'],
            'cross_sells'      => ['halden-arc-office-chair'],
        ],
    ],
];
