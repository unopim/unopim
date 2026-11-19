<?php

namespace Webkul\MagicAI\Enums;

/**
 * Why a Magic AI platform cannot be used to generate embeddings.
 */
enum EmbeddingRejection: string
{
    case Inactive = 'inactive';

    case NoEmbeddingsApi = 'no_embeddings_api';

    case UnreadableApiKey = 'unreadable_api_key';

    case DimensionsMismatch = 'dimensions_mismatch';

    case MissingEmbeddingDeployment = 'missing_embedding_deployment';

    /**
     * Explain the rejection and its remediation to the AI model.
     */
    public function describe(): string
    {
        return match ($this) {
            self::Inactive                   => 'it is inactive; activate it under Magic AI platforms',
            self::NoEmbeddingsApi            => 'its provider has no embeddings API',
            self::UnreadableApiKey           => 'its stored API key cannot be decrypted; re-enter the key under Magic AI platforms',
            self::DimensionsMismatch         => 'its provider returns a vector size that does not match the configured vector index dimensions',
            self::MissingEmbeddingDeployment => 'it has no embedding_deployment set; add the Azure embeddings deployment name to the platform extras',
        };
    }
}
