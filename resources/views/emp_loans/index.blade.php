<x-app-layout>
    <div class="py-6">
        <div class="w-full max-w-[96rem] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        @if($employee)
                            <a href="{{ route('employee.index') }}"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition"
                                title="Kembali ke Daftar Karyawan">
                                <i class="fa-solid fa-arrow-left text-sm"></i>
                            </a>
                        @else
                            <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-2xs">
                                <i class="fa-solid fa-hand-holding-dollar text-lg"></i>
                            </div>
                        @endif
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ $employee ? 'Kelola Pinjaman - ' . $employee->name : __('Kelola Pinjaman Karyawan') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Pantau data pinjaman karyawan, realisasi pembayaran cicilan, sisa saldo belum lunas, dan posting anggaran ke modul Budgeting.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-link href="{{ route('emp-loans.create', ['emp_id' => $employee ? $employee->id : null]) }}"
                            class="inline-flex items-center gap-2">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>{{ __('Buat Pinjaman Baru') }}</span>
                        </x-primary-link>
                    </div>
                </div>
            </div>

            <!-- Stat Cards Ringkasan Pinjaman & Sisa Saldo (ESS Golden Standard) -->
            @isset($stats)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Pinjaman -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pokok Pinjaman</p>
                            <h3 class="text-lg font-extrabold text-slate-900 mt-1 font-mono">
                                Rp {{ number_format($stats['total_amount'] ?? 0, 0, ',', '.') }}
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $stats['total_count'] ?? 0 }} permohonan pinjaman</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                            <i class="fa-solid fa-vault text-lg"></i>
                        </div>
                    </div>

                    <!-- Total Terbayar -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Total Sudah Dibayar</p>
                            <h3 class="text-lg font-extrabold text-emerald-700 mt-1 font-mono">
                                Rp {{ number_format($stats['paid_amount'] ?? 0, 0, ',', '.') }}
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi potongan payroll</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-check text-lg"></i>
                        </div>
                    </div>

                    <!-- Sisa Saldo Belum Lunas -->
                    <div class="bg-white p-4 rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50/50 to-white shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Sisa Saldo (Belum Lunas)</p>
                            <h3 class="text-lg font-extrabold text-amber-800 mt-1 font-mono">
                                Rp {{ number_format($stats['remaining_amount'] ?? 0, 0, ',', '.') }}
                            </h3>
                            <p class="text-[11px] text-amber-600/80 mt-0.5 font-medium">Masih harus dicicil</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                        </div>
                    </div>

                    <!-- Status Pinjaman -->
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Status Pinjaman</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    {{ $stats['open_count'] ?? 0 }} Berjalan
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    {{ $stats['closed_count'] ?? 0 }} Lunas
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Monitoring cicilan aktif</p>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-chart-pie text-lg"></i>
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

                    @if ($message = Session::get('warning'))
                        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2 mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm"></i>
                            <span class="font-medium">{{ $message }}</span>
                        </div>
                    @endif

                    @if (isset($errors) && $errors->any())
                        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs sm:text-sm mb-4" role="alert">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                                <span class="font-semibold">Terjadi Kesalahan:</span>
                            </div>
                            <ul class="list-disc list-inside text-xs text-rose-700 pl-4 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <x-data-table id="emp-loan-table" :headers="[
                            'No',
                            'Karyawan',
                            'Proyek',
                            'Pokok Pinjaman',
                            'Cicilan / Bln',
                            'Terbayar',
                            'Sisa Saldo (Belum Lunas)',
                            'Periode Pinjaman',
                            'Lampiran',
                            'Status',
                            'Budget Ref',
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
                $('#emp-loan-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('emp-loans.index', ['emp_id' => $employee ? $employee->id : null]) }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center w-10 text-xs' },
                        { data: 'employee_name', name: 'employee.name', className: 'text-xs' },
                        { data: 'project_name', name: 'project.name', className: 'text-xs' },
                        { data: 'formatted_amount', name: 'amount', className: 'text-right text-xs' },
                        { data: 'formatted_installment', name: 'installment_amount', className: 'text-right text-xs' },
                        { data: 'paid_amount', name: 'paid_amount', orderable: false, searchable: false, className: 'text-right text-xs' },
                        { data: 'remaining_balance', name: 'remaining_balance', orderable: false, searchable: false, className: 'text-xs' },
                        { data: 'period', name: 'start_date', className: 'text-xs' },
                        { data: 'attachment_badge', name: 'attachment_badge', orderable: false, searchable: false, className: 'text-center text-xs' },
                        { data: 'status', name: 'status', className: 'text-center text-xs' },
                        { data: 'budget_reference', name: 'budget_reference_number', className: 'text-center text-xs' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center text-xs w-28' },
                    ],
                    order: [[7, 'desc']],
                });
            });
        </script>
    @endpush
</x-app-layout>
