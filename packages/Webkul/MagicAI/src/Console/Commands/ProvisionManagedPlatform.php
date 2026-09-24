<?php

namespace Webkul\MagicAI\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\MagicAI\Console\Support\StdinReader;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ManagedPlatform;
use Webkul\MagicAI\Services\ModelDiscovery;
use Webkul\MagicAI\Support\ModelRecommender;
use Webkul\MagicAI\Validator\PlatformValidator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

#[Signature('unopim:magic-ai:managed-platform
    {--platform= : ID of the platform to edit}
    {--provider= : Provider code, e.g. concentrate}
    {--label= : Platform label}
    {--api-url= : API URL, empty for the provider default}
    {--models= : Comma-separated model IDs}
    {--azure-deployment= : Azure deployment name}
    {--azure-api-version= : Azure API version}
    {--default : Make it the default platform}
    {--disabled : Save it disabled}
    {--unmanaged : Save it without the managed lock}
    {--key-stdin : Read the API key from the first line of standard input}')]
#[Description('Create or edit a Magic AI platform, optionally managed, storing its API key encrypted in the database')]
class ProvisionManagedPlatform extends Command
{
    protected const AZURE_DEPLOYMENT = 'gpt-4o';

    protected const AZURE_API_VERSION = '2024-10-21';

    protected MagicAIPlatformRepository $platformRepository;

    protected ManagedPlatform $managedPlatform;

    protected PlatformValidator $platformValidator;

    /**
     * Collect the platform the same way the admin form does, then save it
     * through the repository. The key is never taken as an option value, so
     * it stays out of the process list and shell history.
     */
    public function handle(MagicAIPlatformRepository $platformRepository, ManagedPlatform $managedPlatform, PlatformValidator $platformValidator): int
    {
        $this->platformRepository = $platformRepository;
        $this->managedPlatform = $managedPlatform;
        $this->platformValidator = $platformValidator;

        $platform = $this->targetPlatform();

        if ($platform === false) {
            return self::FAILURE;
        }

        $data = $this->input->isInteractive()
            ? $this->collectInteractively($platform)
            : $this->collectFromOptions($platform);

        if ($data === null) {
            return self::FAILURE;
        }

        if ($this->input->isInteractive()) {
            $this->summarise($data, $platform);

            if (! confirm(trans('admin::app.configuration.platform.command.confirm-save'))) {
                info(trans('admin::app.configuration.platform.command.cancelled'));

                return self::SUCCESS;
            }
        }

        return $this->save($data, $platform);
    }

    /**
     * The platform to edit, null to create one, or false when the requested
     * platform does not exist.
     */
    protected function targetPlatform(): MagicAIPlatform|false|null
    {
        if ($this->option('platform') !== null) {
            $platform = $this->platformRepository->find((int) $this->option('platform'));

            if (! $platform) {
                error(trans('admin::app.configuration.platform.message.not-found'));

                return false;
            }

            return $platform;
        }

        if (! $this->input->isInteractive()) {
            return null;
        }

        $managed = $this->platformRepository->findWhere(['is_managed' => true]);

        if ($managed->isEmpty()) {
            return null;
        }

        $choice = select(
            label: trans('admin::app.configuration.platform.command.target'),
            options: ['new' => trans('admin::app.configuration.platform.command.target-new')]
                + $managed->mapWithKeys(fn (MagicAIPlatform $platform): array => [
                    $platform->id => trans('admin::app.configuration.platform.command.target-option', [
                        'label'    => $platform->label,
                        'provider' => AiProvider::tryFrom($platform->provider)?->label() ?? $platform->provider,
                    ]),
                ])->all(),
            default: 'new',
        );

        return $choice === 'new' ? null : $managed->firstWhere('id', (int) $choice);
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectInteractively(?MagicAIPlatform $platform): array
    {
        $provider = AiProvider::from(select(
            label: trans('admin::app.configuration.platform.fields.provider'),
            options: collect(AiProvider::cases())->mapWithKeys(fn (AiProvider $provider): array => [$provider->value => $provider->label()])->all(),
            default: $platform->provider ?? AiProvider::tryFrom((string) $this->option('provider'))?->value ?? AiProvider::Concentrate->value,
        ));

        $sameProvider = $platform?->provider === $provider->value;

        $label = text(
            label: trans('admin::app.configuration.platform.fields.label'),
            default: $platform->label ?? $provider->label(),
            required: true,
            validate: fn (string $value): ?string => $this->fieldError('label', $value),
        );

        $apiUrl = trim(text(
            label: trans('admin::app.configuration.platform.fields.api-url'),
            default: $sameProvider ? (string) $platform?->api_url : $provider->defaultUrl(),
            validate: fn (string $value): ?string => $this->fieldError('api_url', trim($value) ?: null)
                ?? $this->platformValidator->apiUrlError($provider->value, $value),
            hint: trans('admin::app.configuration.platform.fields.api-url-hint'),
        ));

        $apiKey = null;

        if ($provider !== AiProvider::Ollama) {
            $apiKey = password(
                label: trans('admin::app.configuration.platform.fields.api-key'),
                required: ! $platform?->safeApiKey(),
                hint: $platform instanceof MagicAIPlatform ? trans('admin::app.configuration.platform.command.api-key-keep-hint') : '',
            ) ?: null;
        }

        $extras = $provider === AiProvider::Azure ? [
            'deployment' => text(
                label: trans('admin::app.configuration.platform.fields.azure-deployment'),
                default: (string) ($platform->extras['deployment'] ?? self::AZURE_DEPLOYMENT),
                required: true,
            ),
            'api_version' => text(
                label: trans('admin::app.configuration.platform.fields.azure-api-version'),
                default: (string) ($platform->extras['api_version'] ?? self::AZURE_API_VERSION),
                required: true,
            ),
        ] : [];

        $models = $this->askModels($provider, $apiKey ?? $platform?->safeApiKey(), $apiUrl, $platform instanceof MagicAIPlatform && $sameProvider ? $this->managedPlatform->models($platform) : []);

        $isDefault = confirm(
            label: trans('admin::app.configuration.platform.fields.is-default'),
            default: $platform->is_default ?? $this->platformRepository->getDefault() === null,
        );

        $status = confirm(
            label: trans('admin::app.configuration.platform.fields.status'),
            default: $platform->status ?? true,
            validate: fn (bool $enabled): ?string => $this->platformValidator->defaultStatusError($isDefault, $enabled),
        );

        $isManaged = confirm(
            label: trans('admin::app.configuration.platform.command.managed'),
            validate: fn (bool $managed): ?string => $this->managedExtrasError($managed, $extras),
        );

        return [
            'provider'   => $provider->value,
            'label'      => $label,
            'api_url'    => $apiUrl,
            'api_key'    => $apiKey,
            'models'     => implode(',', $models),
            'extras'     => $extras,
            'is_default' => $isDefault,
            'status'     => $status,
            'is_managed' => $isManaged,
        ];
    }

    /**
     * Offer the models the provider serves, falling back to a typed list
     * when they cannot be fetched.
     *
     * @param  string[]  $stored
     * @return string[]
     */
    protected function askModels(AiProvider $provider, ?string $apiKey, string $apiUrl, array $stored): array
    {
        $fetched = $this->fetchModels($provider, $apiKey, $apiUrl);

        if ($fetched === []) {
            return $this->platformValidator->splitModels(text(
                label: trans('admin::app.configuration.platform.command.models-manual'),
                default: implode(',', $stored),
                required: true,
                validate: fn (string $value): ?string => $this->platformValidator->modelNamesError($value),
            ));
        }

        $selected = multiselect(
            label: trans('admin::app.configuration.platform.fields.models'),
            options: array_values(array_unique([...ModelRecommender::chatCapable($fetched), ...$stored])),
            default: $stored !== [] ? $stored : ModelRecommender::recommend($fetched),
            scroll: 10,
            required: trans('admin::app.configuration.platform.command.models-required'),
        );

        $additional = trim(text(
            label: trans('admin::app.configuration.platform.command.models-additional'),
            validate: fn (string $value): ?string => trim($value) === '' ? null : $this->platformValidator->modelNamesError($value),
        ));

        $models = array_values(array_unique([
            ...$selected,
            ...($additional === '' ? [] : $this->platformValidator->splitModels($additional)),
        ]));

        $unlisted = array_diff($models, $fetched);

        if ($unlisted !== []) {
            warning(trans('admin::app.configuration.platform.command.models-unlisted', ['models' => implode(', ', $unlisted)]));
        }

        return $models;
    }

    /**
     * @return string[]
     */
    protected function fetchModels(AiProvider $provider, ?string $apiKey, string $apiUrl): array
    {
        try {
            $models = spin(
                fn (): array => resolve(ModelDiscovery::class)->discover($provider, $apiKey, $apiUrl ?: null)['models'],
                trans('admin::app.configuration.platform.command.fetching-models', ['provider' => $provider->label()]),
            );
        } catch (\Throwable $e) {
            warning(trans('admin::app.configuration.platform.message.fetch-models-fail').': '.$e->getMessage());

            return [];
        }

        if ($models === []) {
            warning(trans('admin::app.configuration.platform.errors.no-models-returned'));
        }

        return $models;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function collectFromOptions(?MagicAIPlatform $platform): ?array
    {
        $providerValue = $this->option('provider') ?? $platform?->provider;

        if (! $providerValue) {
            return $this->missingOption('provider');
        }

        $provider = AiProvider::tryFrom($providerValue);

        if (! $provider) {
            error(trans('admin::app.configuration.platform.command.invalid-provider', [
                'provider'  => $providerValue,
                'providers' => implode(', ', array_column(AiProvider::cases(), 'value')),
            ]));

            return null;
        }

        $sameProvider = $platform?->provider === $provider->value;

        $apiKey = $this->option('key-stdin') ? resolve(StdinReader::class)->readLine() : '';

        if ($apiKey === '' && $provider !== AiProvider::Ollama && ! $platform?->safeApiKey()) {
            error(trans('admin::app.configuration.platform.command.key-stdin-required'));

            return null;
        }

        $models = $this->option('models') ?? ($sameProvider ? $platform?->models : null);

        if (! $models) {
            return $this->missingOption('models');
        }

        $extras = $provider === AiProvider::Azure ? [
            'deployment'  => $this->option('azure-deployment') ?? $platform->extras['deployment'] ?? self::AZURE_DEPLOYMENT,
            'api_version' => $this->option('azure-api-version') ?? $platform->extras['api_version'] ?? self::AZURE_API_VERSION,
        ] : [];

        $status = ! $this->option('disabled');

        $isManaged = ! $this->option('unmanaged');

        if ($error = $this->managedExtrasError($isManaged, $extras)) {
            error($error);

            return null;
        }

        return [
            'provider'   => $provider->value,
            'label'      => $this->option('label') ?? $platform->label ?? $provider->label(),
            'api_url'    => $this->option('api-url') ?? ($sameProvider ? (string) $platform?->api_url : $provider->defaultUrl()),
            'api_key'    => $apiKey ?: null,
            'models'     => $models,
            'extras'     => $extras,
            'is_default' => $this->option('default') || ($platform instanceof MagicAIPlatform
                ? $platform->is_default
                : $status && $this->platformRepository->getDefault() === null),
            'status'     => $status,
            'is_managed' => $isManaged,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function summarise(array $data, ?MagicAIPlatform $platform): void
    {
        $yesNo = fn (bool $value): string => trans($value ? 'admin::app.common.yes' : 'admin::app.common.no');

        table(
            headers: [
                trans('admin::app.configuration.platform.command.field'),
                trans('admin::app.configuration.platform.command.value'),
            ],
            rows: [
                [trans('admin::app.configuration.platform.fields.provider'), AiProvider::from($data['provider'])->label()],
                [trans('admin::app.configuration.platform.fields.label'), $data['label']],
                [trans('admin::app.configuration.platform.fields.api-url'), $data['api_url'] ?: trans('admin::app.configuration.platform.command.provider-default')],
                [trans('admin::app.configuration.platform.fields.api-key'), $data['api_key'] !== null
                    ? str_repeat('*', 8)
                    : ($platform?->safeApiKey() ? trans('admin::app.configuration.platform.command.key-unchanged') : '')],
                [trans('admin::app.configuration.platform.fields.models'), str_replace(',', ', ', $data['models'])],
                [trans('admin::app.configuration.platform.command.extras'), $data['extras'] === [] ? '' : (string) json_encode($data['extras'])],
                [trans('admin::app.configuration.platform.fields.is-default'), $yesNo($data['is_default'])],
                [trans('admin::app.configuration.platform.fields.status'), trans($data['status'] ? 'admin::app.common.enable' : 'admin::app.common.disable')],
                [trans('admin::app.configuration.platform.managed-badge'), $yesNo($data['is_managed'])],
            ],
        );
    }

    /**
     * Validate the platform with the form's rules and save it through the
     * repository, as the admin form does.
     *
     * @param  array<string, mixed>  $data
     */
    protected function save(array $data, ?MagicAIPlatform $platform): int
    {
        $payload = [
            'label'      => $data['label'],
            'provider'   => $data['provider'],
            'api_url'    => $data['api_url'] ?: null,
            'models'     => $data['models'],
            'extras'     => $data['extras'] === [] ? null : json_encode($data['extras']),
            'is_default' => $data['is_default'],
            'status'     => $data['status'],
        ];

        if ($data['api_key'] !== null) {
            $payload['api_key'] = $data['api_key'];
        }

        try {
            $payload = $this->platformValidator->validate($payload);
        } catch (ValidationException $e) {
            foreach ($e->validator->errors()->all() as $message) {
                error($message);
            }

            return self::FAILURE;
        }

        $payload['extras'] = $data['extras'] ?: null;

        $saved = DB::transaction(function () use ($payload, $platform, $data): MagicAIPlatform {
            $saved = $platform instanceof MagicAIPlatform
                ? $this->platformRepository->update($payload, $platform->id)
                : $this->platformRepository->create($payload);

            $saved->forceFill(['is_managed' => $data['is_managed']])->save();

            return $saved;
        });

        info(trans('admin::app.configuration.platform.command.saved', ['label' => $saved->label]));

        return self::SUCCESS;
    }

    /**
     * The first error of a single field against the form's rules.
     */
    protected function fieldError(string $field, mixed $value): ?string
    {
        $rules = $this->platformValidator->rules();

        return Validator::make([$field => $value], [$field => $rules[$field]])->errors()->first($field) ?: null;
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    protected function managedExtrasError(bool $isManaged, array $extras): ?string
    {
        return $isManaged && $extras !== []
            ? trans('admin::app.configuration.platform.command.managed-extras')
            : null;
    }

    protected function missingOption(string $option): null
    {
        error(trans('admin::app.configuration.platform.command.missing-option', ['option' => $option]));

        return null;
    }
}
