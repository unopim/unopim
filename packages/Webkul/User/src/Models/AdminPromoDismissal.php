<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\AdminPromoDismissal as AdminPromoDismissalContract;

#[Fillable([
    'admin_id',
    'banner',
    'version',
])]
#[Table(name: 'admin_promo_dismissals')]
class AdminPromoDismissal extends Model implements AdminPromoDismissalContract {}
