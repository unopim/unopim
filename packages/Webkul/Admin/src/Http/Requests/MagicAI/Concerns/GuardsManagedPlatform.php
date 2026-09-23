<?php

namespace Webkul\Admin\Http\Requests\MagicAI\Concerns;

use Closure;
use Illuminate\Validation\Validator;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ManagedPlatform;
use Webkul\MagicAI\Services\ProviderOverrides;

trait GuardsManagedPlatform
{
    /**
     * Reject a submission that would use the managed account's key outside
     * its provider, endpoint or model list.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $violations = app(ManagedPlatform::class)->violations(
                    $this->submittedApiKey(),
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
     * The key the request will actually use. A masked or omitted key falls
     * back to the stored platform's key, since that is what gets sent.
     */
    protected function submittedApiKey(): ?string
    {
        $apiKey = (string) $this->input('api_key');

        if ($apiKey !== '' && ! preg_match('/^\*+$/', $apiKey)) {
            return $apiKey;
        }

        $platformId = (int) ($this->route('id') ?? $this->input('id'));

        return $platformId > 0
            ? app(MagicAIPlatformRepository::class)->find($platformId)?->safeApiKey()
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
