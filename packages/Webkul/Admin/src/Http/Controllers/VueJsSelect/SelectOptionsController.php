<?php

namespace Webkul\Admin\Http\Controllers\VueJsSelect;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SelectOptionsController extends AbstractOptionsController
{
    public function getOptions()
    {
        $entityName = request()->get('entityName');
        $page = request()->get('page');
        $limit = request()->get('limit', self::DEFAULT_PER_PAGE);
        $query = request()->get('query') ?? '';
        $queryParams = request()->except(['page', 'query', 'entityName']);
        
        $options = $this->getOptionsByParams($entityName, $page, $query, $queryParams, $limit);
        $currentLocaleCode = core()->getRequestedLocaleCode();
        $formattedOptions = [];

        foreach ($options as $option) {
            $formattedOptions[] = $this->formatOption($option, $currentLocaleCode);
        }

        return new JsonResponse([
            'options'  => $formattedOptions,
            'page'     => $options->currentPage(),
            'lastPage' => $options->lastPage(),
        ]);

    }
}