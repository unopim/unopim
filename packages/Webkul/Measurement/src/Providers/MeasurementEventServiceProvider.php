<?php

namespace Webkul\Measurement\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MeasurementEventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen(
            'unopim.admin.catalog.attributes.edit.card.label.after',
            function ($viewRenderEventManager, $attribute = null) {
                $viewRenderEventManager->addTemplate(
                    'measurement::catalog.attributes.edit',
                    [
                        'attribute' => $attribute,
                    ]
                );
            }
        );

        Event::listen(
            'unopim.admin.products.dynamic-attribute-fields.control.measurement.before',
            function ($viewRenderEventManager) {
                $viewRenderEventManager->addTemplate(
                    'measurement::catalog.products.edit'
                );
            }
        );

        Event::listen(
            'catalog.attribute.update.before',
            \Webkul\Measurement\Listeners\ValidateAttributeMeasurementBeforeUpdate::class . '@handle'
        );
    }
}
