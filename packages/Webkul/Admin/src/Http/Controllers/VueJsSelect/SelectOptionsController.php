<?php

namespace Webkul\Admin\Http\Controllers\VueJsSelect;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Requests\SelectOptionsForm;

class SelectOptionsController extends AbstractOptionsController
{
    public function getOptions(SelectOptionsForm $request): JsonResponse
    {
        $entityName = $request->validated('entityName');
        $page = $request->validated('page') ?? 1;
        $limit = (int) ($request->validated('limit') ?? self::DEFAULT_PER_PAGE);
        $query = (string) ($request->validated('query') ?? '');
        $queryParams = $request->except(['page', 'query', 'entityName']);

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
