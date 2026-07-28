<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Product\Contracts\ProductAssociation as ProductAssociationContract;

class ProductAssociation extends Model implements ProductAssociationContract
{
    protected $fillable = [
        'product_id',
        'association_type_id',
        'related_product_id',
        'position',
        'additional_data',
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'related_product_id');
    }

    public function associationType(): BelongsTo
    {
        return $this->belongsTo(AssociationTypeProxy::modelClass());
    }
}
