<x-app-layout>
    <div class="py-6 sm:py-8 bg-slate-50 min-h-screen"
        x-data="{
            isSubmitting: false,
            showCurrent: false,
            showNew: false,
            showConfirm: false,
            currentPassword: '',
            newPassword: '',
            confirmPassword: '',
            get hasMinLength() {
                return this.newPassword.length >= 8;
            },
            get isConfirmed() {
                return this.newPassword.length > 0 && this.newPassword === this.confirmPassword;
            }
        }">

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message Success -->
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-2xl text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-check text-base"></i>
                        </div>
                        <div>
                            <p class="font-bold text-emerald-900">Berhasil!</p>
                            <p class="text-xs text-emerald-700 mt-0.5">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Flash Message Error -->
            @if (session('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3.5 rounded-2xl text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-exclamation text-base"></i>
                        </div>
                        <div>
                            <p class="font-bold text-rose-900">Terjadi Kesalahan</p>
                            <p class="text-xs text-rose-700 mt-0.5">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Top Header & Breadcrumb Navigation -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <a href="{{ route('timeline.index') }}"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-300 shadow-xs transition">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Ubah Password</h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <i class="fa-solid fa-shield-halved text-[9px]"></i> Keamanan Akun
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Kelola kata sandi akun login Employee Self Service (ESS) Anda.</p>
                    </div>
                </div>
            </div>

            <!-- Banner Ketentuan & Keamanan -->
            <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 text-xs text-amber-900 shadow-xs">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200 mt-0.5">
                    <i class="fa-solid fa-key text-sm"></i>
                </div>
                <div class="space-y-1">
                    <h4 class="font-bold text-amber-950 text-sm">Petunjuk Keamanan Password:</h4>
                    <ul class="list-disc list-inside space-y-0.5 text-amber-800">
                        <li>Password baru minimal memiliki panjang <strong>8 karakter</strong>.</li>
                        <li>Gunakan kombinasi huruf besar, huruf kecil, angka, atau simbol agar akun lebih terlindungi.</li>
                        <li>Jangan bagikan password Anda kepada pihak lain atau rekan kerja.</li>
                    </ul>
                </div>
            </div>

            <!-- Card Formulir Ubah Password -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4.5 border-b border-gray-100 bg-slate-50/60 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Formulir Kata Sandi Baru</h3>
                            <p class="text-[11px] text-gray-500">Akun: <span class="font-mono font-semibold text-gray-700">{{ $user->email }}</span></p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('ess.password.update') }}" class="p-6 sm:p-8 space-y-6" @submit="isSubmitting = true">
                    @csrf
                    @method('PUT')

                    <!-- Password Saat Ini -->
                    <div>
                        <x-input-label for="current_password" value="Password Saat Ini" class="font-semibold text-gray-700" />
                        <div class="relative mt-1.5">
                            <x-text-input
                                id="current_password"
                                name="current_password"
                                ::type="showCurrent ? 'text' : 'password'"
                                class="block w-full pr-10 text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                autocomplete="current-password"
                                placeholder="Masukkan password yang aktif saat ini"
                                x-model="currentPassword"
                                required
                            />
                            <button type="button"
                                @click="showCurrent = !showCurrent"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition"
                                tabindex="-1">
                                <i class="fa-solid" :class="showCurrent ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('current_password')" class="mt-1.5" />
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 pt-2"></div>

                    <!-- Password Baru -->
                    <div>
                        <x-input-label for="password" value="Password Baru" class="font-semibold text-gray-700" />
                        <div class="relative mt-1.5">
                            <x-text-input
                                id="password"
                                name="password"
                                ::type="showNew ? 'text' : 'password'"
                                class="block w-full pr-10 text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                autocomplete="new-password"
                                placeholder="Minimal 8 karakter kombinasi"
                                x-model="newPassword"
                                required
                            />
                            <button type="button"
                                @click="showNew = !showNew"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition"
                                tabindex="-1">
                                <i class="fa-solid" :class="showNew ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                    </div>

                    <!-- Konfirmasi Password Baru -->
                    <div>
                        <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" class="font-semibold text-gray-700" />
                        <div class="relative mt-1.5">
                            <x-text-input
                                id="password_confirmation"
                                name="password_confirmation"
                                ::type="showConfirm ? 'text' : 'password'"
                                class="block w-full pr-10 text-sm rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                autocomplete="new-password"
                                placeholder="Ketik ulang password baru Anda"
                                x-model="confirmPassword"
                                required
                            />
                            <button type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition"
                                tabindex="-1">
                                <i class="fa-solid" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
                    </div>

                    <!-- Checklist Indikator Realtime -->
                    <div class="p-3.5 bg-slate-50 rounded-xl border border-gray-200/80 text-xs space-y-2" x-show="newPassword.length > 0">
                        <span class="font-bold text-gray-700 block text-[11px] uppercase tracking-wider">Status Validasi Password:</span>
                        <div class="flex items-center gap-2" :class="hasMinLength ? 'text-emerald-600 font-semibold' : 'text-gray-400'">
                            <i class="fa-solid" :class="hasMinLength ? 'fa-circle-check text-emerald-500' : 'fa-circle-dot text-gray-300'"></i>
                            <span>Minimal 8 karakter (Saat ini: <span x-text="newPassword.length"></span> karakter)</span>
                        </div>
                        <div class="flex items-center gap-2" :class="isConfirmed ? 'text-emerald-600 font-semibold' : 'text-gray-400'">
                            <i class="fa-solid" :class="isConfirmed ? 'fa-circle-check text-emerald-500' : 'fa-circle-dot text-gray-300'"></i>
                            <span>Konfirmasi password baru cocok</span>
                        </div>
                    </div>

                    <!-- Tombol Aksi di Bawah Kanan (Sesuai Golden Base Template) -->
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <a href="{{ route('timeline.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold shadow-xs transition">
                            Batal
                        </a>
                        <button type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-xs transition disabled:opacity-60 disabled:cursor-not-allowed">
                            <template x-if="!isSubmitting">
                                <span class="inline-flex items-center gap-2">
                                    <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Password Baru
                                </span>
                            </template>
                            <template x-if="isSubmitting">
                                <span class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    Menyimpan...
                                </span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
