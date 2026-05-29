<?php

declare(strict_types=1);

namespace Webkul\Theme\Exceptions;

class ThemeNotFound extends \Exception
{
    /**
     * Create an instance.
     *
     * @param  string  $theme
     */
    public function __construct($themeName)
    {
        parent::__construct("Theme $themeName not Found", 1);
    }
}
