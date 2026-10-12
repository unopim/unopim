<?php

namespace Webkul\ProductPassport\DataTransferObjects;

use Illuminate\Support\Collection;
use Webkul\ProductPassport\Contracts\PassportTemplate as PassportTemplateContract;
use Webkul\ProductPassport\Contracts\PassportTemplateField as PassportTemplateFieldContract;

final readonly class PassportReadiness
{
    /**
     * @param  Collection<int, PassportTemplateFieldContract>  $missingFields
     */
    public function __construct(
        public ?PassportTemplateContract $template,
        public Collection $missingFields,
    ) {}

    public function isReady(): bool
    {
        return $this->template !== null && $this->missingFields->isEmpty();
    }
}
