<?php

return [
    'warning' => [
        'title'           => 'APP_URL Uyuşmazlığı Tespit Edildi',
        'dismiss'         => 'Kapat',
        'lede-before'     => 'Frontend varlıklarınız (CSS, JS) yapılandırılmış olan değere sabitlenmiştir',
        'lede-after'      => 'Kullandığınız ana bilgisayarla eşleşecek şekilde güncelleyin, aksi takdirde stiller ve betikler yüklenmez.',
        'configured-env'  => 'Yapılandırılmış (.env)',
        'mismatch-tag'    => 'UYUŞMAZLIK',
        'actual-browser'  => 'Gerçek (tarayıcı)',
        'in-use-tag'      => 'KULLANIMDA',
        'toggle-step'     => ':number numaralı adımı aç/kapat',
        'step-1-title'    => '.env dosyanızda APP_URL değerini güncelleyin',
        'step-1-hint'     => 'Projenin .env dosyasını açın ve APP_URL satırını değiştirin.',
        'step-2-title'    => 'Uygulama önbelleğini temizleyin',
        'step-2-hint'     => 'Bunu proje kök dizininden terminalinizde çalıştırın.',
        'copy'            => 'Kopyala',
        'copied'          => 'Kopyalandı',
        'note-bold'       => 'Ardından sayfayı zorla yenileyin',
        'note-rest'       => 'böylece tarayıcı güncellenmiş varlıkları yeniden yükler.',
        'progress'        => ':total adımdan :done tanesi tamamlandı',
        'all-done'        => 'Tamamlandı',
        'powered-by'      => 'Tarafından desteklenmektedir',
        'open-source-by'  => 'Açık kaynaklı bir proje:',
        'copied-toast'    => 'Panoya kopyalandı',
        'still-mismatch'  => 'APP_URL hâlâ eşleşmiyor. .env dosyasını güncelleyin ve "php artisan optimize:clear" komutunu çalıştırın.',
        'verify-failed'   => 'APP_URL doğrulanamadı. Lütfen sayfayı yenileyin.',
        'logged-out'      => 'Oturum kapatıldı: APP_URL geçerli ana bilgisayarla eşleşmiyor. .env dosyasında APP_URL değerini güncelleyin ve "php artisan optimize:clear" komutunu çalıştırın.',
    ],

    'log' => [
        'mismatch' => 'APP_URL Uyuşmazlığı Tespit Edildi',
        'hint'     => '.env dosyasındaki APP_URL değerini istek URL\'sine güncelleyin, ardından çalıştırın: php artisan optimize:clear',
    ],
];
