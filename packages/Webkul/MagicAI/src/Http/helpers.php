<?php

use Webkul\MagicAI\MagicAI;

if (! function_exists('magic_ai')) {
    /**
     * MagicAI helper.
     *
     * @return MagicAI
     */
    function magic_ai()
    {
        return resolve('magic_ai');
    }
}
