<?php

use Webkul\Core\Models\CoreConfig;

function debugSettingsPayload(string $allowedIps): array
{
    $configData = collect(include __DIR__.'/../../../src/Config/system.php')
        ->first(fn ($item) => ($item['key'] ?? '') === 'general.debug.settings');

    return [
        'general' => ['debug' => ['settings' => ['enabled' => '1', 'allowed_ips' => $allowedIps]]],
        'keys'    => [json_encode($configData), json_encode($configData)],
    ];
}

it('rejects allowed ip values that are not valid addresses', function (string $invalid) {
    $this->loginAsAdmin();

    $this->post(route('admin.configuration.store', ['general', 'debug']), debugSettingsPayload($invalid))
        ->assertSessionHasErrors('general.debug.settings.allowed_ips');

    expect(CoreConfig::query()->where('code', 'general.debug.settings.allowed_ips')->value('value'))
        ->not->toBe($invalid);
})->with([
    'letters'            => 'abc.def.ghi',
    'octet out of range' => '999.999.999.999',
    'incomplete'         => '192.168.1',
    'trailing separator' => '127.0.0.1,',
    'mixed invalid'      => '127.0.0.1, 300.1.1.1',
    'bad ipv6 group'     => 'gggg::1',
    'double compression' => '1::2::3',
    'only colons'        => ':::',
    'triple colon'       => '2001:db8:::1',
    'oversized group'    => '12345::1',
    'ipv6 with port'     => '[::1]:8080',
    'zone index'         => 'fe80::1%eth0',
]);

it('accepts valid allowed ip values', function (string $valid) {
    $this->loginAsAdmin();

    $this->post(route('admin.configuration.store', ['general', 'debug']), debugSettingsPayload($valid))
        ->assertSessionHasNoErrors();

    expect(CoreConfig::query()->where('code', 'general.debug.settings.allowed_ips')->value('value'))
        ->toBe($valid);
})->with([
    'single ipv4'      => '127.0.0.1',
    'ipv4 list'        => '127.0.0.1,192.168.1.10',
    'spaced list'      => '127.0.0.1, 10.0.0.8',
    'ipv6 loopback'    => '::1',
    'ipv6 unspecified' => '::',
    'ipv6 full'        => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
    'ipv6 compressed'  => '2001:db8::8a2e:370:7334',
    'ipv6 link local'  => 'fe80::200:5aee:feaa:20a2',
    'ipv4 mapped'      => '::ffff:192.168.1.1',
    'ipv6 trailing'    => '1::',
    'ipv6 list'        => '::1,2001:db8::1',
    'mixed'            => '127.0.0.1,::1',
]);
