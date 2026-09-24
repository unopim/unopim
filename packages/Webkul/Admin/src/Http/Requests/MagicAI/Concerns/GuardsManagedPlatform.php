<?php

namespace Webkul\Admin\Http\Requests\MagicAI\Concerns;

use Closure;
use Illuminate\Validation\Validator;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ManagedPlatform;
use Webkul\MagicAI\Services\ProviderOverrides;

trait GuardsManagedPlatform
{
    /**
     * Reject a submission that would send the managed platform's stored key
     * outside its provider, endpoint or model list.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $managedPlatform = app(ManagedPlatform::class);

                $apiKey = $this->input('api_key');

                $violations = $managedPlatform->violations(
                    $this->storedPlatform(),
                    $managedPlatform->usesStoredKey(is_string($apiKey) ? $apiKey : null),
                    $this->input('provider'),
                    $this->input('api_url'),
                    $this->submittedModels(),
                    ProviderOverrides::decode($this->input('extras')),
                );

                foreach ($violations as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }

    /**
     * The saved platform the submission refers to, if any.
     */
    protected function storedPlatform(): ?MagicAIPlatform
    {
        $platformId = (int) ($this->route('id') ?? $this->input('id'));

        return $platformId > 0
            ? app(MagicAIPlatformRepository::class)->find($platformId)
            : null;
    }

    /**
     * @return string[]
     */
    protected function submittedModels(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $model): string => ltrim(trim($model), '~'),
            explode(',', (string) $this->input('models'))
        )));
    }
}
