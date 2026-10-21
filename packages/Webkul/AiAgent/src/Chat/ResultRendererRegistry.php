<?php

namespace Webkul\AiAgent\Chat;

use InvalidArgumentException;

/**
 * Only installed server code registers component/view names. Descriptors are inert references.
 *
 * @phpstan-type RendererRegistration array{component: string, view: string, consumesContext: string|null}
 * @phpstan-type ResultDescriptor array{type: string, id: string}
 */
class ResultRendererRegistry
{
    /** @var array<string, RendererRegistration> */
    private array $renderers = [];

    /** Register trusted component/view names and an optional composer context to clear. */
    public function register(string $type, string $component, string $view, ?string $consumesContext = null): void
    {
        if (! preg_match('/^[a-z][a-z0-9.-]{0,63}$/D', $type)
            || ! preg_match('/^v-[a-z0-9-]{1,125}$/D', $component)
            || ($consumesContext !== null && ! preg_match(ExtensionRegistry::NAMESPACE_PATTERN, $consumesContext))) {
            throw new InvalidArgumentException('Invalid result renderer registration.');
        }

        $this->renderers[$type] = compact('component', 'view', 'consumesContext');
    }

    /** @return array<string, string> */
    public function components(): array
    {
        return array_map(fn ($r) => $r['component'], $this->renderers);
    }

    /** @return array<string, string> */
    public function contextConsumers(): array
    {
        return array_filter(array_map(fn ($r) => $r['consumesContext'], $this->renderers));
    }

    /** @return array<int, string> */
    public function views(): array
    {
        return array_unique(array_column($this->renderers, 'view'));
    }

    /** @return ResultDescriptor|null An inert reference; the renderer authorizes access. */
    public function descriptor(mixed $value): ?array
    {
        if (! is_array($value) || count($value) !== 2
            || ! is_string($value['type'] ?? null) || ! is_string($value['id'] ?? null)
            || ! preg_match('/^[a-z][a-z0-9.-]{0,63}$/D', $value['type'])
            || ! preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $value['id'])) {
            return null;
        }

        return ['type' => $value['type'], 'id' => $value['id']];
    }
}
