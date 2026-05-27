<?php

it('should store helpful feedback via the chat rate endpoint', function () {
    $this->loginAsAdmin();

    $response = $this->postJson(route('ai-agent.chat.rate'), [
        'rating'  => 'helpful',
        'message' => 'This response was great and informative.',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('ai_agent_memories', [
        'scope' => 'catalog',
        'key'   => 'message_feedback:positive',
    ]);
});

it('should store not_helpful feedback via the chat rate endpoint', function () {
    $this->loginAsAdmin();

    $response = $this->postJson(route('ai-agent.chat.rate'), [
        'rating'  => 'not_helpful',
        'message' => 'This response was incorrect.',
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('ai_agent_memories', [
        'scope' => 'catalog',
        'key'   => 'message_feedback:negative',
    ]);
});

it('should return validation error for invalid rating', function () {
    $this->loginAsAdmin();

    $this->postJson(route('ai-agent.chat.rate'), [
        'rating' => 'invalid',
    ])->assertUnprocessable();
});

it('should return validation error when rating is missing', function () {
    $this->loginAsAdmin();

    $this->postJson(route('ai-agent.chat.rate'), [])
        ->assertUnprocessable();
});

it('should update content style preference when helpful feedback is given', function () {
    $admin = $this->loginAsAdmin();

    $this->postJson(route('ai-agent.chat.rate'), [
        'rating'  => 'helpful',
        'message' => 'Clear and concise product description.',
    ]);

    $this->assertDatabaseHas('ai_agent_memories', [
        'scope'   => 'catalog',
        'key'     => 'content_style_preference',
        'user_id' => $admin->id,
    ]);
});
