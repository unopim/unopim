<?php

it('blocks executable and active HTML uploads in the bundled web-server configurations', function () {
    $apache = file_get_contents(public_path('.htaccess'));
    $nginx = file_get_contents(base_path('dockerfiles/nginx.conf'));

    foreach (['php', 'phtml', 'phar', 'html', 'xhtml'] as $extension) {
        expect($apache)->toContain($extension)
            ->and($nginx)->toContain($extension);
    }

    expect($apache)->toContain('^storage/')
        ->and($nginx)->toContain('^/storage/');
});
