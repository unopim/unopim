<?php

namespace Webkul\HistoryControl\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\History as HistoryContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class History extends Model implements HistoryContract
{
    use BelongsToTenant;

    protected $fillable = [];
}
