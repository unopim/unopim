<?php

namespace Webkul\HistoryControl\Interfaces;

interface PresentableHistoryInterface
{
    /**
     * Define any custom presenters to be used while displaying the history for that column
     *
     * return format
     *
     * [
     *     'additional_data' => JsonDataPresenter::class,
     * ]
     */
    public static function getPresenters(): array;
}
