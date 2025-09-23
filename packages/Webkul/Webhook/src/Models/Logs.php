<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    use HasFactory;

    protected $table = 'webhook_logs';

    protected $id = 2;

    public $timestamps = true;

    protected $fillable = [
        'sku',
        'user',
        'status',
        'extra',
    ];

    protected $casts = [
        'extra' => 'jsonResponse',
    ];
}
