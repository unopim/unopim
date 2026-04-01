<?php

namespace Webkul\AiAgent\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds default Agentic PIM configuration values so the feature
 * is enabled out of the box after installation.
 */
class AgenticPimConfigSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'general.magic_ai.agentic_pim.enabled'              => '1',
            'general.magic_ai.agentic_pim.max_steps'            => '5',
            'general.magic_ai.agentic_pim.daily_token_budget'   => '500000',
            'general.magic_ai.agentic_pim.auto_enrichment'      => '0',
            'general.magic_ai.agentic_pim.quality_monitor'      => '0',
            'general.magic_ai.agentic_pim.confidence_threshold' => '0.7',
            'general.magic_ai.agentic_pim.approval_mode'        => 'auto',
        ];

        foreach ($defaults as $code => $value) {
            // Only insert if not already set (don't overwrite user changes)
            $exists = DB::table('core_config')
                ->where('code', $code)
                ->whereNull('channel_code')
                ->whereNull('locale_code')
                ->exists();

            if (! $exists) {
                DB::table('core_config')->insert([
                    'code'         => $code,
                    'value'        => $value,
                    'channel_code' => null,
                    'locale_code'  => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
