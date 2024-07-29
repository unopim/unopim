<?php

namespace Webkul\HistoryControl\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\History as HistoryContract;

class History extends Model implements HistoryContract
{
    protected $fillable = [];
}
