# UnoPim Reference Catalogs — Demo Data Build Plan

This document is the single source of truth for the UnoPim demo data rebuild.
It is committed into the repo so future agents / contributors can resume work
without replaying the original planning conversation.

**Status**: Phase 0 in progress (`food_grocery`).
**Branch**: `feat/demo-data-reference-catalogs`.

---

## Vision

Ship the **most comprehensive open-source PIM demo dataset on the market**,
directly backing every industry vertical claimed on **unopim.com**. Every
reference catalog ships with a hand-authored `README.md` that teaches partners
how to model that domain correctly on day one — not after a costly re-model in
month three.

## Target state (all 20 families, all phases complete)

| Dimension | Target | Current (pre-build) | Vs Akeneo CE icecat_demo_dev |
|---|---|---|---|
| Product parents | 630 | 10 | +110% |
| Variant children | 475 | 0 | +138% |
| Total product rows | **1,105** | 10 | +121% |
| Attribute families | **20** | 1 | +17% |
| Attribute groups (unique) | ~45 | 5 | +275% |
| Attributes (unique) | ~420 | 18 | +180% |
| Attribute options | ~1,800 | 9 | +125% |
| Categories | ~271 (5 levels) | 1 | +146% |
| Channels | **4** | 1 | +33% |
| Locales | **8** | user-picks | +167% |
| Currencies | **5** | user-picks | +150% |
| Brand options | ~120 | 0 | +200% |
| Association types | 6 | 0 | +50% |
| Association rows | ~420 | 0 | +740% |
| Tax categories | 10 | 0 | unique |
| Industries covered | **8** | 0 | **8×** |
| Reference READMEs | **20** | 0 | **decisive** |

## The 8 unopim.com industries → 20 reference catalogs

| # | Family code | unopim.com industry | Customer parallel |
|---|---|---|---|
| 1 | `food_grocery` | Food | — |
| 2 | `food_beverages` | Food | — |
| 3 | `food_fresh_produce` | Food | — |
| 4 | `cpg_household` | CPG | — |
| 5 | `cpg_personal_care` | CPG | — |
| 6 | `fashion_apparel` | Fashion | — |
| 7 | `fashion_footwear` | Fashion | — |
| 8 | `fashion_accessories` | Fashion | — |
| 9 | `pharma_otc` | Pharmacy | — |
| 10 | `medical_devices` | Pharmacy | **Haddenham** |
| 11 | `pharma_supplements` | Pharmacy | — |
| 12 | `manufacturing_industrial` | Manufacturing | **Sawyer, CB Tech** |
| 13 | `manufacturing_tools_mro` | Manufacturing | — |
| 14 | `manufacturing_safety_ppe` | Manufacturing | — |
| 15 | `engineering_components` | Engineering | — |
| 16 | `engineering_lab_equipment` | Engineering | — |
| 17 | `energy_utility` | Energy & Utility | — |
| 18 | `energy_lighting` | Energy & Utility | — |
| 19 | `electronics_consumer` | Retail / E-commerce | — |
| 20 | `building_materials` | Retail | **LussoStone** |

## Channels (4)

| # | Channel | Locales | Currencies | Description style |
|---|---|---|---|---|
| 1 | `ecommerce` | all 8 | USD, EUR, GBP, CHF | Rich HTML (800–2500 chars) |
| 2 | `mobile_app` | en_US, en_GB, de_DE, fr_FR, es_ES | USD, EUR, GBP | Short plain (80–200 chars) |
| 3 | `print_catalogue` | en_US, de_DE, fr_FR | EUR | Print-safe plain (200–400 chars), catalogue pricing |
| 4 | `b2b_wholesale` | en_US, de_DE, zh_CN | USD, EUR, CNY | Technical spec focus, cost + MOQ + lead time |

## Locales (8, asymmetric coverage — intentional)

| Locale | Coverage | Rationale |
|---|---|---|
| `en_US` | 100% (source of truth) | Primary |
| `en_GB` | 100% (mechanical transform) | Retail UK |
| `de_DE` | 50% | Largest PIM market in Europe |
| `fr_FR` | 50% | Akeneo's home market parity |
| `es_ES` | 35% | Growing PIM market |
| `it_IT` | 25% | Fashion + food |
| `nl_NL` | 20% | Distrilink region |
| `pl_PL` | 15% | Eastern Europe |

Asymmetric coverage leaves ~500 products with at least one missing locale →
creates a realistic backlog for the Magic AI Translate feature to work on.

## Currencies (5)

USD · EUR · GBP · CHF · CNY

## Legal / clean-room posture

- **Brand names**: real brand names are used only as `brand` attribute option
  values. This is nominative fair use — the standard PIM-demo practice (Akeneo,
  Plytix, Salsify all do this). **No logos, no ® marks, no claims of affiliation.**
- **Product names**: always fictional `GS-*` prefix + generic descriptor
  (e.g. `"55-inch 4K OLED Smart TV — GlobalStore Pro Series"`). Never a real
  product's SKU / model number.
- **Descriptions**: hand-authored in `en_US` canonical locale in this repo.
  Translations for `de_DE` / `fr_FR` / `es_ES` / `it_IT` / `nl_NL` / `pl_PL` are
  hand-translated by the authoring agent (model is multilingual-capable).
- **Images**: SVG placeholders generated programmatically with name + dimensions
  baked in. Phase 8 can optionally upgrade to real CC0 photography from
  Pexels / Unsplash / Openverse — never AI-generated, never brand-specific.
- **No Icecat data**: we did not import, copy, or reference any data from
  Akeneo's `icecat_demo_dev` fixtures. We only looked at their CSV headers
  for structural inspiration.

## File layout

```
packages/Webkul/Installer/
├── src/
│   ├── Console/Commands/
│   │   └── Installer.php                       (modified — adds --demo-preset flag)
│   ├── Demo/
│   │   └── DemoDataProfile.php                 (NEW value object)
│   ├── Database/
│   │   ├── Seeders/
│   │   │   ├── DatabaseSeeder.php              (modified — passes profile)
│   │   │   └── Demo/
│   │   │       ├── FoodGroceryReferenceSeeder.php     (NEW — Phase 0)
│   │   │       ├── FoodBeveragesReferenceSeeder.php   (Phase 1)
│   │   │       └── ... (one per family)
│   │   └── Data/
│   │       ├── PLAN.md                         (this file)
│   │       ├── templates/
│   │       │   ├── food_grocery/
│   │       │   │   ├── README.md               (modelling guide)
│   │       │   │   ├── cheatsheet.md           (pattern-to-SKU index)
│   │       │   │   ├── attribute_groups.json
│   │       │   │   ├── attributes.json
│   │       │   │   ├── family.json
│   │       │   │   ├── categories.json
│   │       │   │   ├── brand_options.json
│   │       │   │   ├── products.json
│   │       │   │   ├── association_types.json
│   │       │   │   ├── associations.json
│   │       │   │   └── import_sample.csv
│   │       │   ├── food_beverages/  …          (Phase 1)
│   │       │   └── … (18 more families)
│   │       └── Generators/
│   │           └── SvgPlaceholderGenerator.php (NEW — generates product images)
│   └── Resources/assets/images/seeders/
│       └── products/
│           └── food_grocery/                    (SVG placeholders, 1 per product)
```

## Delivery phases

| Phase | Families | New rows | Cumulative | Days |
|---|---|---|---|---|
| **Phase 0** | food_grocery | 75 | 75 | 3.5 |
| Phase 1 | food_beverages, food_fresh_produce, cpg_household, cpg_personal_care | 215 | 290 | 5 |
| Phase 2 | fashion_apparel, fashion_footwear, fashion_accessories | 265 | 555 | 5 |
| Phase 3 | pharma_otc, medical_devices, pharma_supplements | 150 | 705 | 5 |
| Phase 4 | manufacturing_industrial, manufacturing_tools_mro, manufacturing_safety_ppe | 140 | 845 | 5 |
| Phase 5 | engineering_components, engineering_lab_equipment | 75 | 920 | 4 |
| Phase 6 | energy_utility, energy_lighting | 75 | 995 | 4 |
| Phase 7 | electronics_consumer, building_materials | 110 | 1,105 | 4 |
| Phase 8 | Polish — real JPEG images, final translations, history, webhooks | 0 | 1,105 | 2 |

Each phase = one PR, independently mergeable. You can stop after any phase and
still have a complete, useful demo.

## Installer flag design

```bash
# Interactive (default): prompts once for preset
php artisan unopim:install

# Non-interactive
php artisan unopim:install --skip-env-check --skip-admin-creation \
  --demo-preset=minimal          # 0 products (CI / production installs)
  --demo-preset=starter          # food_grocery only (~75 products)
  --demo-preset=medium            # 8 families (~555 products) — DEFAULT for eval
  --demo-preset=full              # all 20 families (~1105 products) — marketing
```

Presets resolve to:

| Preset | Families | Rows | Use case |
|---|---|---|---|
| minimal | 0 | 0 | CI, production installs |
| starter | 1 (food_grocery) | 75 | Quick demo |
| medium | 8 | ~555 | Evaluator default |
| full | 20 | ~1,105 | Marketing / full showcase |

## Phase 0 scope (this session / first PR)

**Must deliver**:
- [x] `feat/demo-data-reference-catalogs` branch
- [x] This `PLAN.md`
- [ ] Directory scaffolding for all 20 families (empty folders with placeholder README.md)
- [ ] `food_grocery/` fully authored:
  - [ ] README.md (modelling guide, ~400 lines)
  - [ ] attribute_groups.json (10 groups)
  - [ ] attributes.json (~70 attributes)
  - [ ] family.json
  - [ ] categories.json (~20 categories)
  - [ ] brand_options.json (~15 brands)
  - [ ] products.json (~20 flagship products, 4 locales)
  - [ ] association_types.json (6 types)
  - [ ] associations.json (~30 associations)
  - [ ] cheatsheet.md
  - [ ] import_sample.csv (10 rows)
- [ ] `DemoDataProfile` value object
- [ ] `Installer::handle()` with new `--demo-preset` flag + one-question prompt
- [ ] `FoodGroceryReferenceSeeder` (reads the JSON fixtures → DB)
- [ ] Wire into `DatabaseSeeder`
- [ ] `SvgPlaceholderGenerator` script + generated SVGs for the 20 products
- [ ] Pest tests: DemoDataProfile unit, FoodGroceryReferenceSeeder integration
- [ ] Pint pass
- [ ] Commit on `feat/demo-data-reference-catalogs`

**Defer to Phase 1+**:
- The other 19 families (authored one phase at a time, ~5 days each)
- 8-locale coverage (Phase 0 ships 4 locales; later phases scale up)
- Real JPEG images (Phase 8 polish)
- Job history seeding (Phase 8)
- Webhook samples (Phase 8)

---

## Contributor checklist per new family

When authoring a new family reference catalog, every one of these must be present:

1. `README.md` — Modelling guide explaining attribute groups, variant strategy, association strategy, completeness rules, channel scoping, 3-5 common mistakes
2. `attribute_groups.json` — Unique groups for this family
3. `attributes.json` — All attributes (shared baseline + family-specific)
4. `family.json` — Family definition + group mappings
5. `categories.json` — Category subtree for this family's products
6. `brand_options.json` — Brand options used by this family
7. `products.json` — Products, each flagged with `demo_pattern` telling the reader what they illustrate
8. `association_types.json` — Any new association types
9. `associations.json` — Seeded associations between products
10. `cheatsheet.md` — "To see X pattern, open SKU Y"
11. `import_sample.csv` — 10-row ready-to-hand-to-client starter CSV
12. SVG placeholder image per product
13. Seeder class at `Database/Seeders/Demo/{Family}ReferenceSeeder.php`
14. Pest test verifying seeder inserts expected row counts
15. Add to `DemoDataProfile::shouldSeedFamily()` and `DatabaseSeeder::run()`

## Out-of-scope (will not be addressed by this project)

- Real product photography beyond CC0 stock placeholders
- Hosted demo environment at demo.unopim.com (infra work)
- Schema changes (e.g. product_models intermediate abstraction — that's a UnoPim core change)
- First-class user groups (UnoPim schema doesn't support them yet)
- Reference entities for brands (UnoPim uses select options, not linked records)
