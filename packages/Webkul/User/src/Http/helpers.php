<?php

use Webkul\User\Bouncer;

if (! function_exists('bouncer')) {
    function bouncer()
    {
        return app()->make(Bouncer::class);
    }
}
