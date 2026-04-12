<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Poin formula
    |--------------------------------------------------------------------------
    |
    | Rumus konversi dari nilai transaksi (Rupiah) menjadi poin bagi member.
    | Contoh: 0.001 artinya setiap Rp 1.000 = 1 poin.
    |
    */
    'points_per_rupiah' => env('POINTS_PER_RUPIAH', 0.001),
];
