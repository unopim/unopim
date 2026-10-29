<?php

namespace Webkul\CalculatedAttribute\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;

class CalculatedAttributeEventServiceProvider extends ServiceProvider
{
    /**
     * Register the listeners that graft this feature onto the admin views.
     *
     * Using the existing view-render events keeps every change additive: no core
     * Blade template is edited, so an upgrade that rewrites the attribute form
     * cannot silently drop the formula field — it would stop rendering, loudly.
     */
    public function boot(): void
    {
        /*
         * Two different hooks because the two templates nest differently: on
         * edit, `card.label.after` sits inside the left column, while on create
         * the equivalent point is already outside it, so the panel is hung
         * below both columns instead. Both are still inside <x-admin::form>,
         * which is what matters for the field to be submitted.
         */
        foreach ([
            'unopim.admin.catalog.attributes.create_form_controls.after',
            'unopim.admin.catalog.attributes.edit.card.label.after',
        ] as $event) {
            Event::listen($event, function ($viewRenderEventManager, $attribute = null): void {
                $viewRenderEventManager->addTemplate('calculated_attribute::catalog.attributes.formula');
            });
        }

        /*
         * The product form renders this instead of an input, because a
         * calculated value has nothing to submit.
         */
        Event::listen(
            'unopim.admin.products.dynamic-attribute-fields.control.calculated.before',
            function ($viewRenderEventManager): void {
                $viewRenderEventManager->addTemplate('calculated_attribute::catalog.products.calculated-field');
            }
        );

        /*
         * The registry snapshots the attribute table per process. A worker that
         * handles many jobs, or a request that saves an attribute and then
         * renders a product, must not keep serving the pre-change snapshot.
         */
        foreach ([
            'catalog.attribute.create.after',
            'catalog.attribute.update.after',
            'catalog.attribute.delete.after',
        ] as $event) {
            Event::listen($event, function (): void {
                app(AttributeRegistry::class)->flush();
            });
        }
    }
}
