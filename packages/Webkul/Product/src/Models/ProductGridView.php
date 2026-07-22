<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Contracts\ProductGridView as ProductGridViewContract;
use Webkul\User\Models\AdminProxy;

#[Fillable([
    'admin_id',
    'name',
    'is_shared',
    'payload',
])]
#[Table(name: 'product_grid_views')]
class ProductGridView extends Model implements ProductGridViewContract
{
    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
            'payload'   => 'array',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'admin_id');
    }
}
