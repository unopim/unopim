<?php

return [
    'warning' => [
        'title'           => 'Rilevata discrepanza di APP_URL',
        'dismiss'         => 'Ignora',
        'lede-before'     => 'I tuoi asset frontend (CSS, JS) sono vincolati al valore configurato',
        'lede-after'      => 'Aggiornalo affinché corrisponda all\'host che stai utilizzando, altrimenti gli stili e gli script non verranno caricati.',
        'configured-env'  => 'Configurato (.env)',
        'mismatch-tag'    => 'DISCREPANZA',
        'actual-browser'  => 'Effettivo (browser)',
        'in-use-tag'      => 'IN USO',
        'toggle-step'     => 'Attiva/disattiva passaggio :number',
        'step-1-title'    => 'Aggiorna APP_URL nel tuo file .env',
        'step-1-hint'     => 'Apri il file .env del progetto e sostituisci la riga APP_URL.',
        'step-2-title'    => 'Svuota la cache dell\'applicazione',
        'step-2-hint'     => 'Esegui questo comando nel terminale dalla radice del progetto.',
        'copy'            => 'Copia',
        'copied'          => 'Copiato',
        'note-bold'       => 'Quindi ricarica forzatamente la pagina',
        'note-rest'       => 'in modo che il browser ricarichi gli asset aggiornati.',
        'progress'        => ':done di :total passaggi completati',
        'all-done'        => 'Tutto completato',
        'powered-by'      => 'Realizzato con',
        'open-source-by'  => 'Un progetto open source di',
        'copied-toast'    => 'Copiato negli appunti',
        'still-mismatch'  => 'APP_URL non corrisponde ancora. Aggiorna .env ed esegui "php artisan optimize:clear".',
        'verify-failed'   => 'Impossibile verificare APP_URL. Ricarica la pagina.',
        'logged-out'      => 'Disconnesso: APP_URL non corrisponde all\'host attuale. Aggiorna APP_URL in .env ed esegui "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'Rilevata discrepanza di APP_URL',
        'hint'     => "Aggiorna APP_URL nel file .env con l'URL della richiesta, quindi esegui: php artisan optimize:clear",
    ],
];
