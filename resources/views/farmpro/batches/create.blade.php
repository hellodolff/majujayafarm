@extends('farmpro.layout')
@section('title', 'Batch Baru')

@section('content')
<h1 class="mb-5 text-3xl font-bold text-slate-900">Batch Baru</h1>

@if (session('capacity_warning'))
    <div role="alert" class="mb-4 max-w-3xl rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">Peringatan kapasitas</p>
        <p class="mt-1">{{ session('capacity_warning') }}</p>
        <label class="mt-2 flex items-center gap-2"><input form="batch-form" type="checkbox" name="confirm_over_capacity" value="1" class="rounded border-amber-400"> Saya paham dan tetap ingin melanjutkan, lalu tekan Simpan lagi.</label>
    </div>
@endif

<x-farmpro.card class="max-w-3xl">
    <form id="batch-form" method="POST" action="{{ route('batches.store') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf
        <x-farmpro.field name="code" label="Kode batch" required hint="Unik dan tidak dapat dipakai ulang, mis. BR-004." />
        <x-farmpro.field name="commodity_id" type="select" label="Komoditas" :options="$commodities" required />
        <x-farmpro.field name="coop_id" type="select" label="Kandang" :options="$coops" required />
        <div>
            <x-farmpro.field name="supplier_id" type="select" label="Pemasok bibit (opsional)" :options="$suppliers" />
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('partners.index', ['type' => 'supplier']) }}" class="mt-1 inline-block text-xs font-medium text-brand-700 hover:underline">Kelola pemasok & pembeli →</a>
            @endif
        </div>
        <x-farmpro.field name="start_date" type="date" label="Tanggal masuk" :value="now()->toDateString()" required />
        <x-farmpro.field name="seed_type" type="select" label="Jenis bibit" :options="['doc' => 'DOC (anak ayam umur sehari)', 'pullet' => 'Pullet']" value="doc" required />
        <x-farmpro.field name="initial_population" type="number" label="Populasi awal (ekor)" required />
        <x-farmpro.field name="price_per_head" type="number" step="0.01" label="Harga per ekor (Rp)" required hint="Total biaya bibit otomatis dicatat satu kali sebagai pengeluaran." />
        <x-farmpro.field name="notes" type="textarea" label="Catatan" class="sm:col-span-2" />
        <div class="flex gap-3 sm:col-span-2">
            <button class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700">Simpan</button>
            <a href="{{ route('batches.index') }}" class="rounded-lg border border-slate-200 px-5 py-2 text-sm hover:bg-slate-50">Batal</a>
        </div>
    </form>
</x-farmpro.card>
@endsection
