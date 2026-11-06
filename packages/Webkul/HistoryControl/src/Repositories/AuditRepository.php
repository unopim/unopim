<?php

namespace Webkul\HistoryControl\Repositories;

use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Get the latest audit entries of a history entity, newest first, each with its admin's name.
     *
     * @return Collection<int, Audit>
     */
    public function latestForHistory(string $entityName, int $id, int $limit = 10): Collection
    {
        return $this->model->query()
            ->leftJoin('admins', 'admins.id', '=', 'audits.user_id')
            ->where('audits.tags', '=', $entityName)
            ->where('audits.history_id', $id)
            ->orderByDesc('audits.id')
            ->limit($limit)
            ->get([
                'audits.id',
                'audits.event',
                'audits.version_id',
                'audits.old_values',
                'audits.new_values',
                'audits.created_at',
                'admins.name as user',
            ]);
    }

    /**
     * Delete every audit entry of the history entities.
     *
     * @param  list<int>  $ids
     */
    public function deleteForHistory(string $entityName, array $ids): void
    {
        $this->model->newQuery()
            ->where('tags', $entityName)
            ->whereIn('history_id', $ids)
            ->delete();
    }
}
