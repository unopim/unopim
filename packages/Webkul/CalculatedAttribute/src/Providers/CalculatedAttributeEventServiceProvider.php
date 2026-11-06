<?php

namespace Webkul\CalculatedAttribute\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Support\AttributeDeletionGuard;
use Webkul\Attribute\Support\AttributeValidationRules;
use Webkul\CalculatedAttribute\Observers\AttributeFormula;
use Webkul\CalculatedAttribute\Rules\ValidFormula;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\CategoryPathResolver;
use Webkul\CalculatedAttribute\Services\FormulaValidator;

class CalculatedAttributeEventServiceProvider extends ServiceProvider
{
    /**
     * Graft this feature onto the admin views and the attribute lifecycle.
     *
     * Everything here is additive: no core Blade template is edited and no core
     * class names this package, so removing the provider removes the feature.
     */
    public function boot(): void
    {
        AttributeProxy::observe(AttributeFormula::class);

        $this->registerFormTemplates();

        $this->registerCacheInvalidation();

        $this->registerValidationRules();

        $this->registerDeletionGuard();
    }

    /**
     * Hang the formula editor on the attribute form and the read-only control
     * on the product form.
     *
     * Two hooks for the attribute form because the two templates nest
     * differently: on edit, `card.label.after` sits inside the left column,
     * while on create the equivalent point is already outside it, so the panel
     * is hung below both columns instead. Both are still inside
     * `<x-admin::form>`, which is what matters for the field to be submitted.
     */
    protected function registerFormTemplates(): void
    {
        foreach ([
            'unopim.admin.catalog.attributes.create_form_controls.after',
            'unopim.admin.catalog.attributes.edit.card.label.after',
        ] as $event) {
            Event::listen($event, function ($viewRenderEventManager, $attribute = null): void {
                $viewRenderEventManager->addTemplate('calculated_attribute::catalog.attributes.formula');
            });
        }

        Event::listen(
            'unopim.admin.products.dynamic-attribute-fields.control.calculated.before',
            function ($viewRenderEventManager): void {
                $viewRenderEventManager->addTemplate('calculated_attribute::catalog.products.calculated-field');
            }
        );
    }

    /**
     * Drop the snapshots as soon as what they describe changes.
     *
     * The bindings are `scoped()`, so nothing survives the request or job that
     * built it; these listeners cover the shorter window inside one — saving an
     * attribute and then rendering a product, or an import job that renames a
     * category and keeps going.
     */
    protected function registerCacheInvalidation(): void
    {
        foreach ([
            'catalog.attribute.create.after',
            'catalog.attribute.update.after',
            'catalog.attribute.delete.after',
        ] as $event) {
            Event::listen($event, function (): void {
                app(AttributeRegistry::class)->flush();
            });
        }

        foreach ([
            'catalog.category.create.after',
            'catalog.category.update.after',
            'catalog.category.delete.after',
        ] as $event) {
            Event::listen($event, function (): void {
                app(CategoryPathResolver::class)->flush();
            });
        }
    }

    /**
     * Validate the formula on every write path.
     *
     * The admin form and both REST endpoints collect their rules through the
     * same core event, so a formula that does not parse, names an unknown
     * attribute or closes a cycle is refused identically by all three.
     *
     * The type and the code are immutable after creation, so on an update the
     * stored row decides what is validated, not the payload. A calculated
     * attribute without a formula would render empty everywhere, so one is
     * required at creation; a partial update that does not mention the field
     * leaves the stored one alone.
     */
    protected function registerValidationRules(): void
    {
        Event::listen(
            AttributeValidationRules::EVENT,
            function (array $data, ?int $attributeId): array {
                $attribute = $attributeId === null
                    ? null
                    : app(AttributeRepository::class)->find($attributeId);

                $type = $attribute?->type ?? ($data['type'] ?? null);

                if ($type !== Attribute::CALCULATED_FIELD_TYPE) {
                    return ['formula' => ['nullable']];
                }

                $code = (string) ($attribute?->code ?? ($data['code'] ?? ''));

                $presence = $attribute === null ? ['required'] : ['sometimes', 'required'];

                return ['formula' => array_merge($presence, ['string', new ValidFormula($code)])];
            }
        );
    }

    /**
     * Refuse to delete an attribute that a formula reads.
     *
     * Deleting one silently empties every column derived from it, which is a
     * failure nobody would notice until an export arrived with blank columns.
     */
    protected function registerDeletionGuard(): void
    {
        Event::listen(AttributeDeletionGuard::EVENT, function (object $attribute): ?string {
            $dependents = app(FormulaValidator::class)->dependents((string) ($attribute->code ?? ''));

            if ($dependents === []) {
                return null;
            }

            return trans('calculated_attribute::app.formula.errors.referenced-by', [
                'attributes' => implode(', ', $dependents),
            ]);
        });
    }
}
