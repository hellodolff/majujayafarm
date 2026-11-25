@php
    $real = ['Komoditas' => 'commodities.index', 'Kandang' => 'coops.index', 'Batch Ternak' => 'batches.index', 'Riwayat Pemeliharaan' => 'batches.index', 'Input Harian' => 'operational.daily', 'Konsumsi Pakan' => 'feed.index', 'Penimbangan Bobot' => 'weight.index', 'Kematian & Afkir' => 'mortality.index', 'Produksi Telur' => 'egg.index', 'Panen & Penjualan' => 'harvest.index', 'Jadwal Vaksinasi' => 'health.index', 'Pemberian Obat' => 'health.index', 'Pemberian Vitamin' => 'health.index', 'Riwayat Kesehatan' => 'health.index', 'Monitoring Penyakit' => 'health.index', 'Stok Pakan' => 'inventory.category', 'Stok Obat & Vaksin' => 'inventory.category', 'Stok Vitamin' => 'inventory.category', 'Riwayat Stok' => 'inventory.category', 'Barang Masuk' => 'inventory.category', 'Barang Keluar' => 'inventory.category', 'Ringkasan Keuangan' => 'finance.index', 'Pemasukan' => 'finance.income', 'Pengeluaran' => 'finance.expense', 'Biaya per Batch' => 'finance.batch-costs', 'HPP' => 'finance.hpp', 'Laba-Rugi' => 'finance.profit-loss', 'Kalkulator Anggaran' => 'planning.index', 'Simulasi Usaha' => 'planning.index', 'Proyeksi Panen' => 'planning.projection', 'Analisis BEP & ROI' => 'planning.analysis', 'Harian' => 'reports.hub', 'Bulanan' => 'reports.hub', 'Per Batch' => 'reports.hub', 'Populasi' => 'reports.hub', 'Produksi' => 'reports.hub', 'Keuangan' => 'finance.index', 'Ekspor Excel/PDF' => 'reports.index', 'Profil Peternakan' => 'settings.index', 'Akun' => 'settings.index', 'Pengguna & Hak Akses' => 'settings.index', 'Satuan & Harga' => 'settings.index', 'Target Performa' => 'commodities.index', 'Backup & Pemulihan' => 'settings.index'];
    $inventoryCategories = ['Stok Pakan' => 'pakan', 'Stok Obat & Vaksin' => 'obat-vaksin', 'Stok Vitamin' => 'vitamin', 'Barang Masuk' => 'barang-masuk', 'Barang Keluar' => 'barang-keluar', 'Riwayat Stok' => 'riwayat-stok'];
    $reportTypes = ['Harian' => 'harian', 'Bulanan' => 'bulanan', 'Per Batch' => 'per-batch', 'Populasi' => 'populasi', 'Produksi' => 'produksi', 'Inventaris' => 'inventaris', 'Keuangan' => 'keuangan'];
    $childUrl = fn ($group, $child) => isset($reportTypes[$child])
        ? route('reports.hub', ['jenis' => $reportTypes[$child]])
        : (isset($inventoryCategories[$child])
            ? route('inventory.category', $inventoryCategories[$child])
            : (isset($real[$child]) ? route($real[$child]) : route('modules.placeholder', [Str::slug($group), Str::slug($child)])));
    $unread = \App\Models\FarmNotification::whereNull('read_at')->count();
@endphp

{{-- ===== SIDEBAR HEADER BRANDING ===== --}}
<div class="sticky top-0 z-20 bg-slate-900/95 backdrop-blur-sm border-b border-slate-800">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-5 py-4">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 flex items-center justify-center shadow-lg shadow-emerald-900/40 text-white text-lg font-black flex-shrink-0">
            🥚
        </div>
        <div>
            <span class="block text-base font-black tracking-tight text-white leading-tight flex items-center gap-2">
                {{ config('farmpro.name') }}
                <span class="text-[9px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-1.5 py-0.5 rounded font-bold">PRO</span>
            </span>
            <span class="block text-[11px] text-slate-400 font-medium">{{ config('farmpro.tagline') }}</span>
        </div>
    </a>
</div>

{{-- ===== NAVIGATION ===== --}}
<nav class="flex-1 space-y-0.5 px-3 py-4">
    @foreach (config('farmpro.menu') as $m)
        @php
            $isLeaf = isset($m['route']) || empty($m['children']);
            $url = isset($m['route']) ? route($m['route']) : route('modules.placeholder', Str::slug($m['label']));
            $active = isset($m['route']) ? request()->routeIs($m['route']) : request()->is('modul/'.Str::slug($m['label']).'*');
            $open = ! $isLeaf && ($active || ($m['label'] === 'Data Peternakan' && request()->routeIs('commodities.*', 'coops.*', 'batches.*')));
        @endphp

        @if ($isLeaf)
            <a href="{{ $url }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors
                      {{ $active
                           ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/30'
                           : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i data-lucide="{{ $m['icon'] }}" class="h-4 w-4 flex-shrink-0"></i>
                <span class="flex-1 leading-tight">{{ $m['label'] }}</span>
                @if (! empty($m['badge']) && $unread)
                    <span class="grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold">{{ $unread }}</span>
                @endif
            </a>
        @else
            <div x-data="{ open: {{ $open ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors">
                    <i data-lucide="{{ $m['icon'] }}" class="h-4 w-4 flex-shrink-0"></i>
                    <span class="flex-1 text-left leading-tight">{{ $m['label'] }}</span>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-500 transition-transform duration-200" :class="open && 'rotate-180'"></i>
                </button>
                <ul x-show="open" x-cloak class="ml-7 mt-0.5 space-y-0.5 border-l border-slate-700/60 pl-3">
                    @foreach ($m['children'] as $c)
                        <li>
                            <a href="{{ $childUrl($m['label'], $c) }}"
                               class="block rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors">
                                {{ $c }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach
</nav>

{{-- ===== SIDEBAR FOOTER ===== --}}
<div class="border-t border-slate-800 px-4 py-4 bg-slate-950/50">
    <div class="flex items-center gap-2 text-[11px] text-emerald-400 font-semibold mb-1">
        <i data-lucide="info" class="h-3.5 w-3.5"></i>
        Standar: 1 Peti = 180 Butir (10kg), 1 Tray = 30 Butir (1.5kg). 

    </div>
    <p class="text-[10px] text-slate-500">{{ config('farmpro.version') }} &bull; {{ date('Y') }}</p>
</div>
