# Calculated Attributes — Usage

A **calculated attribute** holds no value of its own. Its value is derived from a formula
every time it is shown, exported or returned by the API.

Because the value is derived, a calculated attribute is:

- **read-only** in the product form
- **ignored on import** (the column is accepted so an exported file still re-imports, but
  whatever it contains is discarded)
- **not offerable** as a completeness requirement

For why it works this way, see [the design document](calculated-attributes-design.md).

---

## Walkthrough: the "Artikelfamilie" column

Goal: a CSV column holding the product's category path, using the **localised category
names** joined with `#` — for example `Arbeitskleidung#Anzug`.

### 1. Check the category tree

Under **Catalog → Categories**, the categories must be nested the way the path should read,
and each one needs a name in the locale you export:

```
root                        (the technical root — never part of the path)
└── arbeitskleidung         name (de_DE): "Arbeitskleidung"
    └── anzug               name (de_DE): "Anzug"
```

A category with no name in the exported locale falls back to its **code**, so a path can
come out as `arbeitskleidung#anzug` if the German names are missing. That is the usual
cause of a "why are my labels codes?" surprise.

### 2. Create the attribute

**Catalog → Attributes → Create Attribute**

| Field | Value |
|---|---|
| Code | `artikelfamilie` |
| Type | **Calculated** |
| Value per locale | **on** — category names are localised, so the value differs per locale |
| Value per channel | off |

Choosing the type **Calculated** reveals the **Formula** panel. Enter:

```
category_label_path('#')
```

Save. The formula is checked on save — syntax, unknown attribute codes, functions outside
the whitelist and circular dependencies all produce a message on the field rather than a
silent failure.

### 3. Add it to the attribute family

**Catalog → Families → (your family) → Attributes** — drag `artikelfamilie` into a group,
the same as any other attribute. Without this it will not appear on the product form.

### 4. Check a product

Open any product assigned to `anzug`. In the group you chose, `artikelfamilie` shows as a
greyed-out, non-editable field reading:

```
Arbeitskleidung#Anzug
```

Switch the locale selector and the value follows the category names of that locale.

### 5. Export it

**Settings → Data Transfer → Exports → Create**, entity type *Products*. In the attribute
selection, `artikelfamilie` appears alongside every other attribute — pick it like any
other column.

The exported CSV has one row per product per channel/locale, and the `artikelfamilie`
column holds the computed path for that row's locale:

```csv
sku,channel,locale,artikelfamilie
ANZUG-01,default,de_DE,Arbeitskleidung#Anzug
ANZUG-01,default,en_US,Workwear#Suit
```

Leaving the attribute selection empty exports every attribute, calculated ones included.

---

## Second example: a concatenated key

A column joining manufacturer and manufacturer part number:

| Field | Value |
|---|---|
| Code | `hersteller_schluessel` |
| Type | Calculated |
| Formula | `concat('-', hersteller, herstellerartikelnummer)` |

`concat()` drops empty parts, so a product with no part number yields `ACME` rather than
`ACME-`. That is the single most useful property of the function, and the reason to prefer
it over gluing strings together by hand.

---

## Writing formulas

### Referring to other attributes

Write the attribute **code** as a bare word:

```
concat(' ', hersteller, modell)
```

The value is resolved in the current scope automatically, following that attribute's own
*value per locale* / *value per channel* settings. You never write the scope yourself.

Variant products see values inherited from their parent, the same ones the product form
shows.

### System variables

| Variable | Meaning |
|---|---|
| `sku` | the product SKU |
| `type` | `simple` or `configurable` |
| `status` | the product status |
| `family` | the attribute family code |
| `parent` | the parent SKU, for variants |
| `categories` | the assigned category codes, as a list |
| `channel` | the channel being rendered |
| `locale` | the locale being rendered |

### Functions

**Text** — `concat(sep, ...)`, `join(sep, list)`, `coalesce(...)`, `upper`, `lower`, `trim`,
`length`, `substr(s, start, len)`, `replace(s, search, replace)`, `pad_left(s, len, ch)`,
`pad_right(s, len, ch)`, `split(s, sep)`

**Logic** — `if(cond, then, else)`, `is_empty(v)`, `default(v, fallback)`

**Numbers** — `round(n, p)`, `floor`, `ceil`, `abs`, `min(...)`, `max(...)`,
`number_format(n, decimals, dec_point, thousands_sep)`

**Dates** — `now()`, `date_format(v, 'Y-m-d')`, `date_add(v, '+1 month')`, `year`, `month`, `day`

**Categories** — `category_path(sep)`, `category_label_path(sep)`, `category_codes()`,
`category_labels()`

Operators `+ - * / %`, comparisons, `and` / `or` / `not`, and the ternary `a ? b : c` work
as expected. The regex operator `matches` is deliberately **not** allowed.

### Recipes

```
// Fall back to a second attribute when the first is empty
coalesce(kurzbeschreibung, name)

// Empty when a precondition is not met
if(is_empty(ean), '', concat('-', 'EAN', ean))

// Gross price from net
round(preis * 1.19, 2)

// Year and month of a date attribute
date_format(erscheinungsdatum, 'Y-m')

// Different structure per locale, one definition
if(locale == 'de_DE', category_label_path('#'), category_label_path(' > '))

// A calculated attribute may build on another calculated attribute
upper(concat('/', 'DE', artikel_schluessel))
```

---

## Rules and limits

- **No circular dependencies.** `a` referring to `b` while `b` refers to `a` is rejected on
  save, with the cycle named in the message (`a -> b -> a`). Chains of any length are
  checked, not just direct pairs.
- **No arbitrary code.** Only the functions above are callable; anything else is refused at
  save time. A formula cannot reach PHP functions, constants or objects.
- **Length limit** of 2000 characters.
- **One formula per attribute**, evaluated once per channel/locale. If you need a different
  *structure* per locale, branch inside the formula on `locale`.
- An attribute code that is not a valid identifier (one starting with a digit, say) cannot
  be referenced from a formula.

## Troubleshooting

| Symptom | Cause |
|---|---|
| `Unknown attribute or variable "x"` | typo in the code, or the attribute does not exist |
| `Function "x" is not allowed in a formula.` | outside the whitelist — check the spelling against the list above |
| `The formula creates a circular dependency: …` | follow the named chain and break it |
| Column exports as empty | every source value is empty for that product/locale, or the formula references an attribute that was since deleted — re-save the attribute to see the real error |
| Path shows codes instead of names | the categories have no name in that locale |
| Path is missing its top level | the technical root category is deliberately excluded |

## Operational notes

- Values are computed on demand and never stored, so there is nothing to rebuild or
  reindex after changing a formula or renaming a category — the next render or export is
  already correct.
- Calculated values are **not** indexed in Elasticsearch, so the product grid cannot filter
  or sort on them in this version.
- Deleting an attribute that a formula references does not break the page: the calculated
  value renders empty, and re-saving the calculated attribute reports the real error.
