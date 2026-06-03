<?php

return [
    'warning' => [
        'title'           => 'Se detectó una discrepancia en APP_URL',
        'dismiss'         => 'Descartar',
        'lede-before'     => 'Tus recursos del frontend (CSS, JS) están vinculados al valor configurado',
        'lede-after'      => 'Actualízalo para que coincida con el host que estás utilizando; de lo contrario, los estilos y los scripts no se cargarán.',
        'configured-env'  => 'Configurado (.env)',
        'mismatch-tag'    => 'DISCREPANCIA',
        'actual-browser'  => 'Real (navegador)',
        'in-use-tag'      => 'EN USO',
        'toggle-step'     => 'Alternar paso :number',
        'step-1-title'    => 'Actualiza APP_URL en tu archivo .env',
        'step-1-hint'     => 'Abre el archivo .env del proyecto y reemplaza la línea APP_URL.',
        'step-2-title'    => 'Limpia la caché de la aplicación',
        'step-2-hint'     => 'Ejecuta esto en tu terminal desde la raíz del proyecto.',
        'copy'            => 'Copiar',
        'copied'          => 'Copiado',
        'note-bold'       => 'Luego recarga la página por completo',
        'note-rest'       => 'para que el navegador vuelva a cargar los recursos actualizados.',
        'progress'        => ':done de :total pasos completados',
        'all-done'        => 'Todo listo',
        'powered-by'      => 'Con tecnología de',
        'open-source-by'  => 'Un proyecto de código abierto de',
        'copied-toast'    => 'Copiado al portapapeles',
        'still-mismatch'  => 'APP_URL sigue sin coincidir. Actualiza .env y ejecuta "php artisan optimize:clear".',
        'verify-failed'   => 'No se pudo verificar APP_URL. Por favor, recarga la página.',
        'logged-out'      => 'Sesión cerrada: APP_URL no coincide con el host actual. Actualiza APP_URL en .env y ejecuta "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'Se detectó una discrepancia en APP_URL',
        'hint'     => 'Actualiza APP_URL en .env a la URL de la solicitud y luego ejecuta: php artisan optimize:clear',
    ],
];
