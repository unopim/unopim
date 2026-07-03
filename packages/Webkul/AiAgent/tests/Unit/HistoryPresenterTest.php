<?php

use Webkul\AiAgent\Presenters\AgentPresenter;
use Webkul\AiAgent\Presenters\CredentialPresenter;
use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

it('agent presenter implements the history presenter interface', function () {
    expect(class_implements(AgentPresenter::class))
        ->toContain(HistoryPresenterInterface::class);
});

it('credential presenter implements the history presenter interface', function () {
    expect(class_implements(CredentialPresenter::class))
        ->toContain(HistoryPresenterInterface::class);
});

it('presents only the changed common fields as old/new pairs', function (string $presenterClass) {
    $old = [
        'name'     => 'Old Agent',
        'provider' => 'openai',
        'status'   => 1,
    ];

    $new = [
        'name'     => 'New Agent',
        'provider' => 'openai',
        'status'   => 0,
    ];

    $result = $presenterClass::representValueForHistory($old, $new, 'common');

    expect($result)->toBe([
        'name' => [
            'name' => 'name',
            'old'  => 'Old Agent',
            'new'  => 'New Agent',
        ],
        'status' => [
            'name' => 'status',
            'old'  => 1,
            'new'  => 0,
        ],
    ]);
})->with([
    'agent'      => [AgentPresenter::class],
    'credential' => [CredentialPresenter::class],
]);

it('returns an empty array when nothing changed', function () {
    $values = ['provider' => 'openai', 'model' => 'gpt-4'];

    expect(CredentialPresenter::representValueForHistory($values, $values, 'common'))->toBe([]);
});
