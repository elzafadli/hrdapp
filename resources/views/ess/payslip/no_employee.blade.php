<x-app-layout>
    <div class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
                <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Profil Karyawan Belum Terhubung</h2>
                <p class="text-sm text-gray-600 max-w-md mx-auto mb-6">
                    Akun login Anda ({{ Auth::user()->name }}) belum terhubung dengan data master karyawan (EmpData). 
                    Silakan hubungi administrator HRD untuk mengaitkan akun Anda dengan profil karyawan agar dapat melihat slip gaji mandiri.
                </p>
                <a href="{{ route('timeline.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-sm transition shadow-sm">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
