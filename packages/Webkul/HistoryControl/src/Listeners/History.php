<?php

namespace Webkul\HistoryControl\Listeners;

use Prettus\Repository\Events\RepositoryEventBase;

class History
{
    public function handle(RepositoryEventBase $event)
    {
        $this->repository = $event->getRepository();
        $this->model = $event->getModel();
        $this->action = $event->getAction();

    }
}
