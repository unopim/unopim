<?php

namespace Webkul\Core\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Shetabit\Visitor\Models\Visit;

class UpdateCreateVisitIndex implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Model  $model
     * @param  array  $log
     */
    public function __construct(
        protected $model,
        protected $log
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $lastVisit = Visit::query()->where(Arr::only($this->log, [
            'method',
            'url',
            'ip',
            'visitor_id',
            'visitor_type',
        ]))->latest()->first();

        if ($lastVisit?->created_at->isToday()) {
            return;
        }

        if ($this->model !== null && method_exists($this->model, 'visitLogs')) {
            $this->model->visitLogs()->create($this->log);
        } else {
            Visit::query()->create($this->log);
        }
    }
}
