<?php

namespace Webkul\HistoryControl\Repositories;

use OwenIt\Auditing\Models\Audit;
use Webkul\Core\Eloquent\Repository;

class AuditRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Audit::class;
    }

    /**
     * Get version changed data
     */
    public function getVersionDataByNameIdVersionId(string $entityName, int $id, int $versionId)
    {
        $versionData = $this->model->query()
            ->leftJoin('admins', 'admins.id', '=', 'audits.user_id')
            ->select(
                'audits.id',
                'audits.tags as entity_type',
                'audits.event',
                'admins.name as user',
                'audits.updated_at',
                'audits.version_id',
                'audits.old_values',
                'audits.new_values',
                'audits.auditable_type'
            )
            ->where('audits.tags', '=', $entityName)
            ->where('audits.version_id', $versionId)
            ->where('audits.history_id', $id);

        return $versionData->get();
    }
}
