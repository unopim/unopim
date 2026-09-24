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
    public function after()
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $managedPlatform = app(ManagedPlatform::class);

                $apiKey = $this->input('api_key');
                $provider = $this->input('provider');
                $apiUrl = $this->input('api_url');

                $violations = $managedPlatform->violations(
                    $this->managedStoredPlatform(),
                    $managedPlatform->usesStoredKey(is_string($apiKey) ? $apiKey : null),
                    is_string($provider) ? $provider : null,
                    is_string($apiUrl) ? $apiUrl : null,
                    $this->managedSubmittedModels(),
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
    protected function managedStoredPlatform(): ?MagicAIPlatform
    {
        $platformId = (int) ($this->route('id') ?? $this->input('id'));

        return $platformId > 0
            ? app(MagicAIPlatformRepository::class)->find($platformId)
            : null;
    }

    /**
     * @return string[]
     */
    protected function managedSubmittedModels(): array
    {
        $models = $this->input('models');

        return array_values(array_filter(array_map(
            static fn (string $model): string => ltrim(trim($model), '~'),
            explode(',', is_string($models) ? $models : '')
        )));
    }
}
