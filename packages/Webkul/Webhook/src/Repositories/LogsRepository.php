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
}
