<?php

namespace Webkul\Theme\Exceptions;

class ThemeNotFound extends \Exception
{
    /**
     * Create an instance.
     *
     * @param  string  $themeName
     */
    public function __construct($themeName)
    {
        parent::__construct("Theme $themeName not Found", 1);
    }
}
