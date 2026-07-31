<?php

namespace Webkul\HistoryControl\Listeners;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Repository\Events\RepositoryEventBase;

class History
{
    /**
     * @var RepositoryInterface
     */
    public $repository;

    /**
     * @var Model|mixed[]
     */
    public $model;

    /**
     * @var string
     */
    public $action;

    public function handle(RepositoryEventBase $event): void
    {
        $this->repository = $event->getRepository();
        $this->model = $event->getModel();
        $this->action = $event->getAction();

    }
}
