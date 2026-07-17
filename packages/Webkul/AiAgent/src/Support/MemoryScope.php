<?php

namespace Webkul\AiAgent\Support;

/**
 * Channel scoping for agent-memory queries.
 *
 * Catalog-scope memories written after the `channel` column was introduced
 * carry the channel they were learned in. Legacy rows (and user/global
 * memories) have a null channel and remain visible on every channel.
 */
class MemoryScope
{
    /**
     * Constrain a memories query to (channel = ? OR channel IS NULL).
     *
     * @template TQuery of \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public static function apply($query, ?string $channel)
    {
        return $query->where(function ($q) use ($channel) {
            $q->whereNull('channel');

            if ($channel !== null && $channel !== '') {
                $q->orWhere('channel', $channel);
            }
        });
    }
}
