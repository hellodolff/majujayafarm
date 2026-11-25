<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BatchController;
use App\Http\Controllers\Web\CommodityController;
use App\Http\Controllers\Web\CoopController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\OperationalController;
use App\Http\Controllers\Web\ModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master data
    Route::get('/data-peternakan/komoditas', [CommodityController::class, 'index'])->name('commodities.index');
    Route::get('/data-peternakan/komoditas/tambah', [CommodityController::class, 'create'])->name('commodities.create');
    Route::post('/data-peternakan/komoditas', [CommodityController::class, 'store'])->name('commodities.store');
    Route::get('/data-peternakan/komoditas/{commodity}/ubah', [CommodityController::class, 'edit'])->name('commodities.edit');
    Route::put('/data-peternakan/komoditas/{commodity}', [CommodityController::class, 'update'])->name('commodities.update');
    Route::delete('/data-peternakan/komoditas/{commodity}', [CommodityController::class, 'destroy'])->name('commodities.destroy');

    Route::get('/data-peternakan/kandang', [CoopController::class, 'index'])->name('coops.index');
    Route::get('/data-peternakan/kandang/tambah', [CoopController::class, 'create'])->name('coops.create');
    Route::post('/data-peternakan/kandang', [CoopController::class, 'store'])->name('coops.store');
    Route::get('/data-peternakan/kandang/{coop}/ubah', [CoopController::class, 'edit'])->name('coops.edit');
    Route::put('/data-peternakan/kandang/{coop}', [CoopController::class, 'update'])->name('coops.update');
    Route::delete('/data-peternakan/kandang/{coop}', [CoopController::class, 'destroy'])->name('coops.destroy');

    // Batch ternak
    Route::get('/data-peternakan/batch-ternak', [BatchController::class, 'index'])->name('batches.index');
    Route::get('/data-peternakan/batch-ternak/tambah', [BatchController::class, 'create'])->name('batches.create');
    Route::post('/data-peternakan/batch-ternak', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/data-peternakan/batch-ternak/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::put('/data-peternakan/batch-ternak/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::post('/data-peternakan/batch-ternak/{batch}/mutasi', [BatchController::class, 'movement'])->name('batches.movement');
    Route::post('/data-peternakan/batch-ternak/{batch}/selesai', [BatchController::class, 'finish'])->name('batches.finish');
    Route::post('/data-peternakan/batch-ternak/{batch}/batal', [BatchController::class, 'cancel'])->name('batches.cancel');

    // Operational and reporting modules
    Route::get('/operasional/input-harian', [OperationalController::class, 'dailyIndex'])->name('operational.daily');
    Route::post('/operasional/input-harian', [OperationalController::class, 'dailyStore'])->name('operational.daily.store');
    Route::post('/operasional/batch/{batch}/koreksi-harian', [OperationalController::class, 'dailyReverse'])->name('operational.daily.reverse');

    Route::get('/operasional/konsumsi-pakan', [OperationalController::class, 'feedIndex'])->name('feed.index');
    Route::post('/operasional/konsumsi-pakan', [OperationalController::class, 'feedStore'])->name('feed.store');
    Route::delete('/operasional/konsumsi-pakan/{feed}', [OperationalController::class, 'feedReverse'])->name('feed.reverse');

    Route::get('/operasional/penimbangan-bobot', [OperationalController::class, 'weightIndex'])->name('weight.index');
    Route::post('/operasional/penimbangan-bobot', [OperationalController::class, 'weightStore'])->name('weight.store');
    Route::delete('/operasional/penimbangan-bobot/{weight}', [OperationalController::class, 'weightReverse'])->name('weight.reverse');

    Route::get('/operasional/kematian-afkir', [OperationalController::class, 'mortalityIndex'])->name('mortality.index');
    Route::post('/operasional/kematian-afkir', [OperationalController::class, 'mortalityStore'])->name('mortality.store');
    Route::delete('/operasional/kematian-afkir/{movement}', [OperationalController::class, 'mortalityReverse'])->name('mortality.reverse');

    Route::get('/operasional/produksi-telur', [OperationalController::class, 'eggIndex'])->name('egg.index');
    Route::post('/operasional/produksi-telur', [OperationalController::class, 'eggStore'])->name('egg.store');
    Route::delete('/operasional/produksi-telur/{egg}', [OperationalController::class, 'eggReverse'])->name('egg.reverse');

    Route::get('/operasional/panen-penjualan', [OperationalController::class, 'harvestIndex'])->name('harvest.index');
    Route::post('/operasional/panen-penjualan', [OperationalController::class, 'harvestStore'])->name('operational.harvest.store');
    Route::delete('/operasional/panen-penjualan/{harvest}', [OperationalController::class, 'harvestReverse'])->name('harvest.reverse');
    Route::put('/operasional/panen-penjualan/{harvest}', [OperationalController::class, 'harvestUpdate'])->name('harvest.update');

    Route::get('/kesehatan/jadwal-vaksinasi', [OperationalController::class, 'healthIndex'])->name('health.index');
    Route::post('/kesehatan/jadwal-vaksinasi', [OperationalController::class, 'healthStore'])->name('health.store');
    Route::post('/kesehatan/riwayat', [ModuleController::class, 'healthRecordStore'])->name('health.record.store');

    Route::get('/inventaris/{category}', [ModuleController::class, 'inventoryCategory'])->name('inventory.category');
    Route::post('/inventaris/mutasi', [ModuleController::class, 'inventoryMovementStore'])->name('inventory.movement.store');

    Route::get('/keuangan/ringkasan', [ModuleController::class, 'financeIndex'])->name('finance.index');
    Route::get('/keuangan/pemasukan', [ModuleController::class, 'financeIncome'])->name('finance.income');
    Route::get('/keuangan/pengeluaran', [ModuleController::class, 'financeExpense'])->name('finance.expense');
    Route::get('/keuangan/biaya-batch', [ModuleController::class, 'batchCosts'])->name('finance.batch-costs');
    Route::get('/keuangan/hpp', [ModuleController::class, 'hppIndex'])->name('finance.hpp');
    Route::get('/keuangan/laba-rugi', [ModuleController::class, 'profitLoss'])->name('finance.profit-loss');
    Route::post('/keuangan/transaksi', [ModuleController::class, 'financeStore'])->name('finance.store');
    Route::put('/keuangan/transaksi/{transaction}', [ModuleController::class, 'financeUpdate'])->name('finance.update');
    Route::post('/keuangan/transaksi/{transaction}/reversal', [ModuleController::class, 'financeReverse'])->name('finance.reverse');

    Route::get('/perencanaan/simulasi-usaha', [OperationalController::class, 'planningIndex'])->name('planning.index');
    Route::post('/perencanaan/simulasi-usaha', [OperationalController::class, 'planningCalculate'])->name('planning.calculate');
    Route::get('/perencanaan/proyeksi-panen', [ModuleController::class, 'planningProjection'])->name('planning.projection');
    Route::get('/perencanaan/bep-roi', [ModuleController::class, 'planningAnalysis'])->name('planning.analysis');

    Route::get('/laporan', [ModuleController::class, 'reportsIndex'])->name('reports.hub');
    Route::get('/laporan/ekspor', [OperationalController::class, 'reportsIndex'])->name('reports.index');
    Route::get('/notifikasi', [ModuleController::class, 'notificationsIndex'])->name('notifications.index');
    Route::post('/notifikasi/{notification}/dibaca', [ModuleController::class, 'notificationsRead'])->name('notifications.read');

    Route::get('/pengaturan', [ModuleController::class, 'settingsIndex'])->name('settings.index');
    Route::post('/pengaturan/profil', [ModuleController::class, 'settingsProfile'])->name('settings.profile');
    Route::post('/pengaturan/pengguna', [ModuleController::class, 'settingsUser'])->name('settings.user');

    Route::get('/modul/{group}/{page?}', fn ($group, $page = null) => view('farmpro.placeholder', compact('group', 'page')))->name('modules.placeholder');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/akun', [ProfileController::class, 'storeAccount'])
        ->middleware(\App\Http\Middleware\EnsureRole::class.':admin')
        ->name('profile.accounts.store');
});
