<?php

it('should place ai-agent (Magic AI) before data_transfer in the sidebar sort order (Issue #713)', function () {
    $aiAgentMenu = require __DIR__.'/../../../AiAgent/Config/menu.php';
    $adminMenu = require __DIR__.'/../../src/Config/menu.php';

    $aiAgentSort = collect($aiAgentMenu)->firstWhere('key', 'ai-agent')['sort'] ?? null;
    $dataTransferSort = collect($adminMenu)->firstWhere('key', 'data_transfer')['sort'] ?? null;

    expect($aiAgentSort)->toBe(7);
    expect($dataTransferSort)->toBe(8);
    expect($aiAgentSort)->toBeLessThan($dataTransferSort);
});

it('should place ai-agent before data_transfer in the ACL sort order', function () {
    $aiAgentAcl = require __DIR__.'/../../../AiAgent/Config/acl.php';
    $adminAcl = require __DIR__.'/../../src/Config/acl.php';

    $aiAgentSort = collect($aiAgentAcl)->firstWhere('key', 'ai-agent')['sort'] ?? null;
    $dataTransferSort = collect($adminAcl)->firstWhere('key', 'data_transfer')['sort'] ?? null;

    expect($aiAgentSort)->toBe(7);
    expect($dataTransferSort)->toBe(8);
});
