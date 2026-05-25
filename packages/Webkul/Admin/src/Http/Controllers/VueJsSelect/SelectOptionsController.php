<?php

namespace Webkul\Admin\Http\Controllers\VueJsSelect;

use Illuminate\Http\JsonResponse;

class SelectOptionsController extends AbstractOptionsController
{
    public function getOptions(): JsonResponse
    {
        $entityName = request()->input('entityName');
        $page = request()->input('page');
        $limit = request()->input('limit', self::DEFAULT_PER_PAGE);
        $query = request()->input('query') ?? '';
        $queryParams = request()->except(['page', 'query', 'entityName']);

        $options = $this->getOptionsByParams($entityName, $page, $query, $queryParams, $limit);
        $currentLocaleCode = core()->getRequestedLocaleCode();
        $formattedOptions = [];

        foreach ($options as $option) {
            $formattedOptions[] = $this->formatOption($option, $currentLocaleCode, $entityName ?? '');
        }

        return new JsonResponse([
            'options'  => $formattedOptions,
            'page'     => $options->currentPage(),
            'lastPage' => $options->lastPage(),
        ]);

    }
}
