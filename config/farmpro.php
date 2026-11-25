<?php

/**
 * Konfigurasi FarmPro.
 * Struktur menu mengikuti PDF bagian 4 (Struktur Menu dan Navigasi).
 */
return [
    'name' => 'FarmPro',
    'tagline' => 'Poultry Management',
    'version' => 'v1.0.0',
    'demo' => env('FARMPRO_DEMO', true), // tampilkan label "data simulasi"

    'menu' => [
        ['label' => 'Dashboard', 'icon' => 'house', 'route' => 'dashboard'],
        ['label' => 'Data Peternakan', 'icon' => 'warehouse', 'children' => ['Komoditas', 'Kandang', 'Pemasok & Pembeli', 'Batch Ternak', 'Riwayat Pemeliharaan']],
        ['label' => 'Operasional Ternak', 'icon' => 'clipboard-list', 'children' => ['Input Harian', 'Konsumsi Pakan', 'Penimbangan Bobot', 'Kematian & Afkir', 'Produksi Telur', 'Panen & Penjualan']],
        ['label' => 'Kesehatan Ternak', 'icon' => 'heart-pulse', 'children' => ['Jadwal Vaksinasi', 'Pemberian Obat', 'Pemberian Vitamin', 'Riwayat Kesehatan', 'Monitoring Penyakit']],
        ['label' => 'Inventaris', 'icon' => 'package', 'children' => ['Stok Pakan', 'Stok Obat & Vaksin', 'Stok Vitamin', 'Barang Masuk', 'Barang Keluar', 'Riwayat Stok']],
        ['label' => 'Keuangan', 'icon' => 'circle-dollar-sign', 'children' => ['Ringkasan Keuangan', 'Pemasukan', 'Pengeluaran', 'Biaya per Batch', 'HPP', 'Laba-Rugi']],
        ['label' => 'Perencanaan', 'icon' => 'calendar-range', 'children' => ['Kalkulator Anggaran', 'Simulasi Usaha', 'Proyeksi Panen', 'Analisis BEP & ROI']],
        ['label' => 'Laporan', 'icon' => 'file-text', 'route' => 'reports.hub'],
        ['label' => 'Notifikasi', 'icon' => 'bell', 'badge' => true, 'children' => []],
        ['label' => 'Pengaturan', 'icon' => 'settings', 'children' => ['Profil Peternakan', 'Akun', 'Pengguna & Hak Akses', 'Satuan & Harga', 'Target Performa', 'Backup & Pemulihan']],
    ],
];
