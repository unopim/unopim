<?php

namespace Webkul\Core;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Visitor as BaseVisitor;
use Webkul\Core\Jobs\UpdateCreateVisitIndex;

class Visitor extends BaseVisitor
{
    /**
     * Create a visit log.
     */
    #[\Override]
    public function visit(?Model $model = null): void
    {
        foreach ($this->except as $path) {
            if ($this->request->is($path)) {
                return;
            }
        }

        UpdateCreateVisitIndex::dispatch($model, $this->prepareLog());
    }

    /**
     * Retrieve request's url
     */
    #[\Override]
    public function url(): string
    {
        return $this->request->url();
    }

    /**
     * Returns logs
     */
    public function getLog(): array
    {
        return $this->prepareLog();
    }
}
