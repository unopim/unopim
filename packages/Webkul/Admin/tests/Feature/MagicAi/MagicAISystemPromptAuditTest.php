<?php

use Illuminate\Support\Facades\DB;
use Webkul\MagicAI\Models\MagicAISystemPrompt;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->auditableType = (new MagicAISystemPrompt)->getMorphClass();
});

function updateSystemPromptUnchanged($test, MagicAISystemPrompt $prompt)
{
    return $test->put(route('admin.magic_ai.system_prompt.update'), [
        'id'          => $prompt->id,
        'title'       => $prompt->title,
        'tone'        => $prompt->tone,
        'max_tokens'  => $prompt->max_tokens,
        'temperature' => $prompt->temperature,
        'is_enabled'  => $prompt->is_enabled ? '1' : '0',
    ]);
}

describe('MagicAI system prompt update audits', function () {
    it('records no audit when an enabled prompt is saved without any change', function () {
        $prompt = MagicAISystemPrompt::factory()->create(['is_enabled' => 1]);

        DB::table('audits')->where('auditable_type', $this->auditableType)->delete();

        updateSystemPromptUnchanged($this, $prompt)->assertOk();

        $audits = DB::table('audits')
            ->where('auditable_type', $this->auditableType)
            ->where('auditable_id', $prompt->id)
            ->where('event', 'updated')
            ->get();

        expect($audits)->toBeEmpty();
    });

    it('records no audit when a disabled prompt is saved without any change', function () {
        $prompt = MagicAISystemPrompt::factory()->create(['is_enabled' => 0]);

        DB::table('audits')->where('auditable_type', $this->auditableType)->delete();

        updateSystemPromptUnchanged($this, $prompt)->assertOk();

        $audits = DB::table('audits')
            ->where('auditable_type', $this->auditableType)
            ->where('auditable_id', $prompt->id)
            ->where('event', 'updated')
            ->get();

        expect($audits)->toBeEmpty();
    });

    it('still records an audit when the enabled flag actually changes', function () {
        $prompt = MagicAISystemPrompt::factory()->create(['is_enabled' => 0]);

        DB::table('audits')->where('auditable_type', $this->auditableType)->delete();

        $this->put(route('admin.magic_ai.system_prompt.update'), [
            'id'          => $prompt->id,
            'title'       => $prompt->title,
            'tone'        => $prompt->tone,
            'max_tokens'  => $prompt->max_tokens,
            'temperature' => $prompt->temperature,
            'is_enabled'  => '1',
        ])->assertOk();

        $audit = DB::table('audits')
            ->where('auditable_type', $this->auditableType)
            ->where('auditable_id', $prompt->id)
            ->where('event', 'updated')
            ->first();

        expect($audit)->not->toBeNull();
        expect(json_decode($audit->new_values, true))->toHaveKey('is_enabled');
    });
});
