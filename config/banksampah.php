<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Poin formula (legacy fallback)
    |--------------------------------------------------------------------------
    |
    | Rumus konversi dari nilai transaksi (Rupiah) menjadi poin bagi member.
    | Saat ini diabaikan — aturan aktif diambil dari tabel point_rules.
    |
    */
    'points_per_rupiah' => env('POINTS_PER_RUPIAH', 0.001),

    /*
    |--------------------------------------------------------------------------
    | Kontak Admin (WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Nomor WA admin untuk tombol "Hubungi Admin" di halaman merchandise.
    | Format E.164 tanpa tanda + (contoh 6281234567890). Override via env
    | ADMIN_WHATSAPP_NUMBER.
    |
    */
    'admin_whatsapp' => env('ADMIN_WHATSAPP_NUMBER', '6281234567890'),
];
