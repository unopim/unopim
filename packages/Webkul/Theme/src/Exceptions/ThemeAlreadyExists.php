<?php

declare(strict_types=1);

namespace Webkul\Theme\Exceptions;

use Webkul\Theme\Theme;

class ThemeAlreadyExists extends \Exception
{
    /**
     * Create an instance.
     *
     * @param  Theme  $theme
     */
    public function __construct($theme)
    {
        parent::__construct("Theme {$theme->name} already exists", 1);
    }
}
