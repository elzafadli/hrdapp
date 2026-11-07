<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-2xs">
                            <i class="fa-solid fa-hand-holding-dollar text-lg"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ __('Kelola Tunjangan Karyawan') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Master alokasi nominal tunjangan perorangan karyawan untuk perhitungan penggajian (Payroll).
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            <i class="fa-solid fa-layer-group text-indigo-500"></i>
                            Komponen Payroll Allowance
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stat Cards Ringkasan Tunjangan (ESS Golden Standard) -->
            @isset($stats)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Karyawan -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Karyawan</p>
                            <h3 class="text-xl font-extrabold text-slate-900 mt-1 font-mono">
                                {{ $stats['total_employees'] ?? 0 }}
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Seluruh tenaga kerja aktif</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                            <i class="fa-solid fa-users text-lg"></i>
                        </div>
                    </div>

                    <!-- Karyawan Memiliki Tunjangan -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Menerima Tunjangan</p>
                            <h3 class="text-xl font-extrabold text-emerald-700 mt-1 font-mono">
                                {{ $stats['employees_with_allowance'] ?? 0 }}
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Memiliki nominal tunjangan</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-user-check text-lg"></i>
                        </div>
                    </div>

                    <!-- Total Nominal Tunjangan -->
                    <div class="bg-white p-4 rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50/40 via-white to-white shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">Akumulasi Tunjangan</p>
                            <h3 class="text-lg font-extrabold text-indigo-800 mt-1 font-mono">
                                Rp {{ number_format($stats['total_allowance_amount'] ?? 0, 0, ',', '.') }}
                            </h3>
                            <p class="text-[11px] text-indigo-600/80 mt-0.5 font-medium">Total seluruh karyawan</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-money-bill-wave text-lg"></i>
                        </div>
                    </div>

                    <!-- Komponen Tunjangan -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Master Komponen</p>
                            <h3 class="text-xl font-extrabold text-slate-900 mt-1 font-mono">
                                {{ $stats['total_components'] ?? 0 }} <span class="text-xs font-normal text-slate-500">Tipe</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Komponen bertipe allowance</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-list-check text-lg"></i>
                        </div>
                    </div>
                </div>
            @endisset

            <!-- Card Utama Data Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="p-6 text-gray-900">
                    @if ($message = Session::get('success'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2 mb-4" role="alert">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            <span class="font-medium">{{ $message }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <x-data-table id="emp-allowance-table" :headers="[
                            'No',
                            'Karyawan',
                            'Jabatan',
                            'Proyek',
                            'Tunjangan Aktif',
                            'Total Tunjangan',
                            'Aksi'
                        ]" />
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#emp-allowance-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('emp-allowance.index') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center w-12 text-xs' },
                        { data: 'employee_info', name: 'name', className: 'text-xs' },
                        { data: 'jabatan_name', name: 'jabatan.jabatan', className: 'text-xs' },
                        { data: 'project_name', name: 'project.name', className: 'text-xs' },
                        { data: 'allowance_count', name: 'allowance_count', orderable: false, searchable: false, className: 'text-center text-xs' },
                        { data: 'total_allowance', name: 'total_allowance', orderable: false, searchable: false, className: 'text-right text-xs' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center text-xs w-36' },
                    ],
                    order: [[1, 'asc']],
                });
            });
        </script>
    @endpush
</x-app-layout>
