<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $accounts = $user->role === 'admin'
            ? User::orderBy('id')->get(['id', 'name', 'email', 'role', 'created_at'])
            : collect();

        return view('farmpro.profile', [
            'user' => $user,
            'accounts' => $accounts,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Hanya admin yang dapat menambahkan akun.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,operator,viewer,warehouse,finance'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        $account = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $account->role = $validated['role'];
        $account->farm_profile_id = $request->user()->farm_profile_id;
        $account->save();

        AuditLog::record('create_user', $account, null, [
            'email' => $account->email,
            'role' => $account->role,
        ]);

        return back()->with('success', "Akun {$account->name} ({$account->role}) berhasil dibuat.");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->tokens()->delete();
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $user->delete();

        return redirect()->route('login');
    }
}
