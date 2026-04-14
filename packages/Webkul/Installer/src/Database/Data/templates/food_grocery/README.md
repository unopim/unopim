# `food_grocery` — FMCG Reference Catalog for UnoPim

> **If you clicked the "Food Industry" page on unopim.com, this is the reference
> implementation for your domain.** This README explains *why* the catalog is
> modelled the way it is, so you can copy it and adapt to your own products
> without learning PIM modelling through painful retries.

## TL;DR — the 7 things that matter

1. **Never combine "a single bottle" and "a 12-pack of the same bottle" as
   one product with a quantity field.** They are different SKUs with different
   GTINs, different cost structures, and different warehouse logic. Link them
   via a `case_contains` association.
2. **Never use pack size as a variant axis.** A 250 ml and 1 L of the same
   juice serve different occasions and are priced per-ml differently. They
   are separate products linked via `pack_size_variant` association.
3. **Flavours, scents and shades ARE variant axes.** A yogurt line in
   Strawberry / Blueberry / Vanilla shares its description, nutrition table,
   allergens and brand — it only differs on the flavour attribute and the
   flavour-level EAN. Use a single configurable parent with `flavour` as
   the super-attribute.
4. **Mark `allergens` and `ingredients_list` as `is_required`.** The
   completeness score on the EU Ecommerce channel then literally means
   *"legal to sell in the EU under regulation 1169/2011"*. This transforms
   the completeness dashboard from a vanity metric into a compliance gate.
5. **Put the canonical description on one channel; let `mobile_app` and
   `print_catalogue` override with shorter copy per product.** Channel-scoped
   descriptions are PIM's single biggest value proposition for marketing.
6. **Nutrition facts are per 100 g (or per 100 ml for liquids) — always.**
   EU 1169/2011 mandates this. Per-serving figures are secondary. Store both.
7. **Ship `image_front` and `image_back` as separate required image attributes.**
   EU ecommerce regulation effectively requires that shoppers can see both
   the front-of-pack (for marketing) and the back-of-pack (for ingredients /
   nutrition) before purchase.

## Why this family exists

FMCG (Fast-Moving Consumer Goods) is the most regulated product domain PIM
tools handle. It touches seven different compliance surfaces at once:

- **EU Regulation 1169/2011** — mandatory food information for consumers
  (ingredients, allergens, nutrition per 100 g, net weight, country of origin,
  storage)
- **GS1 / GTIN** — barcodes at three levels: consumer unit (EAN-13),
  case/outer (ITF-14), pallet (SSCC)
- **Customs classification** — HS codes / commodity codes for cross-border
  trade
- **Allergen law** — the EU-14 mandatory declaration list
- **Organic certification** — EU bio, USDA Organic, Bio Suisse, JAS
- **Religious certification** — Halal, Kosher
- **Dietary claims law** — "low fat" / "no added sugar" / "high protein" are
  regulated claims with specific thresholds

Getting any of these wrong means the product is not sellable in a market.
So the `food_grocery` reference catalog is also **the hardest test of your
PIM modelling**. If you can model FMCG correctly, you can model anything.

---

## Attribute groups (10)

We deliberately split attributes into 10 groups rather than one flat list.
Two reasons:

1. **Editor onboarding** — a brand manager filling in the marketing description
   should not scroll past 80 regulatory fields to get to their textarea. Groups
   map to roles: *Marketing* edits `product_info` + `media` + `seo`, *Compliance*
   edits `ingredients_allergens` + `nutrition` + `shelf_life_storage`,
   *Logistics* edits `packaging` + `commercial`, and so on.
2. **Completeness scoring per role** — UnoPim can weight completeness per
   channel. We make `ingredients_allergens` and `nutrition` required on the
   EU Ecommerce channel but not on the B2B Wholesale channel, so B2B products
   can be "complete" for internal ordering before the compliance team fills in
   the labels.

### Group 1 — `identifiers`
Every FMCG product is identified by **at least three barcodes**, not one.

| Attribute | Type | Scope | Required | Why |
|---|---|---|---|---|
| `sku` | text (unique) | global | ✅ | Internal catalogue identifier |
| `gtin_13` | text (13 char, numeric, unique) | global | ✅ | Consumer-unit barcode, scanned at POS |
| `gtin_14` | text (14 char, numeric, unique) | global | — | Case / outer barcode, scanned in warehouse |
| `manufacturer_part_number` | text | global | — | Supplier's internal code |
| `hs_commodity_code` | text | global | — | Customs classification |

**Common mistake**: using a single `barcode` text field for everything. That
breaks warehouse scanning because you lose the ability to distinguish a case
scan from a consumer-unit scan.

### Group 2 — `product_info` (channel + locale scoped)
The marketing surface.

| Attribute | Type | Scope | Required | Why |
|---|---|---|---|---|
| `name` | text | locale + channel | ✅ | Different channels want different name lengths |
| `brand` | select (filterable) | global | ✅ | Brand is global — same brand across locales |
| `sub_brand` | select | global | — | e.g. "Coke" → "Coke Zero" |
| `marketing_description` | textarea WYSIWYG | locale + channel | ✅ | Channel-specific length is the PIM value prop |
| `short_description` | textarea plain | locale + channel | ✅ | Used by mobile app and listing grids |
| `product_claims` | multiselect (filterable) | locale | — | "no added sugar", "low fat" — regulated per market |
| `tagline` | text | locale + channel | — | Marketing one-liner |

**Why `brand` is global but `name` is locale + channel scoped**: a brand
name rarely changes between channels ("Coca-Cola" is always "Coca-Cola"),
but the product name on the shelf card (`print_catalogue`) may be longer
than the mobile-app tap target.

### Group 3 — `packaging`
The logistics-team group. Second-most-botched group in PIM implementations.

| Attribute | Type | Required | Notes |
|---|---|---|---|
| `pack_type` | select | ✅ | bottle / can / pouch / jar / carton / box / tube / sachet / tetra_pak |
| `pack_material` | multiselect | ✅ | glass / PET / HDPE / aluminium / paper / tetra_pak / composite |
| `net_weight_g` | decimal | ✅ | **EU law requires this on the label** |
| `net_volume_ml` | decimal | conditional (liquids) | Liquids only |
| `gross_weight_g` | decimal | — | Includes packaging |
| `pack_dimensions_mm` | text (`LxWxH`) | — | Used for shelf planning |
| `pack_count` | integer | — | 6-pack of yogurts = 6 |
| `case_quantity` | integer | — | Consumer units per case |
| `pallet_quantity` | integer | — | Cases per pallet |
| `recyclable` | boolean | — | Marketing filter |
| `returnable_deposit_amount` | price | — | DE Pfand, SE Pant, NL Statiegeld |

### Group 4 — `nutrition`
**Required conditional on `is_food = true`**. Everything per 100 g (per 100 ml
for liquids) to satisfy EU 1169.

`energy_kcal_per_100g` · `energy_kj_per_100g` · `fat_g_per_100g` · `saturated_fat_g_per_100g` · `carbs_g_per_100g` · `sugar_g_per_100g` · `fibre_g_per_100g` · `protein_g_per_100g` · `salt_g_per_100g`

Plus serving-size helpers:

`serving_size_g` · `servings_per_pack` · `energy_kcal_per_serving`

### Group 5 — `ingredients_allergens`
The regulatory gate. All fields in this group should be on the completeness
scoring rule for the EU Ecommerce channel.

| Attribute | Type | Required | Notes |
|---|---|---|---|
| `ingredients_list` | textarea (plain) | ✅ food (per locale) | EU 1169 exact declaration — localised per country |
| `allergens_contains` | multiselect | ✅ food | EU 14: milk, eggs, gluten_cereals, peanuts, tree_nuts, soy, fish, shellfish, sesame, celery, mustard, lupin, molluscs, sulphites |
| `allergens_traces` | multiselect | — | "May contain traces of…" — same list |
| `is_organic` | boolean | — | Drives filter chip |
| `organic_certification` | select | conditional on `is_organic=true` | EU_bio / USDA_organic / Bio_Suisse / JAS |
| `is_halal` | boolean | — | Religious certification |
| `is_kosher` | boolean | — | Religious certification |
| `is_vegan` | boolean | — | Dietary filter |
| `is_vegetarian` | boolean | — | Dietary filter |
| `is_gluten_free` | boolean | — | Accessibility filter |
| `is_lactose_free` | boolean | — | Accessibility filter |

### Group 6 — `shelf_life_storage`

| Attribute | Type | Notes |
|---|---|---|
| `shelf_life_days` | integer | Number of days from production |
| `storage_temperature` | select | ambient / chilled_2_8c / frozen_minus_18c / dry |
| `storage_instructions` | textarea | Localised per country |
| `once_opened_consume_within_days` | integer | For products that expire after opening |
| `preparation_instructions` | textarea | Localised cooking / prep instructions |

### Group 7 — `compliance_origin`

| Attribute | Type | Notes |
|---|---|---|
| `country_of_origin` | select | ISO-3166 |
| `manufactured_in` | select | May differ from country_of_origin |
| `certifications` | multiselect | RSPO / Fair_Trade / Rainforest_Alliance / MSC / ASC / FSC |
| `health_warnings` | textarea (per locale) | Alcohol, caffeine, pregnancy warnings |
| `age_restriction` | select | none / 16_plus / 18_plus |
| `alcohol_abv_percent` | decimal | Conditional |

### Group 8 — `commercial`

| Attribute | Type | Notes |
|---|---|---|
| `msrp` | price (per currency) | Manufacturer suggested retail |
| `cost` | price (per currency) | Wholesale cost — B2B channel |
| `minimum_order_quantity_cases` | integer | B2B ordering |
| `lead_time_days` | integer | B2B ordering |
| `supplier_sku` | text | Supplier's reference |
| `is_promotional` | boolean | Drives promo filter + chip |
| `promotional_start_date` | date | — |
| `promotional_end_date` | date | — |

### Group 9 — `media`
EU 2019/1020 effectively requires front + back pack imagery for ecommerce.

| Attribute | Type | Required | Notes |
|---|---|---|---|
| `image_front` | image | ✅ | Front-of-pack — for marketing and listings |
| `image_back` | image | ✅ | Back-of-pack — for ingredients + nutrition visibility |
| `image_lifestyle` | gallery | — | Marketing lifestyle shots |
| `nutritional_label_pdf` | file | — | Downloadable PDF of the full label |
| `product_datasheet_pdf` | file | — | B2B downloadable spec sheet |

### Group 10 — `seo`
Standard per locale + channel.

| Attribute | Type | Scope |
|---|---|---|
| `meta_title` | text | locale + channel |
| `meta_description` | textarea plain | locale + channel |
| `meta_keywords` | text | locale + channel |
| `url_key` | text | locale + channel |

---

## The 5 common FMCG modelling mistakes — with the right answer

### Mistake 1 — "Pack size as a variant"

**Setup**: the client has 250 ml, 500 ml, and 1 L bottles of the same juice.
Their instinct is to make them variants of one configurable parent with
`net_volume_ml` as the super-attribute.

**Why it's wrong**:
- Different EANs, so POS can't distinguish them
- Different cost-per-ml (economies of scale)
- Different shelf placements (250 ml in the grab-and-go fridge, 1 L on the
  main aisle)
- Different promotional pricing (2-for-1 on 250 ml but not 1 L)
- Different supply chains
- They are **NOT interchangeable** in the customer's mind — a commuter buys
  250 ml to drink now, a family buys 1 L to refrigerate

**Right answer**: three separate parent products. Link them via a
`pack_size_variant` association. The web-shop renders them as size tabs by
reading the association. Example in `products.json`:

- `GS-JUICE-ORANGE-250ML` (simple parent)
- `GS-JUICE-ORANGE-500ML` (simple parent)
- `GS-JUICE-ORANGE-1L` (simple parent)

All three have the same `ingredients_list` and `allergens` but different
prices, promotional periods, and EANs.

### Mistake 2 — "Case as a variant of the consumer unit"

**Setup**: the client wants a "1 bottle of Coke" record and a "24-pack of Coke"
record in the same product.

**Why it's wrong**:
- The case has its own GTIN-14 (outer barcode) vs the consumer unit's EAN-13
- B2B pricing is per-case for the wholesale channel, per-unit for retail
- Warehouse inventory is tracked at case level, shop inventory at unit level
- Breaking them into one record breaks B2B ordering and warehouse management

**Right answer**: two separate parent products linked via a `case_contains`
association with `quantity: 24` metadata.

- `GS-COKE-330ML-UNIT` — consumer unit, EAN-13
- `GS-COKE-330ML-CASE-24` — case, GTIN-14, `case_contains` → `GS-COKE-330ML-UNIT` × 24

### Mistake 3 — "Flavours should be separate products"

**Setup**: the client has 8 flavours of the same yogurt line. They create
8 separate products and copy-paste the description, nutrition and allergens
8 times.

**Why it's wrong**:
- When the recipe changes, they forget to update 8 records
- SEO pages look near-duplicate, hurting search rankings
- Shoppers can't switch flavour on one product page

**Right answer**: **this one IS a variant case**. Single parent with `flavour`
as super-attribute. 8 children each inherit description / nutrition / allergens
from the parent and override only `flavour` and `gtin_13` (and `image_front`
if the pack design is flavour-coded).

- `GS-YOGURT-GREEK` — configurable parent
  - Variant `GS-YOGURT-GREEK-STRAWBERRY`
  - Variant `GS-YOGURT-GREEK-BLUEBERRY`
  - Variant `GS-YOGURT-GREEK-VANILLA`
  - etc.

**Distinguishing rule**: *"If customers swap between options for the same
occasion, it's a variant. If they buy different options for different
occasions, it's separate products."* Flavour = same occasion, variant.
Pack size = different occasions, separate products.

### Mistake 4 — "Completeness = filled all text fields"

**Setup**: the client proudly reports the catalog is "100% complete" because
every textarea has content. But `allergens_contains` is empty.

**Why it's wrong**: the products are not legally sellable in the EU.

**Right answer**: **configure completeness rules per channel**. For the
`ecommerce` channel in EU locales (`de_DE`, `fr_FR`, `es_ES`, `it_IT`),
mark `allergens_contains` + `ingredients_list` + `nutrition` group + `image_front`
+ `image_back` as required. The completeness score becomes a **compliance
dashboard** — red = not sellable, green = sellable.

This single insight transforms PIM from a nice-to-have to a regulatory
safety net.

### Mistake 5 — "One family for all FMCG"

**Setup**: the client starts with a single "Products" family containing 200
attributes so they can represent both food and cleaning products. Every
product has 150 empty fields.

**Why it's wrong**:
- Impossible to onboard editors — the form is overwhelming
- Grids are unusable — 200 columns of mostly-null data
- Search facets are noise

**Right answer**: separate families per regulatory class. This reference
catalog ships three sibling families:

- `food_grocery` — food products (requires nutrition, ingredients, allergens)
- `cpg_household` — cleaning / laundry / paper goods (requires UN dangerous
  goods code, hazard pictograms, GHS signal word)
- `cpg_personal_care` — shampoo, toothpaste (requires INCI ingredients,
  pH, skin type)

Each shares common groups (identifiers, packaging, media, commercial) but
has its own regulatory group.

**Rule of thumb**: if two product classes need ≥ 5 different attributes,
split them into separate families.

---

## Completeness rules per channel (recommended)

| Channel | Required attribute groups | Effect |
|---|---|---|
| `ecommerce` (EU locales) | identifiers + product_info + packaging + nutrition + ingredients_allergens + media (front + back) | Red = not EU-compliant |
| `ecommerce` (non-EU) | identifiers + product_info + packaging + media (front + back) | — |
| `mobile_app` | identifiers + product_info + media (front) | Short description only |
| `print_catalogue` | identifiers + product_info + commercial (MSRP) + media (front) | Print-safe |
| `b2b_wholesale` | identifiers + packaging + commercial + media (front) | Focus on case / pallet / cost |

## Channel-scoping examples

Every product in this catalog has **genuinely different description text**
per channel. Examples:

### `GS-COKE-330ML-UNIT`

- **`ecommerce` locale `en_US`** *(rich HTML, ~1200 chars)*:
  > "The classic taste that refreshes the world. Coca-Cola Original delivers the
  > crisp, refreshing cola flavor you love. Each 330 ml slim can is perfectly
  > portioned for a satisfying refreshment moment, wherever your day takes you.
  > Made with pure carbonated water, high-quality sugar, natural flavors, and
  > caramel colour. …"

- **`mobile_app` locale `en_US`** *(short plain text, ~100 chars)*:
  > "Classic Coca-Cola. 330ml slim can. Refreshing cola taste, perfectly portioned."

- **`print_catalogue` locale `en_US`** *(print-safe, ~300 chars)*:
  > "COCA-COLA ORIGINAL 330ML SLIM CAN. Classic carbonated soft drink.
  > Pack of 24 cans. Case weight 8.9 kg. Shelf-stable 12 months from production.
  > Halal, Kosher, Vegan."

This is the **demo moment that sells PIM**. Show a marketing stakeholder that
three people (web merchandiser, mobile UX designer, catalog printer) can each
edit their own copy without touching anyone else's — and they'll sign the
purchase order.

---

## Files in this directory

| File | Contents |
|---|---|
| `README.md` | This document |
| `cheatsheet.md` | SKU-to-pattern index for the seeded products |
| `attribute_groups.json` | The 10 groups described above |
| `attributes.json` | The ~70 attribute definitions |
| `family.json` | The `food_grocery` family + group mappings |
| `categories.json` | ~20 food categories |
| `brand_options.json` | ~15 brand option values (descriptive use) |
| `products.json` | Hand-authored products, each tagged with `demo_pattern` |
| `association_types.json` | The 6 association types (upsell, xsell, substitution, case_contains, pack_size_variant, seasonal_variant) |
| `associations.json` | Seeded associations between products |
| `import_sample.csv` | 10-row starter CSV partners can hand to clients |

## How the seeder reads these files

`FoodGroceryReferenceSeeder` orchestrates ingest in this exact order so
foreign-key constraints are satisfied:

1. `attribute_groups.json` → `attribute_groups` table
2. `attributes.json` → `attributes` table (+ option values for select/multiselect)
3. `family.json` → `attribute_families` + group mappings + attribute-family mappings
4. `categories.json` → `categories` table (nested-set, chunked insert)
5. `brand_options.json` → `attribute_options` for the `brand` attribute
6. `association_types.json` → `association_types` table
7. `products.json` → `products` table (parents first, then variants)
8. `associations.json` → `product_associations` table (after all parents exist)

Each step is wrapped in a transaction and is idempotent via `delete + insert`
so the seeder can be re-run safely after a template edit.

---

## Credits and legal posture

- All content in this directory is authored from scratch for the UnoPim
  project. Nothing is copied from any real PIM vendor's demo data.
- Brand names that appear in `brand_options.json` are used only as filter
  option values — nominative fair use, the standard PIM-demo practice.
- Product names are fictional `GS-*` prefixed SKUs paired with generic
  descriptors (e.g. "Classic Cola 330ml Slim Can", not "Coca-Cola Original™
  Reference CCBE-2024-XYZ").
- All images are SVG placeholders generated programmatically. No scraped,
  AI-generated, or brand-owned photography.
