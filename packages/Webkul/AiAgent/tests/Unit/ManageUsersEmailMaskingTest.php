<?php

use Webkul\AiAgent\Chat\Tools\ManageUsers;

/**
 * Call the private maskUserData method via reflection.
 */
function maskEmail(string $email): string
{
    $tool = new ManageUsers;
    $ref = new ReflectionMethod($tool, 'maskUserData');
    $ref->setAccessible(true);

    $user = (object) ['email' => $email];
    $masked = $ref->invoke($tool, $user);

    return $masked->email;
}

it('masks a standard email showing first 2 and last 2 characters with visible asterisks', function () {
    $masked = maskEmail('admin@example.com');

    $this->assertEquals('ad******in@example.com', $masked);
});

it('masks a longer email showing first 2 and last 2 characters with visible asterisks', function () {
    $masked = maskEmail('custom@example.com');

    $this->assertEquals('cu******om@example.com', $masked);
});

it('masks a short local part of 3 characters with first 2 chars and asterisks', function () {
    $masked = maskEmail('abc@example.com');

    $this->assertStringStartsWith('ab', $masked);
    $this->assertStringContainsString('******', $masked);
    $this->assertStringEndsWith('@example.com', $masked);
});

it('masks a 2-character local part with asterisks only', function () {
    $masked = maskEmail('ab@example.com');

    $this->assertStringContainsString('******', $masked);
    $this->assertStringEndsWith('@example.com', $masked);
});

it('masks a 1-character local part with asterisks only', function () {
    $masked = maskEmail('a@example.com');

    $this->assertEquals('******@example.com', $masked);
});

it('always contains at least 6 consecutive asterisks for visibility', function () {
    foreach (['a@x.com', 'ab@x.com', 'abc@x.com', 'admin@x.com', 'longusername@x.com'] as $email) {
        $masked = maskEmail($email);

        $this->assertStringContainsString('******', $masked, "Masking of '{$email}' should contain 6 asterisks, got: {$masked}");
        $this->assertNotEquals($email, $masked, "Email '{$email}' should be masked");
    }
});
