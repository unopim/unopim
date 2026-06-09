<?php

use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Regression coverage for the MagicAI platform authorization bypass (audit finding #4).
 *
 * `admin.magic_ai.platform.update` (PUT) and `admin.magic_ai.platform.set_default`
 * (POST) were registered but absent from AiAgent/Config/acl.php, so the fail-open
 * Bouncer middleware skipped the permission check and any authenticated admin could
 * repoint a platform's api_url/api_key or swap the default. The fix maps both routes
 * to `ai-agent.platform.edit`.
 */
function makeMagicAiPlatform(array $overrides = []): MagicAIPlatform
{
    return MagicAIPlatform::create(array_merge([
        'label'      => 'Primary',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'models'     => 'gpt-4o',
        'is_default' => true,
        'status'     => true,
    ], $overrides));
}

describe('MagicAI platform authorization', function () {
    it('forbids a low-privilege admin from updating a platform', function () {
        $platform = makeMagicAiPlatform();

        $this->loginWithPermissions('custom', ['dashboard']);

        $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
            'label'    => 'x',
            'provider' => 'openai',
            'models'   => 'gpt-4o',
            'api_url'  => 'https://evil.tld',
            'api_key'  => 'stolen',
        ])->assertForbidden();
    });

    it('forbids a low-privilege admin from changing the default platform', function () {
        makeMagicAiPlatform(['label' => 'A']);
        $other = makeMagicAiPlatform(['label' => 'B', 'is_default' => false]);

        $this->loginWithPermissions('custom', ['dashboard']);

        $this->postJson(route('admin.magic_ai.platform.set_default', $other->id))
            ->assertForbidden();
    });

    it('allows an admin holding the platform-edit permission to update', function () {
        $platform = makeMagicAiPlatform();

        $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.platform.edit']);

        $this->putJson(route('admin.magic_ai.platform.update', $platform->id), [
            'label'      => 'Updated',
            'provider'   => 'openai',
            'models'     => 'gpt-4o',
            'status'     => true,
            'is_default' => true,
        ])->assertOk();
    });
});
