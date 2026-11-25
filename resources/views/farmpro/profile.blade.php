@extends('farmpro.layout')
@section('title', 'Profil')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Profil Pengguna</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Kelola informasi akun Anda{{ $user->role === 'admin' ? ' dan tambahkan akun pengguna baru' : '' }}.</p>
    </div>

    @if (session('status'))
        <div role="status" class="flex items-center gap-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300 shadow-sm">
            <i data-lucide="check-circle" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0"></i>
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-5 {{ $user->role === 'admin' ? 'lg:grid-cols-2' : '' }}">
        {{-- Profil Saya --}}
        <x-farmpro.card title="Informasi Profil" icon="user">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <x-farmpro.field name="name" label="Nama" :value="$user->name" required />
                <x-farmpro.field name="email" type="email" label="Email" :value="$user->email" required />
                <div>
                    <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Peran</span>
                    <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-900/40 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-400 capitalize">
                        {{ $user->role === 'admin' ? 'Administrator' : $user->role }}
                    </span>
                </div>
                <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition">
                    Simpan perubahan
                </button>
            </form>
        </x-farmpro.card>

        {{-- Input Akun (Hanya Admin) --}}
        @if ($user->role === 'admin')
            <x-farmpro.card title="Input Akun Pengguna" icon="user-plus">
                <form method="POST" action="{{ route('profile.accounts.store') }}" class="space-y-3.5">
                    @csrf
                    <x-farmpro.field name="name" label="Nama Lengkap" :value="old('name')" required />
                    <x-farmpro.field name="email" type="email" label="Email Login" :value="old('email')" required />
                    <x-farmpro.field name="role" type="select" label="Hak Akses (Role)"
                                     :options="['operator' => 'Operator', 'admin' => 'Administrator', 'warehouse' => 'Gudang (Warehouse)', 'finance' => 'Keuangan (Finance)', 'viewer' => 'Viewer (Baca saja)']"
                                     :value="old('role', 'operator')" required />
                    <x-farmpro.field name="password" type="password" label="Password" required hint="Minimal 8 karakter, memakai huruf besar dan kecil, angka, serta simbol (disimpan dengan hash)." />
                    <x-farmpro.field name="password_confirmation" type="password" label="Konfirmasi Password" required />
                    <button type="submit" class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-emerald-600 dark:hover:bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition">
                        Tambah Akun
                    </button>
                </form>
            </x-farmpro.card>
        @endif
    </div>

    {{-- Daftar Akun (Hanya Admin) --}}
    @if ($user->role === 'admin' && $accounts->isNotEmpty())
        <x-farmpro.card title="Daftar Akun Terdaftar" icon="users">
            <div class="overflow-x-auto -mx-1">
                <table class="w-full min-w-[32rem] text-left text-xs">
                    <thead class="border-b border-slate-100 dark:border-slate-700/60 bg-slate-50/80 dark:bg-slate-700/40 text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="pb-2.5 pt-2 pr-3 pl-2 font-bold uppercase tracking-wide text-[10px]">Nama</th>
                            <th class="pb-2.5 pt-2 pr-3 font-bold uppercase tracking-wide text-[10px]">Email</th>
                            <th class="pb-2.5 pt-2 pr-3 font-bold uppercase tracking-wide text-[10px]">Role</th>
                            <th class="pb-2.5 pt-2 pr-2 font-bold uppercase tracking-wide text-[10px]">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @foreach ($accounts as $acc)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition">
                                <td class="py-2.5 pr-3 pl-2 font-bold text-slate-800 dark:text-slate-100">{{ $acc->name }}</td>
                                <td class="pr-3 text-slate-600 dark:text-slate-400">{{ $acc->email }}</td>
                                <td class="pr-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold capitalize {{ $acc->role === 'admin' ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300' }}">
                                        {{ $acc->role }}
                                    </span>
                                </td>
                                <td class="pr-2 text-slate-500 dark:text-slate-400">{{ $acc->created_at?->format('d M Y') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-farmpro.card>
    @endif

    {{-- Hapus Akun --}}
    <x-farmpro.card class="border-red-200 dark:border-red-800/50">
        <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Hapus akun ini secara permanen?')">
            @csrf
            @method('DELETE')
            <h2 class="flex items-center gap-2 text-sm font-bold text-red-700 dark:text-red-400">
                <i data-lucide="triangle-alert" class="h-4 w-4"></i> Hapus Akun
            </h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Konfirmasi dengan kata sandi Anda untuk menghapus akun ini secara permanen.</p>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <input type="password" name="password" required placeholder="Masukkan kata sandi Anda"
                       class="flex-1 min-w-[14rem] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 px-3 py-2 text-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none">
                <button type="submit" class="rounded-xl bg-red-600 hover:bg-red-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition">
                    Hapus akun
                </button>
            </div>
        </form>
    </x-farmpro.card>
</div>
@endsection
