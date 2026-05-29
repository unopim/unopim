<?php

declare(strict_types=1);

namespace Webkul\Product\Presenters;

use Webkul\HistoryControl\Presenters\JsonDataPresenter;

class ProductValuesPresenter extends JsonDataPresenter
{
    public static array $sections = [
        'locale_specific',
        'channel_specific',
        'channel_locale_specific',
        'associations',
    ];

    public static array $otherSections = [
        'categories',
    ];
}
