<?php

namespace Webkul\AiAgent\Examples;

use Illuminate\Console\Command;
use Webkul\AiAgent\Agents\ImageProductAgent;
use Webkul\AiAgent\Repositories\AgentRepository;
use Webkul\AiAgent\Repositories\CredentialRepository;

/**
 * Example console command for analyzing product images.
 *
 * Shows how to use agents in commands with dependency injection
 * and structured output.
 *
 * Usage:
 *   php artisan ai-agent:analyze-image https://example.com/product.jpg --agent-id=1 --credential-id=1 --async
 */
class AnalyzeProductImageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-agent:analyze-image {imageUrl} {--agent-id=1} {--credential-id=1} {--async}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze a product image using AI Agent';

    /**
     * Execute the console command.
     */
    public function handle(
        ImageProductAgent $imageAgent,
        AgentRepository $agentRepository,
        CredentialRepository $credentialRepository,
    ): int {
        $imageUrl = $this->argument('imageUrl');
        $agentId = (int) $this->option('agent-id');
        $credentialId = (int) $this->option('credential-id');
        $async = $this->option('async');

        // Validate agent and credential exist
        $agent = $agentRepository->find($agentId);

        if (! $agent) {
            $this->error("Agent with ID $agentId not found.");

            return self::FAILURE;
        }

        $credential = $credentialRepository->find($credentialId);

        if (! $credential) {
            $this->error("Credential with ID $credentialId not found.");

            return self::FAILURE;
        }

        try {
            $this->info("Analyzing product image: $imageUrl");

            if ($async) {
                $imageAgent->analyzeAsync(
                    imageSource: $imageUrl,
                    agentId: $agentId,
                    credentialId: $credentialId,
                    additionalContext: ['commandExecuted' => true],
                );

                $this->info('✅ Analysis queued successfully.');

                return self::SUCCESS;
            }

            $this->withProgressBar(1, function () use ($imageAgent, $imageUrl, $agentId, $credentialId) {
                $imageAgent->analyze(
                    imageSource: $imageUrl,
                    agentId: $agentId,
                    credentialId: $credentialId,
                );
            });

            $this->newLine();

            $this->info('✅ Analysis complete.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Analysis failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
