<?php

namespace Webkul\AiAgent\Repositories;

use Illuminate\Support\Collection;
use Webkul\AiAgent\Models\Credential;
use Webkul\Core\Eloquent\Repository;

class CredentialRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return Credential::class;
    }

    /**
     * Get active credentials list for dropdowns.
     *
     * @return Collection
     */
    public function getActiveList()
    {
        return $this->model
            ->where('status', true)
            ->select('id', 'label')
            ->get();
    }
}
