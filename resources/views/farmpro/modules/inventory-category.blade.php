@extends('farmpro.layout')
@section('title', Str::headline($category))
@section('content')
<div class="mb-5"><h1 class="text-2xl font-bold text-slate-900">{{ Str::headline($category) }}</h1><p class="text-sm text-slate-500">Kelola master barang, stok minimum, lot, kedaluwarsa, dan pergerakan stok.</p></div>

@if(in_array(auth()->user()->role, ['admin','warehouse']))
<x-farmpro.card title="Tambah barang inventaris" icon="package-plus" class="mb-4">
<form method="POST" action="{{ route('inventory.item.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">@csrf
<x-farmpro.field name="code" label="Kode barang" placeholder="Contoh: PKN-ST" required />
<x-farmpro.field name="name" label="Nama barang" placeholder="Contoh: Pakan Starter" required />
<x-farmpro.field name="category" type="select" label="Kategori" :options="['feed'=>'Pakan','vaccine'=>'Vaksin','medicine'=>'Obat','vitamin'=>'Vitamin','sanitation'=>'Sanitasi','equipment'=>'Peralatan','other'=>'Lainnya']" :value="$category === 'pakan' ? 'feed' : ($category === 'vitamin' ? 'vitamin' : '')" required />
<x-farmpro.field name="base_unit" label="Satuan dasar" placeholder="kg, gram, dosis, botol" required />
<x-farmpro.field name="purchase_unit" label="Satuan pembelian" placeholder="sak, box, liter" />
<x-farmpro.field name="unit_conversion" type="number" label="Konversi ke satuan dasar" :value="1" :step="0.001" required />
<x-farmpro.field name="min_stock" type="number" label="Stok minimum" :value="0" :step="0.001" required />
<x-farmpro.field name="stock" type="number" label="Stok awal" :value="0" :step="0.001" />
<x-farmpro.field name="avg_cost" type="number" label="Harga rata-rata awal" :value="0" :step="0.01" />
<x-farmpro.field name="storage_location" label="Lokasi penyimpanan" />
<div class="flex items-end"><button class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Simpan barang</button></div>
</form>
</x-farmpro.card>
@endif

<div class="grid gap-4 lg:grid-cols-3">
<x-farmpro.card title="Mutasi stok" icon="arrow-left-right" class="lg:col-span-1">
<form method="POST" action="{{ route('inventory.movement.store') }}" class="space-y-3">@csrf
<x-farmpro.field name="inventory_item_id" type="select" label="Barang" :options="$items->pluck('name','id')" required />
<x-farmpro.field name="action" type="select" label="Jenis" :options="['in'=>'Barang masuk','out'=>'Pemakaian batch','adjustment'=>'Penyesuaian','damaged'=>'Rusak','expired'=>'Kedaluwarsa','transfer'=>'Transfer']" required />
<x-farmpro.field name="quantity" type="number" label="Jumlah" :step="0.001" required />
<x-farmpro.field name="unit_cost" type="number" label="Harga per unit" :step="0.01" />
<x-farmpro.field name="movement_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
<x-farmpro.field name="batch_id" type="select" label="Batch (opsional)" :options="$batches->pluck('code','id')" />
<x-farmpro.field name="lot_number" label="Nomor lot" />
<x-farmpro.field name="expiry_date" type="date" label="Tanggal kedaluwarsa" />
<x-farmpro.field name="notes" type="textarea" label="Catatan" />
<button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Simpan mutasi</button>
</form></x-farmpro.card>

<x-farmpro.card title="Daftar stok & riwayat" icon="package" class="lg:col-span-2">
<div class="overflow-x-auto">
<table class="w-full min-w-[46rem] text-left text-sm"><thead><tr class="border-b"><th class="py-2">Kode</th><th>Barang</th><th>Kategori</th><th class="text-right">Stok</th><th>Lokasi</th><th></th></tr></thead><tbody>
@forelse($items as $item)
<tr class="border-b align-top"><td class="py-2 font-mono">{{ $item->code }}</td><td class="font-semibold">{{ $item->name }}</td><td>{{ ucfirst($item->category) }}</td><td class="text-right {{ $item->is_low ? 'font-bold text-red-600' : '' }}">{{ number_format($item->stock,3,',','.').' '.$item->base_unit }}</td><td>{{ $item->storage_location ?: '-' }}</td><td class="text-right">
@if(in_array(auth()->user()->role,['admin','warehouse']))
<details class="inline-block text-left"><summary class="cursor-pointer text-xs text-brand-700">Edit</summary>
<form method="POST" action="{{ route('inventory.item.update',$item) }}" class="mt-2 w-72 space-y-2 rounded-lg border bg-white p-3 shadow">@csrf @method('PUT')
<x-farmpro.field name="code" label="Kode" :value="$item->code" required /><x-farmpro.field name="name" label="Nama" :value="$item->name" required /><x-farmpro.field name="category" type="select" label="Kategori" :options="['feed'=>'Pakan','vaccine'=>'Vaksin','medicine'=>'Obat','vitamin'=>'Vitamin','sanitation'=>'Sanitasi','equipment'=>'Peralatan','other'=>'Lainnya']" :value="$item->category" required /><x-farmpro.field name="base_unit" label="Satuan" :value="$item->base_unit" required /><x-farmpro.field name="purchase_unit" label="Satuan beli" :value="$item->purchase_unit" /><x-farmpro.field name="unit_conversion" type="number" label="Konversi" :value="$item->unit_conversion" :step="0.001" required /><x-farmpro.field name="min_stock" type="number" label="Stok minimum" :value="$item->min_stock" :step="0.001" required /><x-farmpro.field name="storage_location" label="Lokasi" :value="$item->storage_location" /><button class="rounded-lg bg-brand-600 px-3 py-2 text-xs text-white">Simpan</button></form></details>
<form method="POST" action="{{ route('inventory.item.destroy',$item) }}" class="ml-2 inline" onsubmit="return confirm('Hapus atau nonaktifkan barang ini?')">@csrf @method('DELETE')<button class="text-xs text-red-700">Hapus</button></form>
@endif</td></tr>
@empty<tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada master barang pada kategori ini. Tambahkan barang terlebih dahulu.</td></tr>
@endforelse
</tbody></table></div>

<div class="mt-5 border-t pt-4"><h3 class="mb-2 font-semibold">Riwayat mutasi</h3><div class="overflow-x-auto"><table class="w-full min-w-[38rem] text-left text-sm"><thead><tr class="border-b"><th class="py-2">Tanggal</th><th>Barang</th><th>Arah</th><th>Alasan</th><th class="text-right">Jumlah</th><th>Lot</th></tr></thead><tbody>@forelse($movements as $m)<tr class="border-b"><td class="py-2">{{ $m->movement_date }}</td><td>{{ $m->inventoryItem?->name }}</td><td>{{ $m->direction === 'in' ? 'Masuk' : 'Keluar' }}</td><td>{{ $m->reason }}</td><td class="text-right">{{ number_format($m->quantity,3,',','.').' '.$m->inventoryItem?->base_unit }}</td><td>{{ $m->lot_number ?: '-' }}</td></tr>@empty<tr><td colspan="6" class="py-6 text-center text-slate-500">Belum ada pergerakan stok.</td></tr>@endforelse</tbody></table></div></div>
</x-farmpro.card>
</div>
@endsection
