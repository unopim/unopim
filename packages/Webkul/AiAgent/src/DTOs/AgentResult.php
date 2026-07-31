<?php

namespace Webkul\AiAgent\DTOs;

/**
 * Immutable result DTO returned after an agent execution completes.
 */
final readonly class AgentResult
{
    /**
     * @param  bool  $success  Whether the execution succeeded
     * @param  string  $output  Final AI-generated output
     * @param  array<string, mixed>  $data  Structured response data (parsed JSON, etc.)
     * @param  array<string, mixed>  $metadata  Pipeline metadata collected during execution
     * @param  int  $tokensUsed  Total tokens consumed
     * @param  string|null  $error  Error message if failed
     */
    public function __construct(
        public bool $success,
        public string $output = '',
        public array $data = [],
        public array $metadata = [],
        public int $tokensUsed = 0,
        public ?string $error = null,
    ) {}

    /**
     * Create a successful result.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    public static function success(
        string $output,
        array $data = [],
        array $metadata = [],
        int $tokensUsed = 0,
    ): self {
        return new self(
            success: true,
            output: $output,
            data: $data,
            metadata: $metadata,
            tokensUsed: $tokensUsed,
        );
    }

    /**
     * Create a failed result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function failure(string $error, array $metadata = []): self
    {
        return new self(
            success: false,
            metadata: $metadata,
            error: $error,
        );
    }

    /**
     * Serialize to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success'    => $this->success,
            'output'     => $this->output,
            'data'       => $this->data,
            'metadata'   => $this->metadata,
            'tokensUsed' => $this->tokensUsed,
            'error'      => $this->error,
        ];
    }
}
