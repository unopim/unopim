<?php

namespace Webkul\Completeness\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AttributeCompletenessDataGrid extends DataGrid
{
    protected $familyId;

    public function setAttributeFamilyId($familyId): self
    {
        $this->familyId = $familyId;

        return $this;
    }

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $locale = core()->getRequestedLocaleCode();
        $familyId = $this->familyId;
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('attributes')
            ->join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->join('attribute_family_group_mappings', function ($join) use ($familyId) {
                $join->on('attribute_group_mappings.attribute_family_group_id', '=', 'attribute_family_group_mappings.id')
                    ->where('attribute_family_group_mappings.attribute_family_id', '=', $familyId);
            })
            ->join('attribute_groups', 'attribute_family_group_mappings.attribute_group_id', '=', 'attribute_groups.id')
            ->leftJoin('completeness_settings', function ($join) use ($familyId) {
                $join->on('attributes.id', '=', 'completeness_settings.attribute_id')
                    ->where('completeness_settings.family_id', '=', $familyId);
            })
            ->leftJoin('channels', 'completeness_settings.channel_id', '=', 'channels.id')
            ->join('attribute_translations', function ($join) use ($locale) {
                $join->on('attributes.id', '=', 'attribute_translations.attribute_id')
                    ->where('attribute_translations.locale', '=', $locale);
            })
            ->select(
                'attributes.id',
                'attributes.code',
                DB::raw("
                    CASE
                        WHEN {$tablePrefix}attribute_translations.name IS NULL OR CHAR_LENGTH(TRIM({$tablePrefix}attribute_translations.name)) < 1
                        THEN CONCAT('[', {$tablePrefix}attributes.code, ']')
                        ELSE {$tablePrefix}attribute_translations.name
                    END AS name
                "),
                DB::raw(DB::rawQueryGrammar()->groupConcat($tablePrefix.'channels.code', 'channel_required', $tablePrefix.'channels.code', true)),
            )
            ->groupBy(
                'attributes.id',
                'attributes.code',
                'attribute_translations.name'
            );

        $this->addFilter('id', 'attributes.id');
        $this->addFilter('code', 'attributes.code');
        $this->addFilter('name', 'attribute_translations.name');
        $this->addFilter('channel_required', 'channels.code');

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
            'index'      => 'code',
            'label'      => trans('completeness::app.catalog.families.edit.completeness.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('completeness::app.catalog.families.edit.completeness.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'channel_required',
            'label'      => trans('completeness::app.catalog.families.edit.completeness.datagrid.channel-required'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions() {}

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        $this->addMassAction([
            'type'    => 'edit',
            'title'   => trans('completeness::app.catalog.families.edit.completeness.datagrid.actions.change-requirement'),
            'url'     => route('admin.catalog.families.completeness.mass_update'),
            'method'  => 'POST',
            'options' => [
                'modal' => 'open-completeness-required-modal',
            ],
        ]);
    }
}
