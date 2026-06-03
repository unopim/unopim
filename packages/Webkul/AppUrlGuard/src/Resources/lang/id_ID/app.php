<?php

return [
    'warning' => [
        'title'           => 'Ketidakcocokan APP_URL Terdeteksi',
        'dismiss'         => 'Tutup',
        'lede-before'     => 'Aset frontend Anda (CSS, JS) terikat pada konfigurasi',
        'lede-after'      => 'Perbarui agar sesuai dengan host yang Anda gunakan, jika tidak gaya dan skrip tidak akan dimuat.',
        'configured-env'  => 'Dikonfigurasi (.env)',
        'mismatch-tag'    => 'TIDAK COCOK',
        'actual-browser'  => 'Sebenarnya (peramban)',
        'in-use-tag'      => 'DIGUNAKAN',
        'toggle-step'     => 'Alihkan langkah :number',
        'step-1-title'    => 'Perbarui APP_URL di file .env Anda',
        'step-1-hint'     => 'Buka .env proyek dan ganti baris APP_URL.',
        'step-2-title'    => 'Bersihkan cache aplikasi',
        'step-2-hint'     => 'Jalankan ini di terminal Anda dari root proyek.',
        'copy'            => 'Salin',
        'copied'          => 'Tersalin',
        'note-bold'       => 'Lalu segarkan halaman secara paksa',
        'note-rest'       => 'agar peramban memuat ulang aset yang diperbarui.',
        'progress'        => ':done dari :total langkah selesai',
        'all-done'        => 'Semua selesai',
        'powered-by'      => 'Didukung oleh',
        'open-source-by'  => 'Proyek sumber terbuka oleh',
        'copied-toast'    => 'Disalin ke papan klip',
        'still-mismatch'  => 'APP_URL masih tidak cocok. Perbarui .env dan jalankan "php artisan optimize:clear".',
        'verify-failed'   => 'Tidak dapat memverifikasi APP_URL. Silakan segarkan halaman.',
        'logged-out'      => 'Keluar: APP_URL tidak cocok dengan host saat ini. Perbarui APP_URL di .env dan jalankan "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'Ketidakcocokan APP_URL Terdeteksi',
        'hint'     => 'Perbarui APP_URL di .env ke URL permintaan, lalu jalankan: php artisan optimize:clear',
    ],
];
