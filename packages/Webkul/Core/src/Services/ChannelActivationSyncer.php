<?php

namespace Webkul\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\CurrencyProxy;
use Webkul\Core\Models\LocaleProxy;

/**
 * Keeps locale and currency activation aligned with the channels they are attached to.
 *
 * Attaching activates, detaching deactivates only when nothing else still depends on the row.
 */
class ChannelActivationSyncer
{
    /**
     * Activate the attached locales and deactivate the detached ones that became unused.
     *
     * Dispatches `core.locale.activation.synced` with `['enabled' => int[], 'disabled' => int[]]`
     * holding the ids whose status actually changed.
     *
     * @param  int[]  $attachedIds
     * @param  int[]  $detachedIds
     */
    public function syncLocales(array $attachedIds, array $detachedIds): void
    {
        [$attachedIds, $detachedIds] = $this->reconcile($attachedIds, $detachedIds);

        if ($attachedIds === [] && $detachedIds === []) {
            return;
        }

        $modelClass = LocaleProxy::modelClass();

        $enabled = $attachedIds === []
            ? []
            : $this->activate($modelClass::query()->whereIn('id', $attachedIds));

        $disabled = $detachedIds === []
            ? []
            : $this->deactivate(
                $modelClass::query()
                    ->whereIn('id', $detachedIds)
                    ->whereDoesntHave('channel')
                    ->whereDoesntHave('user'),
                config('app.locale')
            );

        if ($enabled === [] && $disabled === []) {
            return;
        }

        Event::dispatch('core.locale.activation.synced', ['enabled' => $enabled, 'disabled' => $disabled]);
    }

    /**
     * Activate the attached currencies and deactivate the detached ones that became unused.
     *
     * Dispatches `core.currency.activation.synced` with `['enabled' => int[], 'disabled' => int[]]`
     * holding the ids whose status actually changed.
     *
     * @param  int[]  $attachedIds
     * @param  int[]  $detachedIds
     */
    public function syncCurrencies(array $attachedIds, array $detachedIds): void
    {
        [$attachedIds, $detachedIds] = $this->reconcile($attachedIds, $detachedIds);

        if ($attachedIds === [] && $detachedIds === []) {
            return;
        }

        $modelClass = CurrencyProxy::modelClass();

        $enabled = $attachedIds === []
            ? []
            : $this->activate($modelClass::query()->whereIn('id', $attachedIds));

        $disabled = $detachedIds === []
            ? []
            : $this->deactivate(
                $modelClass::query()
                    ->whereIn('id', $detachedIds)
                    ->whereDoesntHave('channel'),
                strtoupper((string) config('app.currency'))
            );

        if ($enabled === [] && $disabled === []) {
            return;
        }

        Event::dispatch('core.currency.activation.synced', ['enabled' => $enabled, 'disabled' => $disabled]);
    }

    /**
     * Ids present on both sides are attachments, so they must never be considered for deactivation.
     *
     * @return array{0: int[], 1: int[]}
     */
    protected function reconcile(array $attachedIds, array $detachedIds): array
    {
        $attachedIds = $this->normalizeIds($attachedIds);

        return [$attachedIds, array_values(array_diff($this->normalizeIds($detachedIds), $attachedIds))];
    }

    /**
     * @return int[]
     */
    protected function normalizeIds(array $ids): array
    {
        $ids = array_filter($ids, static fn ($id): bool => is_numeric($id));

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Flip the inactive rows matched by the query to active in a single statement.
     *
     * @return int[]
     */
    protected function activate(Builder $query): array
    {
        $ids = $query->where('status', 0)->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        $query->getModel()->newQuery()->whereIn('id', $ids)->update(['status' => 1]);

        return $ids;
    }

    /**
     * Flip the active rows matched by the query to inactive in a single statement.
     *
     * @return int[]
     */
    protected function deactivate(Builder $query, ?string $protectedCode): array
    {
        $protectedCodes = array_values(array_filter([$protectedCode], static fn (?string $code): bool => (string) $code !== ''));

        $ids = $query->where('status', 1)
            ->when($protectedCodes !== [], fn (Builder $builder) => $builder->whereNotIn('code', $protectedCodes))
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return [];
        }

        $query->getModel()->newQuery()->whereIn('id', $ids)->update(['status' => 0]);

        return $ids;
    }
}
