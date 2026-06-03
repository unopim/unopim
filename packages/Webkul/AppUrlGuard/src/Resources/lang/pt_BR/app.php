<?php

return [
    'warning' => [
        'title'           => 'Incompatibilidade de APP_URL detectada',
        'dismiss'         => 'Dispensar',
        'lede-before'     => 'Seus recursos de frontend (CSS, JS) estão fixados no valor configurado',
        'lede-after'      => 'Atualize-o para corresponder ao host que você está usando, caso contrário os estilos e scripts não serão carregados.',
        'configured-env'  => 'Configurado (.env)',
        'mismatch-tag'    => 'INCOMPATÍVEL',
        'actual-browser'  => 'Real (navegador)',
        'in-use-tag'      => 'EM USO',
        'toggle-step'     => 'Alternar etapa :number',
        'step-1-title'    => 'Atualize APP_URL no seu arquivo .env',
        'step-1-hint'     => 'Abra o .env do projeto e substitua a linha APP_URL.',
        'step-2-title'    => 'Limpe o cache da aplicação',
        'step-2-hint'     => 'Execute isto no seu terminal a partir da raiz do projeto.',
        'copy'            => 'Copiar',
        'copied'          => 'Copiado',
        'note-bold'       => 'Em seguida, recarregue a página totalmente',
        'note-rest'       => 'para que o navegador recarregue os recursos atualizados.',
        'progress'        => ':done de :total etapas concluídas',
        'all-done'        => 'Tudo pronto',
        'powered-by'      => 'Desenvolvido por',
        'open-source-by'  => 'Um projeto de código aberto da',
        'copied-toast'    => 'Copiado para a área de transferência',
        'still-mismatch'  => 'APP_URL ainda não corresponde. Atualize o .env e execute "php artisan optimize:clear".',
        'verify-failed'   => 'Não foi possível verificar APP_URL. Por favor, atualize a página.',
        'logged-out'      => 'Sessão encerrada: APP_URL não corresponde ao host atual. Atualize APP_URL no .env e execute "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'Incompatibilidade de APP_URL detectada',
        'hint'     => 'Atualize APP_URL no .env para a URL da requisição e execute: php artisan optimize:clear',
    ],
];
