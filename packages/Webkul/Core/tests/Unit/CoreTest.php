<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Core\Models\Channel;
use Webkul\Core\Rules\Code;

afterEach(function () {
    /**
     * Clean channels, excluding ID 1 (i.e., the default channel). A fresh instance will always have ID 1.
     */
    Channel::query()->whereNot('id', 1)->delete();
});

it('returns all channels', function () {
    $countOld = $channels = core()->getAllChannels()->count();
    $expectedChannel = Channel::factory()->create();
    $channels = core()->getAllChannels();

    expect($channels->count())->toBe($countOld);
    expect($channels->where('id', $expectedChannel->id))->toBeTruthy();
});

it('returns the current channel code when set via setter', function () {
    $expectedChannel = Channel::factory()->create();
    core()->setCurrentChannel($expectedChannel);
    $channel = core()->getCurrentChannel();

    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the default channel', function () {
    $expectedChannel = Channel::factory()->create();
    config()->set('app.channel', $expectedChannel->code);
    $channel = core()->getDefaultChannel();

    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the first channel if the default channel is not found', function () {
    $expectedChannel = Channel::first();
    config()->set('app.channel', 'wrong_channel_code');
    $channel = core()->getDefaultChannel();

    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the default channel when set via setter', function () {
    $expectedChannel = Channel::factory()->create();
    core()->setDefaultChannel($expectedChannel);
    $channel = core()->getDefaultChannel();

    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the default channel code', function () {
    $expectedChannel = Channel::factory()->create();
    core()->setDefaultChannel($expectedChannel);
    $channelCode = core()->getDefaultChannelCode();

    expect($channelCode)->toBe($expectedChannel->code);
});

it('returns the requested channel', function () {
    $expectedChannel = Channel::factory()->create();
    request()->merge([
        'channel' => $expectedChannel->code,
    ]);
    $channel = core()->getRequestedChannel();

    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the current channel if the requested channel code is not provided', function () {
    $expectedChannel = Channel::factory()->create();
    core()->setCurrentChannel($expectedChannel);
    $channel = core()->getRequestedChannel();

    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the requested channel code', function () {
    $expectedChannel = Channel::factory()->create();
    request()->merge([
        'channel' => $expectedChannel->code,
    ]);
    $channelCode = core()->getRequestedChannelCode();

    expect($channelCode)->toBe($expectedChannel->code);
});

it('returns the current channel code if requested channel code is not provided', function () {
    $expectedChannel = Channel::factory()->create();
    core()->setCurrentChannel($expectedChannel);
    $channelCode = core()->getRequestedChannelCode();

    expect($channelCode)->toBe($expectedChannel->code);
});

it('validation passes for valid code', function () {
    $rule = new Code;
    $message = null;
    $rule->validate('code', 'valid_code_123', function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBeNull();
});

it('validation passes for code with underscores', function () {
    $rule = new Code;
    $message = null;
    $rule->validate('code', 'valid_code_with_underscores', function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBeNull();
});

it('validation passes for code with numbers', function () {
    $rule = new Code;
    $message = null;
    $rule->validate('code', 'code123', function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBeNull();
});

it('validation fails for invalid code length and characters', function () {
    $data = ['code' => str_repeat('!', 192)];
    $rules = ['code' => [new Code]];
    $validator = Validator::make($data, $rules);

    expect($validator->fails())->toBeTrue();
    $messages = $validator->messages()->get('code');
    expect($messages)->toContain(trans('validation.max.string', ['attribute' => 'code', 'max' => 191]));
});

it('validation fails for code with special characters', function () {
    $data = ['code' => 'invalid-code@#$'];
    $rules = ['code' => [new Code]];
    $validator = Validator::make($data, $rules);

    expect($validator->fails())->toBeTrue();
    $messages = $validator->messages()->get('code');
    expect($messages)->toContain(trans('core::validation.code', ['attribute' => 'code']));
});

it('validation fails for code with spaces', function () {
    $data = ['code' => 'invalid code with spaces'];
    $rules = ['code' => [new Code]];
    $validator = Validator::make($data, $rules);

    expect($validator->fails())->toBeTrue();
    $messages = $validator->messages()->get('code');
    expect($messages)->toContain(trans('core::validation.code', ['attribute' => 'code']));
});
