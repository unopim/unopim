<?php

namespace Webkul\AiAgent\Pipelines\Stages\Image;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\PipelineException;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Repositories\CredentialRepository;

/**
 * Stage 4 — Calls the AI to fill in missing or thin product attributes
 * (name, SEO meta, selling points, target audience, etc.) using the
 * mapped attribute data as base context.
 *
 * Attributes already set by previous stages are kept as-is; this stage
 * only generates values for the keys listed in ENRICHMENT_TARGETS that
 * are absent or blank.
 *
 * Reads from metadata (set by AttributeMappingStep):
 *   mappedAttributes  — PIM-keyed attribute values
 *
 * Reads from context (optional):
 *   enrichLocale       — locale for generated text (default: en_US)
 *   enrichmentTargets  — override list of attribute keys to generate
 *   skipEnrichment     — bool, skip this stage entirely
 *
 * Writes to metadata:
 *   enrichedAttributes  array  mappedAttributes merged with AI-generated values
 *   enrichedKeys        array  List of keys that were actually generated
 *   enrichmentTokens    int    Tokens consumed
 */
class EnrichmentStep implements PipelineStageContract
{
    /**
     * Default attribute keys to generate if absent.
     *
     * @var array<string>
     */
    protected const ENRICHMENT_TARGETS = [
        'name',
        'short_description',
        'long_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'key_benefits',
        'target_audience',
        'marketing_tagline',
    ];

    public function __construct(
        protected AiApiClient $apiClient,
        protected CredentialRepository $credentialRepository,
    ) {}

    /**
     * {@inheritdoc}
     *
     * @throws PipelineException
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        // Allow callers to skip enrichment for lightweight runs
        if ($payload->context['skipEnrichment'] ?? false) {
            $mapped = $payload->metadata['mappedAttributes'] ?? [];
            $ctx = ImageProductContext::fromArray($payload->metadata['imageContext'] ?? []);

            return $next($payload->withMetadata([
                'imageContext'       => $ctx->toArray(),
                'enrichedAttributes' => $mapped,
                'enrichedKeys'       => [],
                'enrichmentTokens'   => 0,
            ]));
        }

        $mapped = $payload->metadata['mappedAttributes'] ?? null;

        if (! is_array($mapped)) {
            throw new PipelineException(
                'EnrichmentStep: mappedAttributes is missing — AttributeMappingStep must run first.',
                self::class,
            );
        }

        $targets = (array) ($payload->context['enrichmentTargets'] ?? self::ENRICHMENT_TARGETS);

        // Determine which targets need generation (missing or blank)
        $missingTargets = array_filter(
            $targets,
            fn (string $key) => empty($mapped[$key]),
        );

        if (empty($missingTargets)) {
            // Everything already populated — skip API call
            return $next($payload->withMetadata([
                'enrichedAttributes' => $mapped,
                'enrichedKeys'       => [],
                'enrichmentTokens'   => 0,
            ]));
        }

        $credential = $this->credentialRepository->findOrFail($payload->credentialId);
        $config = CredentialConfig::fromModel($credential);

        $this->apiClient->configure($config);

        $locale = $payload->context['enrichLocale'] ?? 'en_US';
        $messages = $this->buildMessages($mapped, array_values($missingTargets), $locale);

        $response = $this->apiClient->chat(messages: $messages, maxTokens: 2048, temperature: 0.6);
        $generated = $this->parseGenerated($response['content'] ?? '');

        // Merge: existing mapped values take priority; generated fills gaps
        $enriched = array_merge($generated, $mapped);

        $ctx = ImageProductContext::fromArray($payload->metadata['imageContext'] ?? [])
            ->withEnrichment($generated);

        return $next($payload->withMetadata([
            'imageContext'       => $ctx->toArray(),
            'enrichedAttributes' => $enriched,
            'enrichedKeys'       => array_keys($generated),
            'enrichmentTokens'   => $response['tokensUsed'] ?? 0,
        ]));
    }

    /**
     * Build the message array for the enrichment call.
     *
     * @param  array<string, mixed>  $existingAttributes
     * @param  array<string>  $missingKeys
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildMessages(array $existingAttributes, array $missingKeys, string $locale): array
    {
        $existingJson = json_encode($existingAttributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $targetKeys = json_encode($missingKeys);

        return [
            [
                'role'    => 'system',
                'content' => <<<SYSTEM
                    You are a professional product content writer.
                    Generate only the requested attributes for an e-commerce product catalog.
                    Base your response on the provided existing product data.
                    Target locale: $locale.
                    Return a single flat JSON object with exactly the requested keys.
                    Use concise, conversion-focused copy.
                    SYSTEM,
            ],
            [
                'role'    => 'user',
                'content' => <<<USER
                    Existing product data:
                    $existingJson

                    Generate values for these missing attributes: $targetKeys

                    Return ONLY a JSON object with those keys and no other text.
                    USER,
            ],
        ];
    }

    /**
     * Parse the AI-generated attribute JSON.
     *
     * @return array<string, mixed>
     */
    protected function parseGenerated(string $raw): array
    {
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/\s*```$/m', '', $clean ?? $raw);
        $decoded = json_decode(trim($clean ?? ''), true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }
}
