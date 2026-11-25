<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRole;
use App\Models\AuditLog;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PartnerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(EnsureRole::class.':admin', except: ['index'])];
    }

    public function index(Request $request)
    {
        $type = $request->type;

        $partners = Partner::query()
            ->when(in_array($type, ['supplier', 'customer'], true), fn ($q) => $q->where('type', $type))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('farmpro.partners.index', compact('partners'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:supplier,customer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $partner = Partner::create($data);
        AuditLog::record('create_partner', $partner, null, $data);

        return back()->with('success', 'Pemasok/pembeli berhasil ditambahkan.');
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $request->validate([
            'type' => ['required', 'in:supplier,customer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $old = $partner->only(['type', 'name', 'phone', 'address']);
        $partner->update($data);
        AuditLog::record('update_partner', $partner, $old, $data);

        return back()->with('success', 'Data pemasok/pembeli berhasil diperbarui.');
    }

    public function destroy(Partner $partner)
    {
        if ($partner->batches()->exists()) {
            return back()->with('error', 'Partner sudah digunakan pada batch dan tidak dapat dihapus.');
        }

        if ($partner->sales()->exists()) {
            return back()->with('error', 'Partner sudah digunakan pada penjualan dan tidak dapat dihapus.');
        }

        $partner->delete();

        return back()->with('success', 'Pemasok/pembeli berhasil dihapus.');
    }
}
