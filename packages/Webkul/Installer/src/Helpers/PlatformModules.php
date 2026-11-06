<?php

namespace Webkul\Installer\Helpers;

use InvalidArgumentException;
use Webkul\Core\Models\CoreConfigProxy;
use Webkul\Core\Platform\ModuleGate;

class PlatformModules
{
    public function __construct(protected ModuleGate $moduleGate) {}

    /**
     * The platform module keys this install can pick from.
     *
     * @return list<string>
     */
    public function options(): array
    {
        return $this->moduleGate->registered();
    }

    /**
     * Split a comma-separated selection into module keys.
     *
     * @return list<string>
     */
    public function parse(string $modules): array
    {
        return ModuleGate::parseModules($modules);
    }

    /**
     * Translated reasons a selection cannot be stored; empty when it is valid.
     *
     * @param  list<string>  $modules
     * @return list<string>
     */
    public function errors(array $modules): array
    {
        return $this->moduleGate->selectionErrors($modules);
    }

    /**
     * Persist the platform module selection.
     *
     * @param  list<string>  $modules
     *
     * @throws InvalidArgumentException
     */
    public function store(array $modules): void
    {
        $errors = $this->errors($modules);

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        CoreConfigProxy::modelClass()::query()->updateOrCreate(
            [
                'code'         => ModuleGate::CONFIG_KEY,
                'channel_code' => null,
                'locale_code'  => null,
            ],
            ['value' => implode(',', $modules)]
        );
    }
}
