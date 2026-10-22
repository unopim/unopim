<?php

use Illuminate\Support\Facades\Route;

it('constrains every {id} route param to numeric so non-numeric ids 404 instead of throwing a 500 TypeError', function (string $name) {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull();
    expect($route->wheres['id'] ?? null)->toBe('[0-9]+');
})->with([
    'ai-agent.agents.edit',
    'ai-agent.agents.update',
    'ai-agent.agents.destroy',
    'ai-agent.conversations.show',
    'ai-agent.conversations.destroy',
    'ai-agent.dashboard.rollback',
    'ai-agent.dashboard.notifications.dismiss',
]);

it('does not offer an approvals permission that guards no route', function () {
    expect(collect(config('acl'))->pluck('key'))->not->toContain('ai-agent.approvals');
    expect(Route::has('ai-agent.approvals.index'))->toBeFalse();
});
