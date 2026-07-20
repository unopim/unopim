<?php

namespace Webkul\Webhook\Registry;

/**
 * Runtime catalog of webhook-subscribable events, grouped by entity.
 *
 * Any package can register its own events from its service provider boot:
 *
 *   app(EventRegistry::class)->register('order', [
 *       'order.created' => 'shop::app.webhook-events.order.created',
 *   ]);
 *
 * The built-in product events are seeded from config('webhook.events'); the
 * registry — not the config array — is the source of truth every consumer
 * (form, validation, delivery) reads, so third-party events participate on
 * equal footing without touching core.
 */
class EventRegistry
{
    /**
     * @var array<string, array<string, string>> entity => [eventKey => translationKey]
     */
    protected array $groups = [];

    /**
     * @param  array<string, array<string, string>>  $seed
     */
    public function __construct(array $seed = [])
    {
        foreach ($seed as $entity => $events) {
            $this->register((string) $entity, (array) $events);
        }
    }

    /**
     * Register (or extend) an entity's events.
     *
     * @param  array<string, string>  $events  eventKey => translation key
     */
    public function register(string $entity, array $events): self
    {
        $this->groups[$entity] = array_merge($this->groups[$entity] ?? [], $events);

        return $this;
    }

    /**
     * All registered groups: entity => [eventKey => translationKey].
     *
     * @return array<string, array<string, string>>
     */
    public function groups(): array
    {
        return $this->groups;
    }

    /**
     * Flat list of every registered event key.
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        $keys = [];

        foreach ($this->groups as $events) {
            $keys = array_merge($keys, array_keys($events));
        }

        return array_values(array_unique($keys));
    }

    /**
     * Whether an event key is registered.
     */
    public function has(string $event): bool
    {
        return in_array($event, $this->keys(), true);
    }

    /**
     * Groups shaped for a select/multiselect payload.
     *
     * @return array<int, array{entity: string, options: array<int, array{id: string, label: string}>}>
     */
    public function forSelect(): array
    {
        $result = [];

        foreach ($this->groups as $entity => $events) {
            $options = [];

            foreach ($events as $key => $translationKey) {
                $options[] = ['id' => $key, 'label' => trans($translationKey)];
            }

            $result[] = ['entity' => $entity, 'options' => $options];
        }

        return $result;
    }
}
