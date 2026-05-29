<?php

namespace Webkul\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Webkul\Core\Repositories\VisitRepository;

class UpdateCreateVisitIndex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?Model $model,
        protected array $log
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $visitRepository = app(VisitRepository::class);

        $lastVisit = $visitRepository->where(Arr::only($this->log, [
            'method',
            'url',
            'ip',
            'visitor_id',
            'visitor_type',
        ]))->latest()->first();

        if ($lastVisit?->created_at->isToday()) {
            return;
        }

        if ($this->model instanceof Model && method_exists($this->model, 'visitLogs')) {
            $this->model->visitLogs()->create($this->log);
        } else {
            $visitRepository->create($this->log);
        }
    }
}
