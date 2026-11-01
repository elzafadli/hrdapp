<x-app-layout>
    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 sm:p-10 text-center">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mx-auto mb-4 border border-amber-200">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">Akun Belum Terhubung dengan Data Karyawan</h1>
                <p class="text-sm text-gray-600 mt-2 max-w-lg mx-auto leading-relaxed">
                    Akun login Anda (<span class="font-mono font-semibold text-gray-800">{{ $user->email }}</span>) saat ini belum ditautkan ke data karyawan di sistem HRD.
                </p>

                <div class="mt-6 p-4 rounded-xl bg-blue-50 border border-blue-100 text-left text-xs text-blue-900 space-y-2">
                    <div class="flex items-center gap-2 font-bold text-blue-800">
                        <i class="fa-solid fa-circle-info text-blue-600"></i>
                        <span>Langkah Selanjutnya:</span>
                    </div>
                    <p class="text-blue-700">
                        Silakan hubungi tim HRD atau Administrator sistem untuk menghubungkan akun login Anda ke profil karyawan melalui menu <strong>Data Karyawan &rarr; Edit</strong>.
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('timeline.index') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition">
                        <i class="fa-solid fa-arrow-left"></i>
                        Kembali ke Timeline
                    </a>
                    @if(auth()->user()?->role === 'superadmin' || auth()->user()?->role === 'admin')
                        <a href="{{ route('employee.index') }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition">
                            <i class="fa-solid fa-users"></i>
                            Buka Kelola Karyawan
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
