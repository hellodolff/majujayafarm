@extends('farmpro.layout')
@section('title','Pemasok & Pembeli')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-bold text-slate-900">Pemasok &amp; Pembeli</h1><p class="text-sm text-slate-500">Kelola pemasok bibit/pakan dan pembeli hasil panen.</p></div>
    @if(auth()->user()->role === 'admin')
    <a href="#tambah-partner" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">+ Tambah Partner</a>
    @endif
</div>

<div class="mb-4 flex gap-2">
    <a href="{{ route('partners.index') }}" class="rounded-lg border px-3 py-2 text-sm {{ ! request('type') ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600' }}">Semua</a>
    <a href="{{ route('partners.index',['type'=>'supplier']) }}" class="rounded-lg border px-3 py-2 text-sm {{ request('type') === 'supplier' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600' }}">Pemasok</a>
    <a href="{{ route('partners.index',['type'=>'customer']) }}" class="rounded-lg border px-3 py-2 text-sm {{ request('type') === 'customer' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600' }}">Pembeli</a>
</div>

<div class="grid gap-4 lg:grid-cols-3">
@if(auth()->user()->role === 'admin')
<x-farmpro.card title="Tambah partner" icon="user-plus" class="lg:col-span-1" id="tambah-partner">
    <form method="POST" action="{{ route('partners.store') }}" class="space-y-3">
        @csrf
        <x-farmpro.field name="type" type="select" label="Jenis partner" :options="['supplier'=>'Pemasok','customer'=>'Pembeli']" required />
        <x-farmpro.field name="name" label="Nama" required />
        <x-farmpro.field name="phone" label="Nomor telepon" />
        <x-farmpro.field name="address" type="textarea" label="Alamat" />
        <button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Simpan partner</button>
    </form>
</x-farmpro.card>
@endif

<x-farmpro.card title="Daftar partner" icon="users" class="{{ auth()->user()->role === 'admin' ? 'lg:col-span-2' : 'lg:col-span-3' }}">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead><tr class="border-b"><th class="py-2">Jenis</th><th>Nama</th><th>Telepon</th><th>Alamat</th><th></th></tr></thead>
            <tbody>
            @forelse($partners as $partner)
                <tr class="border-b align-top">
                    <td class="py-3"><span class="rounded-full px-2 py-1 text-xs {{ $partner->type === 'supplier' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $partner->type === 'supplier' ? 'Pemasok' : 'Pembeli' }}</span></td>
                    <td class="font-semibold">{{ $partner->name }}</td>
                    <td>{{ $partner->phone ?: '-' }}</td>
                    <td>{{ $partner->address ?: '-' }}</td>
                    <td class="text-right">
                    @if(auth()->user()->role === 'admin')
                        <details class="inline-block text-left">
                            <summary class="cursor-pointer text-xs text-brand-700">Edit</summary>
                            <form method="POST" action="{{ route('partners.update',$partner) }}" class="mt-2 w-72 space-y-2 rounded-lg border bg-white p-3 shadow">
                                @csrf @method('PUT')
                                <x-farmpro.field name="type" type="select" label="Jenis" :options="['supplier'=>'Pemasok','customer'=>'Pembeli']" :value="$partner->type" required />
                                <x-farmpro.field name="name" label="Nama" :value="$partner->name" required />
                                <x-farmpro.field name="phone" label="Telepon" :value="$partner->phone" />
                                <x-farmpro.field name="address" type="textarea" label="Alamat" :value="$partner->address" />
                                <button class="rounded-lg bg-brand-600 px-3 py-2 text-xs text-white">Simpan</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('partners.destroy',$partner) }}" class="ml-2 inline" onsubmit="return confirm('Hapus partner ini?')">@csrf @method('DELETE')<button class="text-xs text-red-700">Hapus</button></form>
                    @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada pemasok atau pembeli.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $partners->links() }}</div>
</x-farmpro.card>
</div>
@endsection
