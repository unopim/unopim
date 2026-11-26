<?php

use Webkul\Core\Models\Locale;

beforeEach(function () {
    $admin = $this->loginAsAdmin();
    $admin->update([
        'ui_locale_id' => Locale::query()->where('code', 'hi_IN')->value('id'),
    ]);
});

it('renders the appearance settings in the requested locale', function () {
    $this->get(route('admin.settings.appearance.index'))
        ->assertOk()
        ->assertSeeText('दिखावट')
        ->assertSeeText('अपना लोगो और फ़ेविकॉन अपलोड करें, साथ ही चाहें तो डार्क मोड के संस्करण भी। बदलाव सहेजने के बाद लागू होते हैं।')
        ->assertSeeText('सुझाया गया इमेज रिज़ॉल्यूशन: 192px X 50px')
        ->assertDontSeeText('Appearance')
        ->assertDontSeeText('Recommended image resolution');
});

it('renders configuration fields in the requested locale', function (string $path, array $translated, array $english) {
    $response = $this->get(route('admin.settings.system.edit', $path))->assertOk();

    foreach ($translated as $text) {
        $response->assertSee($text);
    }

    foreach ($english as $text) {
        $response->assertDontSee($text);
    }
})->with([
    'email' => [
        'system.email',
        ['प्रेषक का नाम', 'प्रेषक का ईमेल', 'एडमिन का नाम', 'SMTP उपयोगकर्ता नाम'],
        ['Sender Name', 'Sender Email', 'Admin Name', 'SMTP Username'],
    ],
    'debug' => [
        'system.debug',
        ['IP-आधारित डीबग सक्षम करें', 'अनुमत IP पते (कॉमा से अलग किए गए)'],
        ['Enable IP-based Debug', 'Allowed IP Addresses (comma-separated)'],
    ],
    'microsoft sso' => [
        'system.microsoft_sso',
        ['टेनेंट ID', 'क्लाइंट ID', 'क्लाइंट सीक्रेट'],
        ['Tenant ID', 'Client ID', 'Client Secret'],
    ],
    'oidc sso' => [
        'system.oidc_sso',
        ['जारीकर्ता URL', 'बटन का लेबल', 'डिफ़ॉल्ट रोल', 'रोल मैपिंग'],
        ['Issuer URL', 'Button Label', 'Default Role', 'Role Mapping'],
    ],
]);
