<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\AdminPromoDismissal as AdminPromoDismissalContract;

class AdminPromoDismissal extends Model implements AdminPromoDismissalContract
{
    protected $table = 'admin_promo_dismissals';

    protected $fillable = [
        'admin_id',
        'banner',
        'version',
    ];
}
