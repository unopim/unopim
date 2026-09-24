<?php

namespace Webkul\MagicAI\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Rules\SafeProviderExtras;
use Webkul\Webhook\Validators\SafeWebhookUrl;

/**
 * The rules a Magic AI platform must satisfy, shared by the admin form and
 * the provisioning command so both accept exactly the same platforms.
 */
class PlatformValidator
{
    public const MODEL_NAME_PATTERN = '/^[a-zA-Z0-9][a-zA-Z0-9\-._:\/@]+$/';

    /**
     * The field rules of a platform submission.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label'      => 'required|string|max:255',
            'provider'   => ['required', Rule::enum(AiProvider::class)],
            'api_url'    => 'nullable|url|max:500',
            'api_key'    => 'nullable|string',
            'models'     => 'required|string',
            'is_default' => 'sometimes|boolean',
            'status'     => 'sometimes|boolean',
            'extras'     => ['nullable', new SafeProviderExtras],
        ];
    }

    /**
     * Validate a whole platform payload and return it with its model list
     * normalised.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $data): array
    {
        Validator::make($data, $this->rules())->validate();

        $data['models'] = $this->validateModelNames((string) $data['models']);

        $this->validateApiUrl($data['provider'] ?? null, $data['api_url'] ?? null);

        $this->ensureDefaultIsEnabled($data);

        return $data;
    }

    /**
     * Split a comma-separated model list, dropping the `~` prefix some
     * providers add.
     *
     * @return string[]
     */
    public function splitModels(string $models): array
    {
        return array_map(fn (string $model): string => ltrim(trim($model), '~'), explode(',', $models));
    }

    /**
     * The model names that are not valid identifiers.
     *
     * @param  string[]  $models
     * @return string[]
     */
    public function invalidModelNames(array $models): array
    {
        return array_values(array_filter(
            $models,
            fn (string $model): bool => $model === '' || ! preg_match(self::MODEL_NAME_PATTERN, $model),
        ));
    }

    /**
     * The error for a comma-separated model list, or null when every name is valid.
     */
    public function modelNamesError(string $models): ?string
    {
        $invalid = $this->invalidModelNames($this->splitModels($models));

        return $invalid === []
            ? null
            : trans('admin::app.configuration.platform.message.invalid-model-names', ['names' => implode(', ', $invalid)]);
    }

    /**
     * Normalise a comma-separated model list.
     *
     * @throws ValidationException
     */
    public function validateModelNames(string $models): string
    {
        if ($error = $this->modelNamesError($models)) {
            throw ValidationException::withMessages(['models' => $error]);
        }

        return implode(',', $this->splitModels($models));
    }

    /**
     * The error for a platform endpoint, or null when it is acceptable. A
     * Custom provider must carry an explicit URL, otherwise generation would
     * fall back to the global openai-compatible URL and ship the key to an
     * unrelated host; any URL must pass the SSRF check.
     */
    public function apiUrlError(?string $provider, ?string $apiUrl): ?string
    {
        $apiUrl = trim((string) $apiUrl);

        if ($provider === AiProvider::Custom->value && $apiUrl === '') {
            return trans('admin::app.configuration.platform.message.custom-api-url-required');
        }

        if (! $this->isSafeApiUrl($apiUrl)) {
            return trans('admin::app.configuration.platform.message.unsafe-api-url');
        }

        return null;
    }

    /**
     * @throws ValidationException
     */
    public function validateApiUrl(?string $provider, ?string $apiUrl): void
    {
        if ($error = $this->apiUrlError($provider, $apiUrl)) {
            throw ValidationException::withMessages(['api_url' => $error]);
        }
    }

    public function isSafeApiUrl(?string $apiUrl): bool
    {
        $apiUrl = trim((string) $apiUrl);

        return $apiUrl === '' || SafeWebhookUrl::validate($apiUrl)['valid'];
    }

    /**
     * The error for a platform marked default while disabled, or null.
     */
    public function defaultStatusError(bool $isDefault, bool $status): ?string
    {
        return $isDefault && ! $status
            ? trans('admin::app.configuration.platform.message.default-requires-enabled')
            : null;
    }

    /**
     * A platform cannot be marked default unless it is also enabled.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function ensureDefaultIsEnabled(array $data): void
    {
        if ($error = $this->defaultStatusError(! empty($data['is_default']), ! empty($data['status']))) {
            throw ValidationException::withMessages(['is_default' => $error]);
        }
    }
}
