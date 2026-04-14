# `food_grocery` — SKU-to-Pattern Cheatsheet

Use this to find the exact product that illustrates a given PIM modelling pattern.
Every seeded product is tagged with a `demo_pattern` key in `products.json`.

## Channel-scoped descriptions

**SKU**: `GS-COLA-CLASSIC-330ML`
**Shows**: The same product having genuinely different marketing copy per channel —
`ecommerce` has 1200-char rich HTML, `mobile_app` has 100-char plain text,
`print_catalogue` has 300-char uppercase catalogue copy, `b2b_wholesale` focuses
on case quantity and lead time. **This is the single most convincing PIM demo moment.**
Open the product, switch the channel dropdown, and watch the description field
change.

## Case → Consumer-Unit association

**Parent SKU**: `GS-COLA-CLASSIC-330ML-CASE-24`
**Links to**: `GS-COLA-CLASSIC-330ML` via `case_contains` (quantity 24)
**Shows**: Why you must model "a single can of cola" and "a 24-pack of cola" as
two separate products, not one product with a quantity field. The case has its
own GTIN-14 (outer barcode), its own B2B price, and its own warehouse logic.

## Pack-size variants (separate products, NOT variant children)

**SKUs**: `GS-JUICE-ORANGE-250ML` + `GS-JUICE-ORANGE-1L`
**Linked via**: `pack_size_variant` association
**Shows**: Why pack size should be separate products linked by association, not
variants of one parent. A 250 ml grab-and-go juice serves a different occasion
than a 1 L family-size carton — they have different prices, different shelf
placements, and different EANs. Variant axes are for *interchangeable* options
(same occasion), not *complementary* options (different occasions).

## Flavour variants (configurable parent + variant children)

**Parent SKU**: `GS-YOGURT-GREEK` (type: `configurable`, super-attribute: `flavour`)
**Variants**:
- `GS-YOGURT-GREEK-NATURAL`
- `GS-YOGURT-GREEK-STRAWBERRY`
- `GS-YOGURT-GREEK-BLUEBERRY`
- `GS-YOGURT-GREEK-VANILLA`

**Shows**: The *correct* use of variants. All four flavours share the same
pack type, shelf life, allergens and brand — they only differ on the `flavour`
attribute and the flavour-level EAN-13. A customer scrolling through the yogurt
aisle swaps flavours for the *same occasion*, so variants are right.

## Intentionally-incomplete products (completeness demo)

**SKUs**:
- `GS-WATER-STILL-500ML` — missing `allergens_contains`
- `GS-BISCUIT-BUTTER-200G` — missing `ingredients_list`

**Shows**: When the `ecommerce` channel's completeness rule requires both
`allergens_contains` and `ingredients_list` (see `family.json →
required_on_channel.ecommerce`), these two products show up in the
completeness dashboard as **not sellable in the EU**. That transforms the
completeness score from a vanity metric into a compliance gate.

## Full regulatory content

**SKU**: `GS-CEREAL-HONEY-FLAKES-500G`
**Shows**: A product with **every field filled correctly** — full nutrition table
per 100g, declared allergens, "may contain traces" declaration, ingredients list,
brand, country of origin, GTIN-13. The completeness bar reads **100%** against
the EU Ecommerce channel rule. This is the reference for "what a correctly-modelled
FMCG product looks like."

## Multi-certification organic

**SKU**: `GS-COFFEE-ARABICA-250G`
**Shows**: Three simultaneous certifications — Fair Trade, Rainforest Alliance,
EU Organic — each as its own option on the `certifications` multiselect. Brand
+ organic + fair-trade demoes the filter facet side of PIM.

**SKU**: `GS-OLIVE-OIL-EXV-500ML`
**Shows**: Single-certification organic (`eu_organic`) with Italian country of
origin. Combined with the `it_IT` locale translation to demo locale-specific
product content.

## Allergen-rich product

**SKU**: `GS-CHOCOLATE-DARK-85-100G`
**Shows**: A product that contains two allergens (`milk`, `soy`) AND has
may-contain-traces warnings (`tree_nuts`, `peanuts`, `gluten_cereals`). Opens
the way to demo the **allergen filter facet** on the storefront.

## Locale asymmetry (translation workflow demo)

| Product | en_US | en_GB | de_DE | fr_FR | it_IT |
|---|---|---|---|---|---|
| `GS-COLA-CLASSIC-330ML` | ✅ | ✅ | ✅ | ✅ | — |
| `GS-COFFEE-ARABICA-250G` | ✅ | — | ✅ | ✅ | — |
| `GS-OLIVE-OIL-EXV-500ML` | ✅ | — | — | — | ✅ |
| `GS-PASTA-SPAGHETTI-500G` | ✅ | — | — | — | ✅ |
| `GS-YOGURT-GREEK` | ✅ | — | ✅ | — | — |
| `GS-CHOCOLATE-DARK-85-100G` | ✅ | — | ✅ | — | — |
| `GS-JUICE-ORANGE-1L` | ✅ | — | ✅ | ✅ | — |

**Shows**: Asymmetric locale coverage is realistic. The remaining cells are
**genuine translation work** for the Magic AI Translate feature to do. Opening
`GS-COLA-CLASSIC-330ML` in the `es_ES` locale reveals missing content → partner
clicks **Translate** → AI fills it in → reviewer approves → merged.

## How to walk a prospect through this catalog

A 5-minute demo script a partner can run for a client:

1. **"This is how descriptions change per channel."** → Open `GS-COLA-CLASSIC-330ML`,
   toggle channel dropdown.
2. **"This is how variants work."** → Open `GS-YOGURT-GREEK`, show the 4 flavours,
   point out shared attributes vs per-variant attributes.
3. **"This is why pack sizes are NOT variants."** → Open `GS-JUICE-ORANGE-250ML`,
   show the `pack_size_variant` association to `GS-JUICE-ORANGE-1L`.
4. **"This is the compliance gate."** → Open `GS-WATER-STILL-500ML`, show the
   red completeness badge against the ecommerce channel (missing allergens).
5. **"This is the translation workflow."** → Open `GS-COLA-CLASSIC-330ML` in
   `es_ES` locale, show the missing fields, click **Translate** to fill them.

Deal closed.
