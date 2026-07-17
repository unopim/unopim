<?php

namespace Webkul\Completeness\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\Notification\Events\NotificationEvent;
use Webkul\Product\Repositories\ProductRepository;

class BulkProductCompletenessJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    protected const CHUNK_SIZE = 100;

    protected const BATCH_SIZE = 1000;

    protected $productRepository;

    public $tries = 3;

    public $uniqueFor = 300;

    public function __construct(
        protected array $product = [],
        protected ?int $familyId = null,
        protected ?int $userId = null,
    ) {
        $this->queue = config('completeness.queue', 'system');
    }

    public function uniqueId(): string
    {
        if (is_null($this->familyId)) {
            return uniqid('completeness-job-', true);
        }

        return 'completeness-job-'.$this->familyId;
    }

    public function handle(): void
    {
        $this->productRepository = resolve(ProductRepository::class);

        try {
            $allProductIds = $this->collectProductIds();

            if ($allProductIds === []) {
                return;
            }

            $jobs = $this->buildChunkedJobs($allProductIds);

            if ($jobs === []) {
                return;
            }

            $totalProducts = count($allProductIds);
            $userId = $this->userId;
            $familyId = $this->familyId;

            Bus::batch($jobs)
                ->name('completeness-calculation'.($familyId ? "-family-{$familyId}" : ''))
                ->onQueue(config('completeness.queue', 'system'))
                ->then(function (Batch $batch) use ($totalProducts, $userId, $familyId): void {
                    static::sendCompletionNotification($totalProducts, $userId, $familyId);
                })
                ->catch(function (Batch $batch, Throwable $e): void {
                    logger()->error('Completeness batch failed: '.$e->getMessage());
                })
                ->allowFailures()
                ->dispatch();

        } catch (Throwable $e) {
            logger()->error($e);
        }
    }

    protected function collectProductIds(): array
    {
        if ($this->familyId) {
            return $this->getFamilyProductIds();
        }

        return $this->product;
    }

    protected function getFamilyProductIds(): array
    {
        $allIds = [];

        $this->productRepository
            ->select('id')
            ->where('attribute_family_id', $this->familyId)
            ->orderBy('id')
            ->chunkById(self::BATCH_SIZE, function ($products) use (&$allIds): void {
                foreach ($products as $product) {
                    $allIds[] = $product->id;
                }
            });

        return $allIds;
    }

    protected function buildChunkedJobs(array $productIds): array
    {
        $jobs = [];

        foreach (array_chunk($productIds, self::CHUNK_SIZE) as $chunk) {
            $jobs[] = new ProductCompletenessJob($chunk);
        }

        return $jobs;
    }

    public static function sendCompletionNotification(int $totalProducts, ?int $userId, ?int $familyId): void
    {
        $userIds = [];
        $userEmails = [];

        if ($userId) {
            $admin = (array) DB::table('admins')->find($userId);

            if ($admin !== []) {
                $userIds[] = $admin['id'];
                $userEmails[] = $admin['email'];
            }
        }

        if ($userIds === []) {
            $admins = DB::table('admins')
                ->join('roles', 'admins.role_id', '=', 'roles.id')
                ->where('roles.permission_type', 'all')
                ->select('admins.id', 'admins.email')
                ->get();

            $userIds = $admins->pluck('id')->toArray();
            $userEmails = $admins->pluck('email')->toArray();
        }

        if (empty($userIds)) {
            return;
        }

        $description = trans('completeness::app.notifications.completeness-calculated', [
            'count' => $totalProducts,
        ]);

        if ($familyId) {
            $familyCode = DB::table('attribute_families')->where('id', $familyId)->value('code');

            if ($familyCode !== null) {
                $description = trans('completeness::app.notifications.completeness-calculated-family', [
                    'count'  => $totalProducts,
                    'family' => $familyCode,
                ]);
            }
        }

        $mailConfigured = Config::get('mail.default')
            && Config::get('mail.mailers.smtp.host')
            && Config::get('mail.mailers.smtp.port')
            && Config::get('mail.mailers.smtp.username')
            && Config::get('mail.mailers.smtp.password');

        event(new NotificationEvent([
            'type'         => 'completeness',
            'title'        => trans('completeness::app.notifications.completeness-title'),
            'description'  => $description,
            'user_ids'     => $userIds,
            'mailable'     => $mailConfigured && config('notifications.enabled', true),
            'user_emails'  => $userEmails,
            'templateName' => 'completeness::emails.completeness-completed',
            'templateData' => [
                'totalProducts' => $totalProducts,
                'familyId'      => $familyId,
            ],
        ]));
    }
}
