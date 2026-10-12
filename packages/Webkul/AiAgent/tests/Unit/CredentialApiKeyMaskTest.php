<?php

/*
 * Guards against leaking the decrypted AI provider key: the credential edit
 * template must never echo $credential->apiKey (the encrypted cast decrypts it
 * straight into the HTML value attribute). It must render a masked placeholder.
 */
it('does not echo the decrypted api key in the credential edit template', function () {
    $template = file_get_contents(
        base_path('packages/Webkul/AiAgent/Resources/views/credentials/edit.blade.php')
    );

    expect($template)->not->toContain('$credential->apiKey');
});
