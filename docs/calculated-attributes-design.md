# Calculated Attributes — Design

Status: accepted (v1)
Base: UnoPim 3.1.3
Branch: `feature/calculated-attributes`

A calculated attribute is an attribute whose value is **derived** from other values of the
same product by evaluating a stored formula. It is never typed in by a user, never written
by an import, and never stored in `products.values`.

Target use cases (examples only — the feature itself is generic):

- an "Artikelfamilie" column holding the category **label** path joined with `#`
  (`Arbeitskleidung#Anzug`)
- a key column concatenating e.g. manufacturer and manufacturer part number

---

## 1. Scope and non-goals

In scope for v1:

- a new attribute type `calculated` with a stored formula
- a sandboxed expression language with a function whitelist
- syntax + circular-dependency validation on save
- evaluation on demand for product detail view, CSV/XLSX export and the admin API
- read-only everywhere: product form, import, completeness

Explicitly **not** in scope for v1:

- using a calculated attribute as a configurable (super) attribute axis
- filtering/sorting the product grid by a calculated value
- Elasticsearch indexing of calculated values
- calculated attributes on categories or association fields

These are all additive follow-ups; nothing in the v1 data model blocks them.

---

## 2. Data model

### 2.1 Migration

One migration, `add_formula_to_attributes_table`:

```php
$table->text('formula')->nullable()->after('default_value');
```

Rollback drops the column. Nothing else in the schema changes — no new table, no new
foreign key, no change to `products.values`.

Rationale for a column on `attributes` rather than a side table (as `attribute_measurement`
does): a formula is a 1:1 scalar property of the attribute with no own lifecycle, no
translations and no relations. A side table would add a join to the attribute hot path
(`AttributeRepository::all()` is called per export worker) for no benefit. The measurement
package needs a side table because it stores a *relation* (attribute → measurement family +
unit), which is a different shape.

### 2.2 Attribute type

`calculated` is registered as a new attribute type through the standard
`mergeConfigFrom(..., 'attribute_types')` mechanism, exactly as `Webkul\Measurement` registers
`measurement`. That automatically makes it:

- selectable in the attribute create/edit type dropdown
- accepted by `Webkul\Attribute\Rules\AttributeTypes` (which validates against
  `array_keys(config('attribute_types'))`)
- filterable in the attribute datagrid

### 2.3 Why a new package

The feature lives in a new first-party package `Webkul\CalculatedAttribute`, registered in
`bootstrap/providers.php`. This mirrors `Webkul\Measurement`, which is the established
precedent in this codebase for "an extra attribute type plus its behaviour". Keeping it out
of `Webkul\Attribute` means the core attribute package gains no new dependency on an
expression engine, and the whole feature can be disabled by removing one provider line.

---

## 3. Formula language

### 3.1 Engine choice

**Decision: `symfony/expression-language`, hardened.**

| Option | Verdict |
|---|---|
| raw PHP `eval()` | rejected outright — arbitrary RCE from an admin text field |
| Blade / Twig | template engines, not expression evaluators; both reach PHP callables too easily |
| hand-written DSL (lexer + parser + evaluator) | ~800–1200 lines of new parser code to review and fuzz, all of it a fresh security surface. Rejected: we would be writing a sandbox from scratch to avoid using a sandbox that already exists. |
| **symfony/expression-language** | **chosen** — a dedicated, widely deployed expression evaluator with a compact AST, no statements, no loops, no assignment, no `new`, and functions limited to what the host registers |

Hardening applied on top of the component (see `SafeExpressionLanguage`):

1. `registerFunctions()` is overridden and does **not** call `parent::registerFunctions()`.
   That drops Symfony's built-in `constant()` registration, so the function table starts empty
   and contains only our whitelist.
2. Only scalars, `null` and arrays are ever placed in the evaluation context. No object ever
   enters the context, so the `foo.bar` property accessor and `foo.bar()` method-call nodes
   have nothing to reach. This is the important one: ExpressionLanguage's sandbox is only as
   tight as the values you hand it.
3. `parse()` is called with an explicit list of allowed variable names. An expression naming
   an unknown variable fails at parse time with a `SyntaxError`, which is what powers the
   "unknown attribute code" error message.
4. Evaluation is wrapped so any engine exception becomes a null value at render/export time
   rather than a 500.

The attack surface that remains is "an admin with attribute-edit permission can write a slow
expression". That is bounded by the expression length limit (2000 chars, enforced by a
validation rule) and by the absence of loops in the grammar.

### 3.2 Variables

Inside a formula the following names are bound:

| Name | Type | Contents |
|---|---|---|
| `<attribute_code>` | scalar / array | the product's value for that attribute **resolved in the current scope** |
| `sku` | string | product SKU |
| `type` | string | `simple` / `configurable` |
| `status` | bool | product status |
| `family` | string | attribute family code |
| `parent` | string\|null | parent SKU for variants |
| `categories` | string[] | category codes assigned to the product |
| `channel` | string | code of the channel being evaluated |
| `locale` | string | code of the locale being evaluated |

Scope resolution for a referenced attribute uses the existing
`Attribute::getValueFromProductValues($values, $channel, $locale)`, so a referenced attribute
transparently resolves from `common`, `locale_specific`, `channel_specific` or
`channel_locale_specific` according to **its own** `value_per_locale` / `value_per_channel`
flags. The formula author does not spell the scope out, and does not need to.

The values passed in are the **variant-resolved** values
(`VariantValueResolver::resolve()`), so a variant that inherits `manufacturer` from its
parent sees the inherited value, consistent with how the product form and export already
behave.

### 3.3 Function whitelist

Only these are callable. Anything else is a validation error at save time.

**Strings**

| Function | Notes |
|---|---|
| `concat(sep, ...parts)` | joins non-empty parts with `sep`; empty/null parts are dropped, so no dangling separators |
| `join(sep, list)` | same, for an array |
| `coalesce(...)` | first non-empty argument |
| `upper(s)`, `lower(s)`, `trim(s)` | |
| `substr(s, start, len)` | |
| `replace(s, search, replace)` | |
| `length(s)` | |
| `contains(s, needle)`, `starts_with(s, p)`, `ends_with(s, p)` | |
| `pad_left(s, len, ch)`, `pad_right(s, len, ch)` | |

**Logic**

| Function | Notes |
|---|---|
| `if(cond, then, else)` | eager (all branches evaluated; no side effects exist, so this is safe) |
| `is_empty(v)` | true for `null`, `''`, `[]` |
| `default(v, fallback)` | `v` unless empty, else `fallback` |

The ternary `a ? b : c` and the operators `and or not == != < > <= >= + - * / %` come from the
grammar itself.

**Numbers**

`round(n, p)`, `floor(n)`, `ceil(n)`, `abs(n)`, `number_format(n, decimals, dec_point, thousands_sep)`

**Dates**

`now()`, `date_format(value, format)`, `date_add(value, modifier)`, `year(v)`, `month(v)`, `day(v)`

`date_format` uses PHP date format letters and is null-safe.

**Categories**

| Function | Notes |
|---|---|
| `category_path(sep)` | code path of the product's *deepest* category, root excluded |
| `category_label_path(sep)` | same but using the localised category names |
| `category_codes()` | array of assigned category codes |
| `category_labels()` | array of assigned category names in the current locale |

The "Artikelfamilie" case is therefore `category_label_path('#')`.

The category path deliberately excludes the nested-set root node: every category in UnoPim
descends from a single technical root, and including it would prefix every value with the
same noise.

### 3.4 Multilingual behaviour — decision

**Decision: one formula per attribute, evaluated once per (channel, locale).**

The alternative — a formula per locale — was rejected:

- it multiplies the maintenance surface by the number of locales for a feature whose common
  case is *locale-independent structure with locale-dependent inputs*
- it would need its own translations table, and circularity checking would have to run per
  locale, so a formula could be valid in `de_DE` and circular in `en_US`
- the actual requirement ("Artikelfamilie must read German labels in German exports") is
  already met by evaluating a single formula in a locale context, because
  `category_label_path()` and every referenced locale-specific attribute resolve against the
  active locale

If a genuinely different *structure* per locale is ever needed, it is expressible today
inside the one formula via `if(locale == 'de_DE', ..., ...)`, which keeps one reviewable
definition per attribute.

A calculated attribute's own `value_per_locale` / `value_per_channel` flags still control how
it is *presented* (badges in the form, one column vs. one value in the export row), and are
settable as with any other attribute.

### 3.5 Circular dependency detection

On save, the formula is parsed and the set of referenced attribute codes extracted from the
AST (`Nodes\NameNode`), not by regex. The referenced codes that are themselves `calculated`
form the edges of a directed graph; the graph is loaded once from the `attributes` table and
a depth-first search looks for a cycle that includes the attribute being saved (including the
self-edge `a -> a`).

A cycle is rejected with a message naming the cycle, e.g.

```
The formula creates a circular dependency: article_key -> family_path -> article_key
```

Because the check runs on every save of any calculated attribute, and the graph can only be
extended through that same code path, the stored set of formulas is acyclic by construction.
Evaluation therefore needs no runtime cycle guard; it still carries a recursion depth limit
as a defence-in-depth measure against a graph mutated directly in the database.

---

## 4. Evaluation and caching strategy

### 4.1 The two candidates

**A — compute on read.** No persisted value. Every consumer (detail view, export row, API
response) evaluates the formula when it needs the value.

**B — materialise on write.** Store the computed value into `products.values` on product save
and on category reassignment; re-compute dependents when a source attribute changes;
re-compute *every* product when a formula or a category label changes.

### 4.2 What drives the cost

Relevant catalogue size: ~1500 products, the number given in the requirements. For export
cost the row count that matters is products × channels × locales; the shape of the export
loop (`Exporter::prepareProducts()`) is one row per product per channel-locale pair.

The per-evaluation cost of a compiled expression is dominated by context assembly, not by the
expression itself: `ExpressionLanguage` caches parsed expressions by (expression, variable
names), so each formula is parsed **once per worker process** and then evaluated as a
compiled closure. Evaluation of a typical 2–4 term formula is in the low microseconds.

For a 1500-product catalogue with 2 channel-locale combinations and 3 calculated attributes
that is ~9000 evaluations per full export — a negligible share of an export that already
spends its time on I/O and on per-row attribute formatting.

The category functions are the one real cost, because naively they would hit the database per
product. That is solved by a per-process `CategoryPathResolver` that loads the whole category
tree once and answers path queries from memory. This makes the category path case O(1) per
product after one query, the same order as option B.

### 4.3 Decision: compute on read (option A)

Reasons, in order of weight:

1. **Correctness is free.** A derived value can never be stale, because there is no stored
   copy to go stale. Option B needs invalidation on: product save, category assignment change,
   **category rename** (which changes `category_label_path` for every product in that subtree),
   formula edit, attribute-option label edit, and variant re-parenting. Each of those is a
   separate hook, and the category-rename case alone is a full-catalogue recompute. Missing any
   one of them produces silently wrong exports — the exact failure mode the requirements call
   out ("klare Fehlermeldung statt stillem Fehlschlag").
2. **The performance argument for B is weak at this size.** B trades a few microseconds of
   read-time CPU for a write-time recompute job plus a storage copy. At 1500 products the read
   cost is not a bottleneck; at 10× that it is still not the bottleneck compared to the
   existing per-row export formatting.
3. **No write amplification.** Option B would write into `products.values`, which means every
   calculated attribute shows up in the product audit history as a change on every recompute,
   polluting `HistoryControl` with machine-generated diffs.
4. **Clean rollback.** Dropping the feature means dropping one column. Under option B it would
   also mean scrubbing derived values out of every product's `values` JSON.

Caching that *is* implemented, all of it process-local and read-only:

- parsed/compiled expressions, memoised by the engine
- the category tree (one query per request or per export worker)
- the calculated-attribute list and their extracted dependencies

### 4.4 Revisiting the decision

The decision is cheap to reverse because all consumers go through one service,
`CalculatedValueResolver`. If a future catalogue makes read-time evaluation hot, a
materialising cache can be added behind that service without touching the exporter, the view
or the API. The design note to carry forward is that such a cache must be keyed by
(product, channel, locale, formula version, category tree version).

---

## 5. Integration points

| Area | Behaviour |
|---|---|
| Attribute form (create/edit) | formula textarea shown when type is `calculated`, with inline help listing the available functions; validated on submit |
| Product detail/edit form | rendered read-only (disabled input with the computed value and a "calculated" badge); no `name` attribute, so nothing is submitted |
| Product save | calculated codes are stripped from the incoming values before validation and persistence, so a crafted POST cannot write one |
| CSV/XLSX export | the exporter already builds its column list from *all* attributes, so a calculated attribute is selectable in the profile like any other; values are injected into the scope-merged field array before `setAttributesValues()` |
| CSV import | calculated columns are ignored rather than written |
| Completeness | calculated attributes are excluded from the completeness attribute picker, so they can never become a required field |
| Admin API | calculated values are added to the product response under the scope they belong to |
| Variants | calculated values are computed from variant-resolved values; a variant computes its own value from its own effective inputs |

### 5.1 Why export injection happens at the merged-fields stage

`Exporter::prepareProducts()` merges `common`, `locale_specific`, `channel_specific` and
`channel_locale_specific` into one flat `$mergedFields` array per (channel, locale) and hands
it to `setAttributesValues()`. Injecting there means:

- the calculated value is already scope-resolved for the row being written
- `setAttributesValues()` applies the same escaping (`EscapeFormulaOperators`) and the same
  selected-attribute filtering as for stored values, with no special-casing
- a calculated attribute with `value_per_locale` set naturally varies by row

---

## 6. UI sketch

**Attribute create/edit** — when `type == 'calculated'`, a panel appears below the type
selector:

```
┌─ Formula ──────────────────────────────────────────────┐
│ ┌────────────────────────────────────────────────────┐ │
│ │ category_label_path('#')                           │ │
│ │                                                    │ │
│ └────────────────────────────────────────────────────┘ │
│ Reference other attributes by their code.              │
│ ▸ Available functions                 (collapsible)    │
│   concat(sep, ...)  if(cond, a, b)  coalesce(...)      │
│   category_label_path(sep)  date_format(v, fmt)  ...   │
│                                                        │
│ Examples:                                              │
│   concat('-', manufacturer, manufacturer_part_number)  │
│   category_label_path('#')                             │
└────────────────────────────────────────────────────────┘
```

On save failure the field shows the engine's message, mapped to a human sentence:

- `Unknown attribute or variable "manufactrer" in the formula.`
- `The formula creates a circular dependency: a -> b -> a`
- `Function "shell_exec" is not allowed.`

Autocompletion of attribute codes is explicitly a nice-to-have and is not built in v1; the
help panel lists the function names, and attribute codes are visible in the attribute grid.

**Product edit** — the field renders as a disabled input showing the computed value, with the
same label/scope badges as any other attribute plus a `calculated` badge. Empty computed
values render as an empty field rather than an error.

---

## 7. Testing strategy

| Case | Level |
|---|---|
| simple concatenation, separator handling, empty-part dropping | unit |
| category label path (the "Artikelfamilie" walkthrough) | feature, against real categories |
| circular dependency rejected on save (direct and transitive) | feature |
| unknown attribute code rejected on save | feature |
| disallowed function rejected on save | unit |
| missing / empty / null source values yield empty, not an error | unit |
| scope resolution (common / locale / channel / channel+locale) | unit |
| export emits the computed value in the right column | feature |
| import does not write a calculated attribute | feature |
| completeness picker excludes calculated attributes | feature |

---

## 8. Open questions

1. **Elasticsearch.** Calculated values are not indexed, so the product grid cannot filter on
   them. Materialising into the ES document (not into MySQL) would be the natural way to add
   this, and is compatible with the compute-on-read decision.
2. **Export of a calculated attribute whose formula references a locale-specific attribute,
   in a profile that exports only one locale.** Works, but the value is computed per exported
   row, so the same product can legitimately show different values across rows. Documented
   rather than prevented.
3. **Permissions.** Editing a formula is covered by the existing attribute-edit ACL. Whether a
   separate finer-grained permission is wanted is a product decision.
4. **Formula length / complexity budget.** Capped at 2000 characters. No CPU time budget is
   enforced per evaluation; the grammar has no loops, so the worst case is bounded by
   expression length.
