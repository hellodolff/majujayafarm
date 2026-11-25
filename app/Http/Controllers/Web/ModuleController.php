<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRole;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Commodity;
use App\Models\DailyRecord;
use App\Models\EggProduction;
use App\Models\Expense;
use App\Models\FarmNotification;
use App\Models\FarmProfile;
use App\Models\FinancialTransaction;
use App\Models\Harvest;
use App\Models\HealthRecord;
use App\Models\HealthSchedule;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PopulationMovement;
use App\Models\Sale;
use App\Models\User;
use App\Models\WeightRecord;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ModuleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(EnsureRole::class.':admin,finance,operator,warehouse', except: [
            'healthIndex', 'healthRecordStore', 'notificationsIndex', 'notificationsRead', 'reportsIndex', 'inventoryCategory', 'inventoryMovementStore',
        ])];
    }

    public function healthIndex()
    {
        return view('farmpro.modules.health', [
            'batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(),
            'schedules' => HealthSchedule::with('batch')->orderBy('due_date')->get(),
            'records' => HealthRecord::with('batch')->latest('record_date')->take(30)->get(),
        ]);
    }

    public function healthRecordStore(Request $r, InventoryService $inventory)
    {
        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id', 'record_date' => 'required|date|before_or_equal:today',
            'type' => 'required|in:vaccine,medicine,vitamin,supplement,other', 'product_name' => 'nullable|string|max:255',
            'dose' => 'nullable|string|max:50', 'method' => 'nullable|string|max:50', 'lot_number' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date', 'withdrawal_days' => 'nullable|integer|min:0',
            'cost' => 'nullable|numeric|min:0', 'performed_by' => 'nullable|string|max:255',
            'inventory_item_id' => 'nullable|exists:inventory_items,id', 'quantity' => 'nullable|numeric|min:0.001',
            'symptoms' => 'nullable|string', 'suspected_disease' => 'nullable|string|max:255', 'cases' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($d, $inventory) {
            $cost = (float) ($d['cost'] ?? 0);
            if (! empty($d['inventory_item_id']) && (float) ($d['quantity'] ?? 0) > 0) {
                $item = InventoryItem::findOrFail($d['inventory_item_id']);
                $cost = $inventory->consume($item, (float) $d['quantity'], $d['record_date'], (int) $d['batch_id'], 'batch_usage');
            }

            $record = HealthRecord::create([
                'batch_id' => $d['batch_id'], 'record_date' => $d['record_date'], 'type' => $d['type'],
                'product_name' => $d['product_name'] ?? null, 'dose' => $d['dose'] ?? null,
                'method' => $d['method'] ?? null, 'lot_number' => $d['lot_number'] ?? null,
                'expiry_date' => $d['expiry_date'] ?? null, 'withdrawal_days' => $d['withdrawal_days'] ?? null,
                'cost' => $cost, 'performed_by' => $d['performed_by'] ?? null,
                'symptoms' => $d['symptoms'] ?? null, 'suspected_disease' => $d['suspected_disease'] ?? null,
                'cases' => $d['cases'] ?? null, 'notes' => $d['notes'] ?? null,
            ]);

            if ($cost > 0) {
                FinancialTransaction::create([
                    'trx_date' => $d['record_date'], 'type' => 'expense', 'category' => 'health_'.$d['type'],
                    'amount' => $cost, 'batch_id' => $d['batch_id'], 'source_type' => HealthRecord::class,
                    'source_id' => $record->id, 'description' => 'Intervensi kesehatan '.$record->product_name,
                ]);
            }
            AuditLog::record('create_health', $record);
        });

        return back()->with('success', 'Catatan kesehatan berhasil disimpan.');
    }

    public function inventoryCategory(string $category)
    {
        $allowed = ['pakan', 'obat-vaksin', 'vitamin', 'barang-masuk', 'barang-keluar', 'riwayat-stok'];
        abort_unless(in_array($category, $allowed, true), 404);

        $categoryMap = ['pakan' => 'feed', 'obat-vaksin' => ['vaccine', 'medicine'], 'vitamin' => 'vitamin'];
        $itemsQuery = InventoryItem::where('is_active', true)->orderBy('category')->orderBy('name');
        if (isset($categoryMap[$category])) {
            $map = (array) $categoryMap[$category];
            $itemsQuery->whereIn('category', $map);
        }

        return view('farmpro.modules.inventory-category', [
            'category' => $category,
            'items' => $itemsQuery->get(),
            'movements' => InventoryMovement::with('inventoryItem')->latest('movement_date')->latest('id')->take(100)->get(),
            'batches' => Batch::active()->orderByDesc('start_date')->get(),
        ]);
    }

    public function inventoryMovementStore(Request $r, InventoryService $inventory)
    {
        $d = $r->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id', 'action' => 'required|in:in,out,adjustment,damaged,expired,transfer',
            'quantity' => 'required|numeric|gt:0', 'unit_cost' => 'nullable|numeric|min:0',
            'movement_date' => 'required|date', 'batch_id' => 'nullable|exists:batches,id',
            'lot_number' => 'nullable|string|max:50', 'expiry_date' => 'nullable|date', 'notes' => 'nullable|string',
        ]);
        $item = InventoryItem::findOrFail($d['inventory_item_id']);

        if ($d['action'] === 'in') {
            $inventory->purchase($item, (float) $d['quantity'], (float) ($d['unit_cost'] ?? 0), $d['movement_date'], $d);
        } else {
            $reason = match ($d['action']) { 'damaged' => 'damaged', 'expired' => 'expired', 'transfer' => 'transfer', default => 'adjustment' };
            $inventory->consume($item, (float) $d['quantity'], $d['movement_date'], $d['batch_id'] ?? null, $reason);
        }

        return back()->with('success', 'Mutasi stok berhasil dicatat.');
    }

    public function financeIndex()
    {
        $trx = FinancialTransaction::latest('trx_date')->latest('id')->take(200)->get();
        $income = (float) $trx->where('type', 'income')->sum('amount');
        $expense = (float) $trx->where('type', 'expense')->sum('amount');
        return view('farmpro.modules.finance', compact('trx', 'income', 'expense'));
    }

    public function financeIncome()
    {
        $trx = FinancialTransaction::where('type', 'income')->latest('trx_date')->latest('id')->paginate(25)->withQueryString();
        $batches = Batch::active()->orderByDesc('start_date')->pluck('code', 'id');
        return view('farmpro.modules.finance-transactions', [
            'transactions' => $trx,
            'batches' => $batches,
            'type' => 'income',
            'title' => 'Pemasukan',
            'categories' => ['Penjualan Ayam', 'Penjualan Telur', 'Penjualan Pupuk/Kotoran', 'Penerimaan Lain'],
        ]);
    }

    public function financeExpense()
    {
        $trx = FinancialTransaction::where('type', 'expense')->latest('trx_date')->latest('id')->paginate(25)->withQueryString();
        $batches = Batch::active()->orderByDesc('start_date')->pluck('code', 'id');
        return view('farmpro.modules.finance-transactions', [
            'transactions' => $trx,
            'batches' => $batches,
            'type' => 'expense',
            'title' => 'Pengeluaran',
            'categories' => ['DOC/Pullet', 'Pakan', 'Vaksin/Obat', 'Vitamin/Suplemen', 'Tenaga Kerja', 'Listrik & Air', 'Sekam/Litter', 'Transportasi', 'Perawatan Kandang', 'Penyusutan', 'Biaya Lain'],
        ]);
    }

    public function financeStore(Request $r)
    {
        $d = $r->validate([
            'trx_date' => 'required|date', 'type' => 'required|in:income,expense,transfer',
            'category' => 'required|string|max:40', 'amount' => 'required|numeric|gt:0',
            'payment_method' => 'nullable|string|max:20', 'batch_id' => 'nullable|exists:batches,id',
            'description' => 'nullable|string',
        ]);
        $trx = FinancialTransaction::create($d);
        AuditLog::record('finance_'.$d['type'], $trx);
        return back()->with('success', 'Transaksi keuangan berhasil dicatat.');
    }

    public function financeUpdate(Request $r, FinancialTransaction $transaction)
    {
        $d = $r->validate([
            'trx_date' => 'required|date',
            'category' => 'required|string|max:40',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'nullable|string|max:20',
            'batch_id' => 'nullable|exists:batches,id',
            'description' => 'nullable|string',
        ]);

        if (! is_null($transaction->source_type) || $transaction->reversed_of_id) {
            return back()->with('error', 'Transaksi sumber otomatis tidak dapat diedit langsung. Gunakan koreksi pada modul sumber.');
        }

        $old = $transaction->only(['trx_date', 'category', 'amount', 'payment_method', 'batch_id', 'description']);
        $transaction->update($d);
        AuditLog::record('update_finance_'.$transaction->type, $transaction, $old, $transaction->only(array_keys($d)));

        return back()->with('success', 'Transaksi keuangan berhasil diperbarui.');
    }

    public function financeReverse(FinancialTransaction $transaction)
    {
        if (! is_null($transaction->source_type) || $transaction->reversed_of_id) {
            return back()->with('error', 'Transaksi sumber otomatis harus dikoreksi pada modul sumbernya.');
        }

        if (FinancialTransaction::where('reversed_of_id', $transaction->id)->exists()) {
            return back()->with('error', 'Transaksi ini sudah pernah dikoreksi/reversal.');
        }

        FinancialTransaction::create([
            'trx_date' => now()->toDateString(),
            'type' => $transaction->type,
            'category' => $transaction->category,
            'amount' => -(float) abs($transaction->amount),
            'payment_method' => $transaction->payment_method,
            'batch_id' => $transaction->batch_id,
            'description' => 'Reversal transaksi keuangan #'.$transaction->id,
            'reversed_of_id' => $transaction->id,
        ]);

        AuditLog::record('reverse_finance_'.$transaction->type, $transaction, null, ['reversed_of_id' => $transaction->id]);

        return back()->with('success', 'Transaksi dikoreksi dengan reversal dan histori tetap tersimpan.');
    }

    public function batchCosts()
    {
        $costs = Batch::with('commodity')->orderByDesc('start_date')->get()->map(function (Batch $b) {
            $expense = (float) FinancialTransaction::where('batch_id', $b->id)->where('type', 'expense')->sum('amount');
            $income = (float) Sale::where('batch_id', $b->id)->sum('total');
            return ['batch' => $b, 'expense' => $expense, 'income' => $income, 'profit' => $income - $expense];
        });
        return view('farmpro.modules.batch-costs', compact('costs'));
    }

    public function hppIndex()
    {
        $rows = Batch::with('commodity')->orderByDesc('start_date')->get()->map(function (Batch $b) {
            $cost = (float) FinancialTransaction::where('batch_id', $b->id)->where('type', 'expense')->sum('amount');
            $unit = $b->commodity->product_unit ?: 'ekor';
            $volume = $unit === 'kg' ? (float) (\App\Models\Harvest::where('batch_id', $b->id)->sum('total_weight_kg')) : $b->initial_population;
            return ['batch' => $b, 'cost' => $cost, 'volume' => $volume, 'unit' => $unit, 'hpp' => $volume > 0 ? round($cost / $volume, 2) : null];
        });
        return view('farmpro.modules.hpp', compact('rows'));
    }

    public function profitLoss(Request $r)
    {
        $month = $r->filled('bulan') ? $r->bulan : now()->format('Y-m');
        $income = (float) FinancialTransaction::where('type', 'income')->where('trx_date', 'like', $month.'%')->sum('amount');
        $expense = (float) FinancialTransaction::where('type', 'expense')->where('trx_date', 'like', $month.'%')->sum('amount');
        return view('farmpro.modules.profit-loss', compact('month', 'income', 'expense'));
    }

    public function planningProjection()
    {
        $batches = Batch::with('commodity')->orderByDesc('start_date')->take(20)->get();
        return view('farmpro.modules.planning-projection', compact('batches'));
    }

    public function planningAnalysis()
    {
        return view('farmpro.modules.planning-analysis');
    }

    public function reportsIndex(Request $r)
    {
        $data = $this->filteredReports($r);

        return view('farmpro.modules.reports-hub', $data);
    }

    public function filteredReports(Request $r): array
    {
        $r->validate([
            'format' => 'nullable|in:view,csv,pdf',
            'jenis' => 'nullable|in:harian,bulanan,per-batch,populasi,produksi,inventaris,keuangan',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'bulan' => 'nullable|date_format:Y-m',
            'batch_id' => 'nullable|exists:batches,id',
            'commodity_id' => 'nullable|exists:commodities,id',
        ]);

        $jenis = $r->jenis ?: 'harian';
        $start = $r->tanggal_mulai
            ? \Carbon\Carbon::parse($r->tanggal_mulai)->startOfDay()
            : ($r->bulan ? \Carbon\Carbon::createFromFormat('Y-m', $r->bulan)->startOfMonth() : now()->startOfMonth());
        $end = $r->tanggal_selesai
            ? \Carbon\Carbon::parse($r->tanggal_selesai)->endOfDay()
            : ($r->bulan ? $start->copy()->endOfMonth() : now()->endOfDay());

        $batches = Batch::with(['commodity', 'coop'])
            ->when($r->batch_id, fn ($q, $id) => $q->whereKey($id))
            ->when($r->commodity_id, fn ($q, $id) => $q->where('commodity_id', $id))
            ->orderByDesc('start_date')
            ->get();
        $batchIds = $batches->pluck('id');

        $rows = collect();
        $columns = [];
        $title = 'Laporan Harian';

        if ($jenis === 'harian') {
            $title = 'Laporan Harian';
            $columns = ['Tanggal', 'Batch', 'Mati', 'Afkir', 'Pakan (kg)', 'Bobot (g)', 'Telur', 'Catatan'];
            $rows = DailyRecord::with('batch:id,code')->whereIn('batch_id', $batchIds)
                ->whereBetween('record_date', [$start->toDateString(), $end->toDateString()])
                ->latest('record_date')->get()->map(fn ($r) => [
                    'Tanggal' => \Carbon\Carbon::parse($r->record_date)->format('d/m/Y'),
                    'Batch' => $r->batch?->code,
                    'Mati' => $r->deaths,
                    'Afkir' => $r->culls,
                    'Pakan (kg)' => (float) FeedRecord::where('batch_id', $r->batch_id)->whereDate('record_date', $r->record_date)->sum('feed_kg'),
                    'Bobot (g)' => optional(WeightRecord::where('batch_id', $r->batch_id)->whereDate('record_date', $r->record_date)->latest('id')->first())->avg_weight_g,
                    'Telur' => (int) EggProduction::where('batch_id', $r->batch_id)->whereDate('record_date', $r->record_date)->sum('total_eggs'),
                    'Catatan' => $r->notes,
                ]);
        } elseif ($jenis === 'bulanan') {
            $title = 'Laporan Bulanan';
            $columns = ['Bulan', 'Pemasukan', 'Pengeluaran', 'Laba/Rugi'];
            $monthStart = $start->copy()->startOfMonth();
            $monthEnd = $end->copy()->endOfMonth();
            for ($month = $monthStart->copy(); $month <= $monthEnd;) {
                $from = $month->copy()->startOfMonth();
                $to = $month->copy()->endOfMonth();
                $income = (float) FinancialTransaction::where('type', 'income')->whereBetween('trx_date', [$from->toDateString(), $to->toDateString()])->when($r->batch_id, fn ($q, $id) => $q->where('batch_id', $id))->sum('amount');
                $expense = (float) FinancialTransaction::where('type', 'expense')->whereBetween('trx_date', [$from->toDateString(), $to->toDateString()])->when($r->batch_id, fn ($q, $id) => $q->where('batch_id', $id))->sum('amount');
                $rows->push(['Bulan' => $month->translatedFormat('F Y'), 'Pemasukan' => $income, 'Pengeluaran' => $expense, 'Laba/Rugi' => $income - $expense]);
                $month = $month->addMonth();
            }
        } elseif ($jenis === 'per-batch') {
            $title = 'Laporan Per Batch';
            $columns = ['Batch', 'Komoditas', 'Kandang', 'Populasi Awal', 'Populasi Sekarang', 'Status', 'Umur (hari)'];
            $rows = $batches->map(fn ($b) => ['Batch' => $b->code, 'Komoditas' => $b->commodity?->name, 'Kandang' => $b->coop?->name, 'Populasi Awal' => $b->initial_population, 'Populasi Sekarang' => $b->current_population, 'Status' => ucfirst($b->status), 'Umur (hari)' => $b->age_days]);
        } elseif ($jenis === 'populasi') {
            $title = 'Laporan Populasi';
            $columns = ['Batch', 'Tanggal', 'Jenis', 'Jumlah', 'Alasan'];
            $rows = PopulationMovement::with('batch:id,code')->whereIn('batch_id', $batchIds)->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])->latest('movement_date')->get()->map(fn ($m) => ['Batch' => $m->batch?->code, 'Tanggal' => \Carbon\Carbon::parse($m->movement_date)->format('d/m/Y'), 'Jenis' => match ($m->type) { 'death' => 'Kematian', 'cull' => 'Afkir', 'harvest' => 'Panen', 'transfer_in' => 'Transfer masuk', 'transfer_out' => 'Transfer keluar', default => ucfirst($m->type) }, 'Jumlah' => $m->quantity, 'Alasan' => $m->reason]);
        } elseif ($jenis === 'produksi') {
            $title = 'Laporan Produksi';
            $columns = ['Tanggal', 'Batch', 'Telur Total', 'Layak Jual', 'Telur Retak', 'Kotor', 'Ditolak', 'Bobot Telur (kg)'];
            $rows = EggProduction::with('batch:id,code')->whereIn('batch_id', $batchIds)->whereBetween('record_date', [$start->toDateString(), $end->toDateString()])->latest('record_date')->get()->map(fn ($e) => ['Tanggal' => \Carbon\Carbon::parse($e->record_date)->format('d/m/Y'), 'Batch' => $e->batch?->code, 'Telur Total' => $e->total_eggs, 'Layak Jual' => $e->sellable_eggs, 'Telur Retak' => $e->cracked_eggs, 'Kotor' => $e->dirty_eggs, 'Ditolak' => $e->reject_eggs, 'Bobot Telur (kg)' => $e->egg_weight_kg]);
        } elseif ($jenis === 'inventaris') {
            $title = 'Laporan Inventaris';
            $columns = ['Tanggal', 'Barang', 'Arah', 'Alasan', 'Jumlah', 'Lot', 'Biaya/Unit'];
            $rows = InventoryMovement::with('inventoryItem')->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])->when($r->batch_id, fn ($q, $id) => $q->where('batch_id', $id))->latest('movement_date')->get()->map(fn ($m) => ['Tanggal' => \Carbon\Carbon::parse($m->movement_date)->format('d/m/Y'), 'Barang' => $m->inventoryItem?->name, 'Arah' => $m->direction === 'in' ? 'Masuk' : 'Keluar', 'Alasan' => $m->reason, 'Jumlah' => $m->quantity, 'Lot' => $m->lot_number, 'Biaya/Unit' => $m->unit_cost]);
        } else {
            $title = 'Laporan Keuangan';
            $columns = ['Tanggal', 'Tipe', 'Kategori', 'Nominal', 'Batch', 'Keterangan'];
            $rows = FinancialTransaction::with('batch:id,code')->whereBetween('trx_date', [$start->toDateString(), $end->toDateString()])->when($r->batch_id, fn ($q, $id) => $q->where('batch_id', $id))->latest('trx_date')->get()->map(fn ($t) => ['Tanggal' => \Carbon\Carbon::parse($t->trx_date)->format('d/m/Y'), 'Tipe' => match ($t->type) { 'income' => 'Pemasukan', 'expense' => 'Pengeluaran', default => 'Transfer' }, 'Kategori' => $t->category, 'Nominal' => $t->amount, 'Batch' => $t->batch?->code, 'Keterangan' => $t->description]);
        }

        return [
            'jenis' => $jenis,
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'batches' => Batch::with('commodity')->orderByDesc('start_date')->get(),
            'commodities' => Commodity::orderBy('name')->get(),
        ];
    }

    public function notificationsIndex()
    {
        return view('farmpro.modules.notifications', [
            'items' => FarmNotification::latest()->take(100)->get(),
            'lowStock' => InventoryItem::whereColumn('stock', '<=', 'min_stock')->where('is_active', true)->get(),
            'schedules' => HealthSchedule::with('batch')->where('status', 'pending')->orderBy('due_date')->get(),
        ]);
    }

    public function notificationsRead(FarmNotification $notification)
    {
        $notification->update(['read_at' => now()]);
        return back();
    }

    public function settingsIndex()
    {
        $profile = FarmProfile::first();
        $users = User::orderBy('name')->get();
        $commodities = Commodity::orderBy('name')->get();
        return view('farmpro.modules.settings', compact('profile', 'users', 'commodities'));
    }

    public function settingsProfile(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:255', 'address' => 'nullable|string|max:255', 'timezone' => 'required|string|max:40', 'currency' => 'required|string|size:3']);
        $profile = FarmProfile::firstOrCreate(['id' => 1]);
        $profile->update($d);
        return back()->with('success', 'Profil peternakan diperbarui.');
    }

    public function settingsUser(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:8', 'role' => 'required|in:admin,operator,viewer,warehouse,finance']);
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'password' => Hash::make($d['password']), 'role' => $d['role']]);
        AuditLog::record('create_user', $user, null, ['role' => $d['role']]);
        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function inventoryItemStore(Request $r)
    {
        $d = $r->validate([
            'code' => 'required|string|max:30|unique:inventory_items,code',
            'name' => 'required|string|max:255',
            'category' => 'required|in:feed,vaccine,medicine,vitamin,sanitation,equipment,other',
            'base_unit' => 'required|string|max:15',
            'purchase_unit' => 'nullable|string|max:15',
            'unit_conversion' => 'required|numeric|gt:0',
            'min_stock' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'avg_cost' => 'nullable|numeric|min:0',
            'storage_location' => 'nullable|string|max:255',
        ]);

        $item = InventoryItem::create([
            'code' => strtoupper($d['code']),
            'name' => $d['name'],
            'category' => $d['category'],
            'base_unit' => $d['base_unit'],
            'purchase_unit' => $d['purchase_unit'] ?: null,
            'unit_conversion' => $d['unit_conversion'],
            'min_stock' => $d['min_stock'],
            'stock' => $d['stock'] ?? 0,
            'avg_cost' => $d['avg_cost'] ?? 0,
            'storage_location' => $d['storage_location'] ?: null,
            'is_active' => true,
        ]);

        if ((float) $item->stock > 0) {
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'direction' => 'in',
                'reason' => 'adjustment',
                'quantity' => $item->stock,
                'unit_cost' => $item->avg_cost,
                'movement_date' => now()->toDateString(),
                'lot_number' => null,
                'expiry_date' => null,
            ]);
        }

        AuditLog::record('create_inventory_item', $item);

        return back()->with('success', 'Barang inventaris berhasil ditambahkan.');
    }

    public function inventoryItemUpdate(Request $r, InventoryItem $item)
    {
        $d = $r->validate([
            'code' => ['required', 'string', 'max:30', \Illuminate\Validation\Rule::unique('inventory_items', 'code')->ignore($item->id)],
            'name' => 'required|string|max:255',
            'category' => 'required|in:feed,vaccine,medicine,vitamin,sanitation,equipment,other',
            'base_unit' => 'required|string|max:15',
            'purchase_unit' => 'nullable|string|max:15',
            'unit_conversion' => 'required|numeric|gt:0',
            'min_stock' => 'required|numeric|min:0',
            'storage_location' => 'nullable|string|max:255',
        ]);

        $old = $item->only(['code', 'name', 'category', 'base_unit', 'purchase_unit', 'unit_conversion', 'min_stock', 'storage_location']);
        $d['code'] = strtoupper($d['code']);
        $item->update($d);
        AuditLog::record('update_inventory_item', $item, $old, $d);

        return back()->with('success', 'Data barang berhasil diperbarui.');
    }

    public function inventoryItemDestroy(InventoryItem $item)
    {
        if ($item->movements()->exists()) {
            $item->update(['is_active' => false]);
            AuditLog::record('deactivate_inventory_item', $item);
            return back()->with('success', 'Barang dinonaktifkan karena memiliki riwayat mutasi/stok.');
        }

        $item->delete();
        AuditLog::record('delete_inventory_item', $item);

        return back()->with('success', 'Barang inventaris berhasil dihapus.');
    }
}
