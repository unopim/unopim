<?php

namespace Webkul\ProductPassport\Enums;

/**
 * Reserved payload slots. A field carrying a role feeds the identifier block and
 * the data carrier instead of rendering as an ordinary passport row, so any
 * attribute a merchant already maintains can act as the GTIN.
 */
enum PassportFieldRole: string
{
    case Gtin = 'gtin';
    case Model = 'model';
    case Batch = 'batch';

    public function label(): string
    {
        return trans('passport::app.templates.roles.'.$this->value);
    }
}
