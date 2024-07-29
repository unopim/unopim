<?php

namespace Webkul\HistoryControl\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OwenIt\Auditing\Contracts\Audit;
use Webkul\HistoryControl\DataGrids\HistoryDataGrid;
use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Repositories\AuditRepository;

class HistoryController extends Controller
{
    public function get(string $entityName, int $id)
    {
        if (request()->ajax()) {
            return app(HistoryDataGrid::class)->setEntityName($entityName)->setEntityId($id)->toJson();
        }
    }

    public function getHistoryView(string $entityName, int $id)
    {
        // Your code here
    }

    public function getVersionHistoryView(string $entityName, int $id, int $versionId)
    {
        $versionData = app(AuditRepository::class)->getVersionDataByNameIdVersionId($entityName, $id, $versionId);

        $normalizedData = $this->normalize($versionData);

        return new JsonResponse($normalizedData);
    }

    /**
     * Normalized the data
     *
     *  [
     *      'version'   => '1',
     *      'dateTime'  => 'Thu, 30-05-2024 12:57:59 (8 hours ago)',
     *      'user'      => 'Example',
     *      'versionHistory' => [
     *          [
     *              'name'      => 'Name',
     *              'old'       => 'Default',
     *              'new'       => 'Bottle New',
     *          ],
     *          [
     *              'name'      => 'Root Category',
     *              'old'       => '\'\'',
     *              'new'       => 'Root',
     *          ],
     * ],
     */
    protected function normalize(Collection $items): array
    {
        $normalizedData = [
            'version'        => $items[0]->version_id,
            'dateTime'       => core()->formatDateWithTimeZone($items[0]->updated_at, 'D, d-m-Y H:i:s'),
            'user'           => $items[0]->user,
            'versionHistory' => [],
        ];

        foreach ($items as $item) {
            $oldValues = $item->old_values;
            $newValues = $item->new_values;

            $presenters = $this->getPresenters($item);

            foreach ($presenters as $fieldName => $presenter) {
                if (
                    (
                        ! empty($oldValues[$fieldName])
                        || ! empty($newValues[$fieldName])
                    )
                    && in_array(HistoryPresenterInterface::class, class_implements($presenter))
                ) {
                    $presentedValues = $presenter::representValueForHistory(
                        oldValues: ($oldValues[$fieldName] ?? []),
                        newValues: ($newValues[$fieldName] ?? []),
                        fieldName: $fieldName
                    );

                    if (! empty($presentedValues)) {
                        $normalizedData['versionHistory'] += $presentedValues;

                        unset($oldValues[$fieldName]);

                        unset($newValues[$fieldName]);
                    }
                }
            }

            foreach ($oldValues as $locale => $oldValue) {
                if (is_array($oldValue)) {
                    foreach ($oldValue as $name => $value) {
                        if ($locale !== 'common') {
                            $name .= ' ('.$locale.')';
                        }

                        $normalizedData['versionHistory'][$name] = [
                            'name' => $name,
                            'old'  => is_array($value) ? implode(', ', $value) : $value,
                        ];
                    }
                } else {
                    $name = $locale;
                    $normalizedData['versionHistory'][$name] = [
                        'name' => $name,
                        'old'  => $oldValue,
                    ];
                }
            }

            foreach ($newValues as $locale => $newValue) {
                if (is_array($newValue)) {
                    foreach ($newValue as $name => $value) {
                        if ($locale !== 'common') {
                            $name .= ' ('.$locale.')';
                        }

                        $normalizedData['versionHistory'][$name]['new'] = is_array($value) ? implode(', ', $value) : $value;

                        $normalizedData['versionHistory'][$name]['name'] = $name;
                    }
                } else {
                    $name = $locale;
                    $normalizedData['versionHistory'][$name]['new'] = $newValue;

                    $normalizedData['versionHistory'][$name]['name'] = $name;
                }
            }
        }

        return $normalizedData;
    }

    public function restoreHistory(string $entityName, int $id)
    {
        // Your code here
    }

    public function deleteHistory(string $entityName, int $id)
    {
        // Your code here
    }

    /**
     * Gets any value Presenters for columns that may need different way to normalize history data
     */
    private function getPresenters(Audit $item): array
    {
        if (
            in_array(PresentableHistoryInterface::class, class_implements($item->auditable_type))
        ) {
            return $item->auditable_type::getPresenters();
        }

        return [];
    }
}
