<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $configPrefix = 'general.magic_ai.settings';

        $platform = $this->getConfigValue($configPrefix.'.ai_platform');
        $apiKey = $this->getConfigValue($configPrefix.'.api_key');
        $apiDomain = $this->getConfigValue($configPrefix.'.api_domain');
        $apiModel = $this->getConfigValue($configPrefix.'.api_model');
        $organization = $this->getConfigValue($configPrefix.'.organization');

        if (! $platform || ! $apiKey) {
            return;
        }

        $extras = [];

        if ($organization) {
            $extras['organization'] = $organization;
        }

        DB::table('magic_ai_platforms')->insert([
            'label'      => ucfirst($platform).' (Migrated)',
            'provider'   => $platform,
            'api_url'    => $this->normalizeApiUrl($apiDomain, $platform),
            'api_key'    => Crypt::encryptString($apiKey),
            'models'     => $apiModel ?: '',
            'extras'     => ! empty($extras) ? json_encode($extras) : null,
            'is_default' => true,
            'status'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('magic_ai_platforms')->where('label', 'like', '%(Migrated)')->delete();
    }

    private function getConfigValue(string $key): ?string
    {
        $record = DB::table('core_config')
            ->where('code', $key)
            ->first();

        return $record?->value;
    }

    private function normalizeApiUrl(?string $domain, string $platform): string
    {
        $defaults = [
            'openai'     => 'https://api.openai.com/v1',
            'anthropic'  => 'https://api.anthropic.com/v1',
            'gemini'     => 'https://generativelanguage.googleapis.com/v1beta',
            'groq'       => 'https://api.groq.com/openai/v1',
            'ollama'     => 'http://localhost:11434',
            'xai'        => 'https://api.x.ai/v1',
            'mistral'    => 'https://api.mistral.ai/v1',
            'deepseek'   => 'https://api.deepseek.com',
            'openrouter' => 'https://openrouter.ai/api/v1',
        ];

        if (! $domain) {
            return $defaults[$platform] ?? '';
        }

        // Ensure https:// prefix
        if (! str_starts_with($domain, 'http://') && ! str_starts_with($domain, 'https://')) {
            $domain = 'https://'.$domain;
        }

        // Append /v1 if it's a bare domain like https://api.openai.com
        if (in_array($platform, ['openai', 'groq', 'xai', 'mistral']) && ! str_contains($domain, '/v1')) {
            $domain = rtrim($domain, '/').'/v1';
        }

        return $domain;
    }
};
