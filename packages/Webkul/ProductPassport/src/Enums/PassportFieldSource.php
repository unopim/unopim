<?php

namespace Webkul\ProductPassport\Enums;

enum PassportFieldSource: string
{
    case Attribute = 'attribute';
    case Fixed = 'fixed';

    public function label(): string
    {
        return trans('passport::app.templates.sources.'.$this->value);
    }
}
