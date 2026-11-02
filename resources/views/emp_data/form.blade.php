@php
    $careerList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_careers')) ? $empData->careers : collect();
    $educationList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_educations')) ? $empData->educations : collect();
    $experienceList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_experiences')) ? $empData->experiences : collect();
    $trainingList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_trainings')) ? $empData->trainings : collect();
@endphp

<x-app-layout>
    {{-- Alpine JS Controller Logic --}}
    @include('emp_data.partials.scripts')

    <div class="py-6 bg-slate-50 min-h-screen" x-data="employeeFormManager()">

        {{-- FLOATING CLIENT-SIDE TOAST NOTIFICATION --}}
        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
            class="fixed top-5 right-5 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl text-xs font-semibold border backdrop-blur-md max-w-md"
            :class="toast.type === 'success' ? 'bg-emerald-600/95 text-white border-emerald-500 shadow-emerald-500/25' : 'bg-rose-600/95 text-white border-rose-500 shadow-rose-500/25'"
            style="display: none;">
            <i class="fa-solid text-base shrink-0"
                :class="toast.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
            <span class="leading-relaxed" x-text="toast.message"></span>
            <button type="button" @click="toast.show = false"
                class="ml-2 p-1 text-white/80 hover:text-white rounded-lg transition shrink-0">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Top Header Nav --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('employee.index') }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-300 shadow-xs transition"
                        title="Kembali ke Data Karyawan">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 tracking-tight"
                            x-text="empExists ? ('Kelola Data Karyawan: ' + empName) : 'Tambah Karyawan Baru'">
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5"
                            x-text="empExists ? 'Kelola identitas, riwayat karir, lama bekerja, riwayat pendidikan, dan payroll' : 'Lengkapi data identitas karyawan di awal sebelum menambahkan karir, pendidikan, dan payroll'">
                        </p>
                    </div>
                </div>

                <div x-show="empExists" class="flex items-center gap-2">
                    <a :href="empUrl()" href="{{ $empData->exists ? route('employee.show', $empData->id) : '#' }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-xs transition">
                        <i class="fa-solid fa-eye text-blue-500"></i>
                        Lihat Profil
                    </a>
                </div>
            </div>

            {{-- Alert Notifikasi Sukses Server (Jika Redirect Tradisional) --}}
            @if(session('success'))
                <div
                    class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-xs">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Notice Awal jika Karyawan Baru (Auto-Hide Saat Disimpan via AJAX) --}}
            <div x-show="!empExists" x-cloak
                class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start justify-between gap-3 shadow-xs">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-info text-amber-500 text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold block">Tahap Awal: Simpan Data Karyawan Terlebih Dahulu</span>
                        <span>Tab Karir, Pendidikan, Pengalaman Kerja, Pelatihan, dan Data BPJS saat ini terkunci.
                            Setelah Data Karyawan pertama kali disimpan, Anda dapat langsung mengelola setiap tab secara
                            mandiri tanpa refresh.</span>
                    </div>
                </div>
                <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-amber-200 text-slate-700 font-semibold whitespace-nowrap text-xs shadow-2xs">
                    <span class="text-red-500 font-bold text-sm">*</span>
                    <span>Wajib diisi</span>
                </div>
            </div>

            {{-- TAB NAVIGATION BAR --}}
            @include('emp_data.partials.tabs-navigation')

            {{-- TAB CONTENTS --}}
            @include('emp_data.partials.tab-data-karyawan')
            @include('emp_data.partials.tab-karir')
            @include('emp_data.partials.tab-pendidikan')
            @include('emp_data.partials.tab-pengalaman-kerja')
            @include('emp_data.partials.tab-pelatihan-skill')
            @include('emp_data.partials.tab-bpjs')

        </div>

        {{-- MODALS --}}
        @include('emp_data.partials.modal-confirm')
        @include('emp_data.partials.modal-career')
        @include('emp_data.partials.modal-education')
        @include('emp_data.partials.modal-experience')
        @include('emp_data.partials.modal-training')

    </div>
</x-app-layout>
