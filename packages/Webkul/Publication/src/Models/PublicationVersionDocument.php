<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Publication\Contracts\PublicationVersionDocument as PublicationVersionDocumentContract;

#[Fillable(['publication_version_id', 'publication_id', 'path'])]
#[Table(name: 'publication_version_documents')]
class PublicationVersionDocument extends Model implements PublicationVersionDocumentContract
{
    public function version(): BelongsTo
    {
        return $this->belongsTo(PublicationVersionProxy::modelClass(), 'publication_version_id');
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }
}
