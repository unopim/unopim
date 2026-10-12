<?php

namespace Webkul\ProductPassport\Enums;

/**
 * ESPR access tiers. The list and its order stay code-owned because the signed
 * URL elevation and the fail-closed clamp in `PublicationController` depend on
 * it; only the per-field assignment is admin-editable.
 */
enum PassportFieldTier: string
{
    case Consumer = 'consumer';
    case Operator = 'operator';
    case Authority = 'authority';

    public function label(): string
    {
        return trans('passport::app.templates.tiers.'.$this->value);
    }
}
