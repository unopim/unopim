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

    // Arrange
    $expectedChannel = Channel::factory()->create();

    // Act
    $channels = core()->getAllChannels();

    // Assert
    expect($channels->count())->toBe($countOld);
    expect($channels->where('id', $expectedChannel->id))->toBeTruthy();
});

it('returns the current channel code when set via setter', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    // Act
    core()->setCurrentChannel($expectedChannel);

    $channel = core()->getCurrentChannel();

    // Assert
    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the default channel', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    config()->set('app.channel', $expectedChannel->code);

    // Act
    $channel = core()->getDefaultChannel();

    // Assert
    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the first channel if the default channel is not found', function () {
    // Arrange
    $expectedChannel = Channel::first();

    config()->set('app.channel', 'wrong_channel_code');

    // Act
    $channel = core()->getDefaultChannel();

    // Assert
    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the default channel when set via setter', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    // Act
    core()->setDefaultChannel($expectedChannel);

    $channel = core()->getDefaultChannel();

    // Assert
    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the default channel code', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    // Act
    core()->setDefaultChannel($expectedChannel);

    $channelCode = core()->getDefaultChannelCode();

    // Assert
    expect($channelCode)->toBe($expectedChannel->code);
});

it('returns the requested channel', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    request()->merge([
        'channel' => $expectedChannel->code,
    ]);

    // Act
    $channel = core()->getRequestedChannel();

    // Assert
    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the current channel if the requested channel code is not provided', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    core()->setCurrentChannel($expectedChannel);

    // Act
    $channel = core()->getRequestedChannel();

    // Assert
    expect($channel->id)->toBe($expectedChannel->id);
    expect($channel->code)->toBe($expectedChannel->code);
});

it('returns the requested channel code', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    request()->merge([
        'channel' => $expectedChannel->code,
    ]);

    // Act
    $channelCode = core()->getRequestedChannelCode();

    // Assert
    expect($channelCode)->toBe($expectedChannel->code);
});

it('returns the current channel code if requested channel code is not provided', function () {
    // Arrange
    $expectedChannel = Channel::factory()->create();

    core()->setCurrentChannel($expectedChannel);

    // Act
    $channelCode = core()->getRequestedChannelCode();

    // Assert
    expect($channelCode)->toBe($expectedChannel->code);
});

it('validation passes for valid code', function () {
    // Arrange
    $rule = new Code;
    $message = null;

    // Act & Assert
    $rule->validate('code', 'valid_code_123', function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBeNull();
});

it('validation passes for code with underscores', function () {
    // Arrange
    $rule = new Code;
    $message = null;

    // Act
    $rule->validate('code', 'valid_code_with_underscores', function ($msg) use (&$message) {
        $message = $msg;
    });

    // Assert
    expect($message)->toBeNull();
});

it('validation passes for code with numbers', function () {
    // Arrange
    $rule = new Code;
    $message = null;

    // Act
    $rule->validate('code', 'code123', function ($msg) use (&$message) {
        $message = $msg;
    });

    // Assert
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
