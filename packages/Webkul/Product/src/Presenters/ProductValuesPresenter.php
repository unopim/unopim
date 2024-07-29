<?php

namespace Webkul\Product\Presenters;

use Webkul\HistoryControl\Presenters\JsonDataPresenter;

class ProductValuesPresenter extends JsonDataPresenter
{
    public static $sections = [
        'locale_specific',
        'channel_specific',
        'channel_locale_specific',
        'associations',
    ];

    public static $otherSections = [
        'categories',
    ];
}
