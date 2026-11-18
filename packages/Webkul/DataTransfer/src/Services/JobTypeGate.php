<?php

namespace Webkul\DataTransfer\Services;

use Webkul\Core\Platform\ModuleGate;
use Webkul\DataTransfer\Contracts\JobTrack;

/**
 * Decides which import/export job types are reachable in the current platform mode.
 *
 * Stateless: the mode is read from the scoped ModuleGate on every call, so admin
 * requests, console commands and queue workers all see the current setting.
 */
class JobTypeGate
{
    /**
     * Registries whose entries carry an optional `module` tag.
     */
    public const array REGISTRIES = ['importers', 'exporters', 'quick_exporters'];

    /**
     * The job types of a registry (`importers`, `exporters`, `quick_exporters`) whose module is switched on.
     *
     * @return array<array-key, mixed>
     */
    public function visible(string $registry): array
    {
        return app(ModuleGate::class)->filter((array) config($registry, []));
    }

    /**
     * The job type keys of a registry whose module is switched off.
     *
     * @return list<array-key>
     */
    public function hidden(string $registry): array
    {
        $types = (array) config($registry, []);

        return array_keys(array_diff_key($types, app(ModuleGate::class)->filter($types)));
    }

    /**
     * Whether an entity type belongs to a switched-off module in any registry.
     *
     * Types missing from the registries are never hidden, so jobs of an uninstalled
     * extension keep their current behaviour.
     */
    public function hides(mixed $entityType): bool
    {
        if (! is_string($entityType) || $entityType === '') {
            return false;
        }

        return array_any(
            self::REGISTRIES,
            fn (string $registry): bool => in_array($entityType, $this->hidden($registry), true),
        );
    }

    /**
     * Whether a job track runs a job instance whose type is hidden.
     */
    public function hidesTrack(JobTrack $track): bool
    {
        return $this->hides($track->jobInstance?->entity_type);
    }
}
