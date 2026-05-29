<?php

declare(strict_types=1);

namespace Webkul\HistoryControl\Listeners;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Repository\Events\RepositoryEventBase;

class History
{
    public RepositoryInterface $repository;

    /**
     * @var Model|mixed[]
     */
    public Model|array $model;

    public string $action;

    public function handle(RepositoryEventBase $event): void
    {
        $this->repository = $event->getRepository();
        $this->model = $event->getModel();
        $this->action = $event->getAction();

    }
}
