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

                if ($violations === []) {
                    $this->guardStoredKeyConnection($validator);
                }
            },
        ];
    }

    /**
     * Whether the submission stands for a saved platform's key by sending it
     * masked together with the platform id.
     */
    public function replaysStoredKey(): bool
    {
        $apiKey = $this->input('api_key');

        return is_string($apiKey)
            && preg_match('/^\*+$/', $apiKey) === 1
            && $this->filled('id');
    }

    /**
     * The endpoint the outbound call goes to. A user who cannot edit
     * platforms always replays a stored key against its saved endpoint.
     */
    public function outboundApiUrl(): ?string
    {
        $apiUrl = $this->input('api_url');

        $apiUrl = is_string($apiUrl) && trim($apiUrl) !== '' ? $apiUrl : null;

        if ($apiUrl !== null || ! $this->replaysStoredKey() || $this->canEditPlatforms()) {
            return $apiUrl;
        }

        return $this->managedStoredPlatform()?->api_url;
    }

    /**
     * Reject a replayed stored key for an unknown platform, or one a user
     * without edit rights points at another provider or endpoint.
     */
    protected function guardStoredKeyConnection(Validator $validator): void
    {
        if (! $this->replaysStoredKey()) {
            return;
        }

        $platform = $this->managedStoredPlatform();

        if (! $platform instanceof MagicAIPlatform) {
            $validator->errors()->add('id', trans('admin::app.configuration.platform.message.not-found'));

            return;
        }

        if ($this->canEditPlatforms()) {
            return;
        }

        $provider = $this->input('provider');

        if (! app(ManagedPlatform::class)->isPlatformConnection(
            $platform,
            is_string($provider) ? $provider : null,
            $this->outboundApiUrl(),
        )) {
            $validator->errors()->add('api_url', trans('admin::app.configuration.platform.message.managed-connection-locked'));
        }
    }

    protected function canEditPlatforms(): bool
    {
        return bouncer()->hasPermission('ai-agent.platform.edit');
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
