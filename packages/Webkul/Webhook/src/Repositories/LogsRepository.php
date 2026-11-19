<?php

namespace Webkul\Webhook\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Webhook\Models\WebhookLog;

class LogsRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return WebhookLog::class;
    }

    /**
     * Delete the given logs in a single query.
     *
     * @param  array<int>  $ids
     */
    public function massDelete(array $ids): int
    {
        return $this->model->newQuery()->whereKey($ids)->delete();
    }
}
