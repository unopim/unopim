<?php

namespace Webkul\HistoryControl\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class HistoryDataGrid extends DataGrid
{
    /**
     * Datagrid entity name
     */
    protected $entityName;

    /**
     * Datagrid entity id
     */
    protected $entityId;

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        if (! $this->entityName) {
            throw new \Exception('entityName cannot be empty');
        }

        if (! $this->entityId) {
            throw new \Exception('entityId cannot be empty');
        }

        $queryBuilder = DB::table('audits as his')
            ->leftJoin('admins', 'his.user_id', '=', 'admins.id')
            ->select('his.id', 'his.tags as entity_type', 'his.event', 'admins.name as user', 'his.updated_at', 'his.version_id')
            ->where(function ($query) {
                $query->where('his.tags', '=', $this->entityName)
                    ->where('his.history_id', '=', $this->entityId);
            })
            ->groupBy('his.updated_at', 'his.user_id');

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.catalog.products.index.datagrid.id'),
            'type'       => 'id',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'updated_at',
            'label'      => trans('admin::app.catalog.history.index.datagrid.date_time'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                return '<span class="icon-calendar"> '.core()->formatDateWithTimeZone($row->updated_at, 'D, d-m-Y H:i:s').' ('.$this->calculateTimeAgo($row->updated_at).')</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'version_id',
            'label'      => trans('admin::app.catalog.history.index.datagrid.version'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'user',
            'label'      => trans('admin::app.catalog.history.index.datagrid.user'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                return '<span class="icon-user">'.$row->user.'</span>';
            },
        ]);

    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('history.view')) {
            $this->addAction([
                'icon'   => 'icon-view',
                'title'  => trans('admin::app.catalog.history.view'),
                'method' => 'GET',
                'url'    => function ($row) {
                    if ($row->version_id) {
                        return route('admin.history.version.view', [$this->getEntityName(), $this->getEntityId(), $row->version_id]);
                    }
                },
                'frontend_view'  => 'view-modal',
            ]);
        }
    }

    /**
     * Calculate time ago.
     */
    public function calculateTimeAgo($dateTime)
    {
        $time = strtotime($dateTime);
        $current = time();
        $diff = $current - $time;

        $second = 1;
        $minute = 60;
        $hour = 60 * 60;
        $day = 24 * 60 * 60;
        $month = 30 * 24 * 60 * 60;
        $year = 365 * 24 * 60 * 60;

        if ($diff < $minute) {
            $ago = round($diff / $second);
            $timeUnit = $ago > 1 ? 'seconds' : 'second';

            return $ago." $timeUnit ago";
        } elseif ($diff < $hour) {
            $ago = round($diff / $minute);
            $timeUnit = $ago > 1 ? 'minutes' : 'minute';

            return $ago." $timeUnit ago";
        } elseif ($diff < $day) {
            $ago = round($diff / $hour);
            $timeUnit = $ago > 1 ? 'hours' : 'hour';

            return $ago." $timeUnit ago";
        } elseif ($diff < $month) {
            $ago = round($diff / $day);
            $timeUnit = $ago > 1 ? 'days' : 'day';

            return $ago." $timeUnit ago";
        } elseif ($diff < $year) {
            $ago = round($diff / $month);
            $timeUnit = $ago > 1 ? 'months' : 'month';

            return $ago." $timeUnit ago";
        } else {
            $ago = round($diff / $year);
            $timeUnit = $ago > 1 ? 'years' : 'year';

            return $ago." $timeUnit ago";
        }
    }

    /**
     * To set the entity name
     */
    public function setEntityName(string $entityName): HistoryDataGrid
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * To get the entity name
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function formatData(): array
    {
        $formatData = parent::formatData();

        $formatData['meta']['entity_type'] = $this->getEntityName();

        return $formatData;
    }

    /**
     * To set the entity id
     */
    public function setEntityId(string $entityId): HistoryDataGrid
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * To get the entity id
     */
    public function getEntityId()
    {
        return $this->entityId;
    }
}
