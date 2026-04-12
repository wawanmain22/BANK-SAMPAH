<?php

use App\Models\Article;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('/edukasi', 'public.edukasi-index')->name('public.edukasi.index');
Route::get('/edukasi/{article:slug}', function (Article $article) {
    abort_unless($article->isPublished(), 404);

    return view('public.edukasi-show', ['article' => $article]);
})->name('public.edukasi.show');

Route::view('/merchandise', 'public.merchandise-index')->name('public.merchandise.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isOwner()) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');
    Route::livewire('saldo', 'pages::nasabah.saldo')->name('nasabah.saldo');
    Route::livewire('transaksi', 'pages::nasabah.transaksi')->name('nasabah.transaksi');
    Route::livewire('pencairan-saya', 'pages::nasabah.pencairan')->name('nasabah.pencairan');
    Route::livewire('poin', 'pages::nasabah.poin')->name('nasabah.poin');
});

Route::middleware(['auth', 'verified', 'role:admin,owner'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
        Route::livewire('nasabah', 'pages::admin.nasabah.index')->name('nasabah.index');
        Route::livewire('kategori-sampah', 'pages::admin.waste-category.index')->name('waste-category.index');
        Route::livewire('harga-sampah', 'pages::admin.waste-price.index')->name('waste-price.index');
        Route::livewire('nabung', 'pages::admin.saving.index')->name('saving.index');
        Route::livewire('nabung/baru', 'pages::admin.saving.create')->name('saving.create');
        Route::livewire('release-saldo', 'pages::admin.release.index')->name('release.index');
        Route::livewire('pencairan', 'pages::admin.withdrawal.index')->name('withdrawal.index');
        Route::livewire('histori-poin', 'pages::admin.point-history.index')->name('point-history.index');
        Route::livewire('sedekah', 'pages::admin.sedekah.index')->name('sedekah.index');
        Route::livewire('sedekah/baru', 'pages::admin.sedekah.create')->name('sedekah.create');
        Route::livewire('inventory', 'pages::admin.inventory.index')->name('inventory.index');
        Route::livewire('inventory/movements', 'pages::admin.inventory.movements')->name('inventory.movements');
        Route::livewire('mitra', 'pages::admin.partner.index')->name('partner.index');
        Route::livewire('penjualan', 'pages::admin.sales.index')->name('sales.index');
        Route::livewire('penjualan/baru', 'pages::admin.sales.create')->name('sales.create');
        Route::livewire('produk', 'pages::admin.product.index')->name('product.index');
        Route::livewire('pengolahan', 'pages::admin.processing.index')->name('processing.index');
        Route::livewire('pengolahan/baru', 'pages::admin.processing.create')->name('processing.create');
        Route::livewire('artikel', 'pages::admin.article.index')->name('article.index');
        Route::livewire('tukar-poin', 'pages::admin.redemption.index')->name('redemption.index');
    });

require __DIR__.'/settings.php';
