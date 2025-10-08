<?php

namespace Webkul\Completeness\Repositories;

use Webkul\Completeness\Contracts\CompletenessSetting;
use Webkul\Core\Eloquent\Repository;

class CompletenessSettingsRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return CompletenessSetting::class;
    }
}
