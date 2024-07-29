<?php

namespace Webkul\HistoryControl\Listeners;

use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;

class ProxyValueSyncEventListener
{
    const EVENT_KEY = 'core.model.proxy.sync.';

    const AUDIT_EVENT = 'updated';

    /**
     * Handle the event.
     */
    public function handle($event, $data): void
    {
        $oldValues = $data['old_values'];

        $newValues = $data['new_values'];

        $model = $data['model'];

        if ($model instanceof HistoryContract) {

            $eventName = ucfirst(substr($event, strlen(self::EVENT_KEY)));

            if ($oldValues !== $newValues) {
                $model->auditCustomOld['common'][$eventName] = $oldValues;
                $model->auditCustomNew['common'][$eventName] = $newValues;
            }

            if (! empty($model->auditCustomOld) || ! empty($model->auditCustomNew)) {
                $model->auditEvent = self::AUDIT_EVENT;
                $model->isCustomEvent = true;

                Event::dispatch(AuditCustom::class, [$model]);
            }
        }
    }
}
