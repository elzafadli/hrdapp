<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EssPasswordController extends Controller
{
    /**
     * Tampilkan halaman formulir Ubah Password ESS.
     */
    public function edit(Request $request): View
    {
        return view('ess.password.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Proses pembaruan password pengguna.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required'         => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.min'                      => 'Password baru minimal berisi 8 karakter.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('ess.password.edit')->with('success', 'Password Anda berhasil diperbarui.');
    }
}
