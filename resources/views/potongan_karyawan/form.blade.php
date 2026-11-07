<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('potongan-karyawan.index') }}"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition"
                            title="Kembali ke Daftar Potongan">
                            <i class="fa-solid fa-arrow-left text-sm"></i>
                        </a>
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ isset($header) ? 'Kelola Potongan Karyawan - ' . \Carbon\Carbon::createFromDate($header->year, $header->month, 1)->translatedFormat('F Y') : __('Buat Periode Potongan Baru') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Form penginputan potongan bulanan karyawan, perhitungan denda keterlambatan presensi, dan cicilan pinjaman.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="fa-solid fa-calendar-check text-blue-500"></i>
                            Periode: <strong id="header-period-badge">{{ isset($header) ? \Carbon\Carbon::createFromDate($header->year, $header->month, 1)->translatedFormat('F Y') : date('F Y') }}</strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card Kalkulasi Otomatis (Keterlambatan & Pinjaman) -->
            <div class="bg-gradient-to-br from-amber-50/80 via-white to-indigo-50/50 border border-amber-200/90 rounded-xl p-5 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <div class="space-y-2.5 max-w-2xl">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500 text-white shadow-xs">
                                <i class="fa-solid fa-calculator text-sm"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Perhitungan Otomatis Potongan (Keterlambatan &amp; Pinjaman)</h3>
                                <p class="text-xs text-slate-600">
                                    Hitung akumulasi denda keterlambatan presensi (&gt; 08:30 WIB) dan cicilan pinjaman karyawan aktif secara otomatis.
                                </p>
                            </div>
                        </div>

                        <!-- Info Pill Badges -->
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-amber-200 text-xs font-medium text-slate-700 shadow-2xs">
                                <i class="fa-regular fa-calendar text-amber-600"></i>
                                Cut-off Presensi: <strong id="cutoff-label">{{ $cutoffPeriod['label'] ?? 'Tgl 25 s/d 25' }}</strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-amber-200 text-xs font-medium text-slate-700 shadow-2xs">
                                <i class="fa-solid fa-coins text-amber-600"></i>
                                Tarif Denda: <strong id="rate-label">Rp {{ number_format($lateRate ?? 15000, 0, ',', '.') }} / keterlambatan</strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-amber-200 text-xs font-medium text-slate-700 shadow-2xs">
                                <i class="fa-solid fa-clock text-amber-600"></i>
                                Batas Masuk: <strong class="text-red-600">&gt; 08:30 WIB</strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-indigo-200 text-xs font-medium text-indigo-800 shadow-2xs">
                                <i class="fa-solid fa-hand-holding-dollar text-indigo-600"></i>
                                Cicilan Pinjaman: <strong>Otomatis dari EmpLoan</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Aksi Hitung -->
                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <button type="button" id="btn-calc-all"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-bolt text-sm"></i>
                            <span id="btn-calc-all-text">Hitung Potongan Otomatis</span>
                        </button>
                    </div>
                </div>

                <!-- Live Result Alert Box (Muncul setelah kalkulasi berhasil) -->
                <div id="calc-result-box" class="hidden mt-4 pt-4 border-t border-amber-200/80 animate-fade-in">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white/95 p-3.5 rounded-lg border border-amber-200 text-xs shadow-2xs">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-600">
                                <i class="fa-solid fa-check"></i>
                            </span>
                            <div>
                                <span class="font-bold text-slate-800 block" id="calc-result-headline">Kalkulasi Selesai</span>
                                <span class="text-slate-600" id="calc-result-summary">Nilai denda keterlambatan dan cicilan pinjaman telah otomatis diisikan ke kolom tabel.</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btn-open-detail-modal"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-md transition">
                                <i class="fa-solid fa-list-check text-slate-500"></i>
                                <span>Lihat Rincian Potongan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Utama Pengajuan/Penyimpanan Potongan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <form id="potongan-form" method="POST" action="{{ isset($header) ? route('potongan-karyawan.update', $header->id) : route('potongan-karyawan.store') }}">
                    @csrf
                    @isset($header) @method('PUT') @endisset

                    <div class="p-6 text-gray-900 space-y-6">
                        <!-- Periode Input (Tanggal Periode Potongan) -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <div class="max-w-md">
                                <x-input-label for="period_date" :value="__('Tanggal Periode')" />
                                <div class="relative mt-1">
                                    <x-text-input id="period_date" name="period_date" type="text" class="block w-full pr-10 cursor-pointer bg-white"
                                        value="{{ isset($header) ? \Carbon\Carbon::createFromDate($header->year, $header->month, 25)->format('Y-m-d') : date('Y-m-25') }}" required autofocus />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-regular fa-calendar"></i>
                                    </div>
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 font-semibold text-xs border border-blue-200">
                                        <i class="fa-solid fa-calendar-check text-[11px]"></i>
                                        Periode Gaji: <span id="active-month-label">{{ isset($header) ? \Carbon\Carbon::createFromDate($header->year, $header->month, 1)->translatedFormat('F Y') : date('F Y') }}</span>
                                    </span>
                                </div>
                                <!-- Hidden inputs for year, month, start_date & end_date -->
                                <input type="hidden" id="year" name="year" value="{{ $header->year ?? date('Y') }}">
                                <input type="hidden" id="month" name="month" value="{{ $header->month ?? date('n') }}">
                                <input type="hidden" id="start_date" name="start_date" value="{{ isset($header) && !empty($header->start_date) ? \Carbon\Carbon::parse($header->start_date)->format('Y-m-d') : ($cutoffPeriod['start_date_formatted'] ?? '') }}">
                                <input type="hidden" id="end_date" name="end_date" value="{{ isset($header) && !empty($header->end_date) ? \Carbon\Carbon::parse($header->end_date)->format('Y-m-d') : ($cutoffPeriod['end_date_formatted'] ?? '') }}">
                                <x-input-error class="mt-1" :messages="$errors->get('period_date')" />
                                <x-input-error class="mt-1" :messages="$errors->get('year')" />
                                <x-input-error class="mt-1" :messages="$errors->get('month')" />
                            </div>
                        </div>

                        <!-- Toolbar Pencarian & Filter Karyawan -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fa-solid fa-users text-indigo-600"></i>
                                    Daftar Potongan Karyawan
                                </span>
                                <span id="employee-count-badge" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    {{ count($employees) }} Karyawan
                                </span>
                            </div>

                            <div class="relative w-full sm:w-80">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </div>
                                <x-text-input
                                    type="text"
                                    id="search-employee"
                                    class="block w-full pl-9 pr-8 text-xs py-2 bg-white border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Cari nama karyawan atau jabatan..."
                                    autocomplete="off"
                                />
                                <button type="button" id="btn-clear-search" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Tabel Input Potongan Per Karyawan -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                                <table id="potongan-table" class="w-full text-sm text-left text-gray-500 divide-y divide-slate-200">
                                    <thead class="text-xs text-slate-700 uppercase bg-slate-100 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-4 py-3 min-w-[200px]">Karyawan</th>
                                            @foreach($components as $payrollComponent)
                                                @php
                                                    $isLateComponent = (stripos($payrollComponent->name, 'keterlambatan') !== false) || ($payrollComponent->id == 14);
                                                    $isLoanComponent = (stripos($payrollComponent->name, 'pinjaman') !== false) || ($payrollComponent->id == 13);
                                                @endphp
                                                <th class="px-4 py-3 min-w-[170px]
                                                    {{ $isLateComponent ? 'bg-amber-100/70 border-x border-amber-200 text-amber-900' : '' }}
                                                    {{ $isLoanComponent ? 'bg-indigo-100/70 border-x border-indigo-200 text-indigo-900' : '' }}">
                                                    <div class="flex items-center gap-1.5">
                                                        <span>{{ $payrollComponent->name }}</span>
                                                        @if($isLateComponent)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white" title="Dihitung otomatis via Presensi">
                                                                Otomatis (&gt; 08:30)
                                                            </span>
                                                        @elseif($isLoanComponent)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-600 text-white" title="Dihitung otomatis via EmpLoan">
                                                                Otomatis (Pinjaman)
                                                            </span>
                                                        @endif
                                                    </div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @foreach($employees as $employee)
                                            <tr class="hover:bg-slate-50 transition employee-row"
                                                data-emp-id="{{ $employee->id }}"
                                                data-emp-name="{{ strtolower($employee->name) }}"
                                                data-emp-jabatan="{{ strtolower($employee->jabatan->jabatan ?? '') }}">
                                                <td class="px-4 py-3">
                                                    <div class="font-semibold text-slate-900">{{ $employee->name }}</div>
                                                    <div class="text-xs text-slate-500">{{ $employee->jabatan->jabatan ?? '-' }}</div>
                                                </td>
                                                @foreach($components as $payrollComponent)
                                                    @php
                                                        $deduction = $existingDeductions->firstWhere(function($item) use ($employee, $payrollComponent) {
                                                            return $item['employee_id'] == $employee->id && $item['component_id'] == $payrollComponent->id;
                                                        });
                                                        $deductionValue = $deduction ? $deduction['value'] : '';
                                                        $isLateCol = (stripos($payrollComponent->name, 'keterlambatan') !== false) || ($payrollComponent->id == 14);
                                                        $isLoanCol = (stripos($payrollComponent->name, 'pinjaman') !== false) || ($payrollComponent->id == 13);
                                                    @endphp
                                                    <td class="px-4 py-2.5
                                                        {{ $isLateCol ? 'bg-amber-50/40 border-x border-amber-100' : '' }}
                                                        {{ $isLoanCol ? 'bg-indigo-50/30 border-x border-indigo-100' : '' }}">
                                                        <div class="relative">
                                                            <x-text-input
                                                                type="text"
                                                                id="deduction_{{ $employee->id }}_{{ $payrollComponent->id }}"
                                                                name="deductions[{{ $employee->id }}_{{ $payrollComponent->id }}]"
                                                                data-emp-id="{{ $employee->id }}"
                                                                data-comp-id="{{ $payrollComponent->id }}"
                                                                data-is-late="{{ $isLateCol ? '1' : '0' }}"
                                                                data-is-loan="{{ $isLoanCol ? '1' : '0' }}"
                                                                class="deduction-input block w-full currency-mask text-right text-sm py-1.5
                                                                    {{ $isLateCol ? 'font-medium text-slate-800 border-amber-300 focus:ring-amber-500 focus:border-amber-500' : '' }}
                                                                    {{ $isLoanCol ? 'font-medium text-slate-800 border-indigo-300 focus:ring-indigo-500 focus:border-indigo-500' : '' }}"
                                                                value="{{ $deductionValue }}"
                                                                placeholder="0.00"
                                                            />

                                                            {{-- Info Keterlambatan: Berapa kali karyawan terlambat --}}
                                                            @if($isLateCol)
                                                                @php
                                                                    $lateInfo = $initialLateData[$employee->id] ?? null;
                                                                    $lateCount = $lateInfo['late_count'] ?? 0;
                                                                @endphp
                                                                <div id="late-count-box-{{ $employee->id }}" class="mt-1 flex items-center justify-between text-[11px]">
                                                                    <span class="text-slate-400">Presensi:</span>
                                                                    <span class="late-count-badge font-bold {{ $lateCount > 0 ? 'text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded' : 'text-emerald-600' }}" data-emp-id="{{ $employee->id }}">
                                                                        @if($lateCount > 0)
                                                                            <i class="fa-solid fa-triangle-exclamation text-[10px] mr-0.5"></i> {{ $lateCount }}x Terlambat (> 08:30)
                                                                        @else
                                                                            <i class="fa-solid fa-check text-[10px] mr-0.5"></i> 0x (Tepat Waktu)
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            @endif

                                                            {{-- Info Cicilan Pinjaman: Sisa Saldo Karyawan --}}
                                                            @if($isLoanCol)
                                                                @php
                                                                    $loanRecord = isset($activeLoans) ? $activeLoans->get($employee->id, collect())->first() : \App\Models\EmpLoan::where('emp_id', $employee->id)->where('status', 'open')->first();
                                                                    $loanBal = $loanRecord ? $loanRecord->calculateBalanceForPeriod((int)($header->year ?? date('Y')), (int)($header->month ?? date('n'))) : null;
                                                                @endphp
                                                                @if($loanBal && $loanBal['saldo_before'] > 0)
                                                                    <div id="loan-saldo-box-{{ $employee->id }}" class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                                                                        <span class="text-slate-400">Saldo:</span>
                                                                        <span class="font-bold text-indigo-700 font-mono loan-saldo-text" data-emp-id="{{ $employee->id }}">
                                                                            Rp {{ number_format($loanBal['saldo_before'], 0, ',', '.') }}
                                                                        </span>
                                                                    </div>
                                                                @elseif($loanRecord)
                                                                    <div id="loan-saldo-box-{{ $employee->id }}" class="mt-1 text-right text-[10px] text-emerald-600 font-bold">
                                                                        <i class="fa-solid fa-check"></i> Lunas
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Tombol Simpan -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <div class="text-xs text-slate-500">
                            <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
                            Pastikan data potongan sudah sesuai sebelum menyimpan.
                        </div>

                        <div class="flex items-center gap-3">
                            <x-secondary-link href="{{ route('potongan-karyawan.index') }}">
                                {{ __('Batal') }}
                            </x-secondary-link>

                            <x-primary-button id="btn-submit-form" class="inline-flex items-center gap-2">
                                <i class="fa-solid fa-check text-xs"></i>
                                <span id="btn-submit-text">{{ isset($header) ? __('Simpan Potongan') : __('Buat Periode') }}</span>
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Detail Rincian Presensi & Pinjaman -->
    <div id="modal-detail" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div id="modal-backdrop" class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-xs"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 text-white">
                            <i class="fa-solid fa-list-check text-sm"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-slate-800 text-base" id="modal-title">Rincian Perhitungan Potongan Otomatis</h3>
                            <p class="text-xs text-slate-500" id="modal-period-text">Periode Cut-Off</p>
                        </div>
                    </div>
                    <button type="button" id="btn-close-modal" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Tab Navigation in Modal -->
                <div class="px-6 pt-3 bg-white border-b border-slate-200 flex items-center gap-4 text-xs font-semibold">
                    <button type="button" id="tab-late-btn" class="modal-tab-btn py-2 border-b-2 border-amber-500 text-amber-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Denda Keterlambatan</span>
                        <span id="badge-late-count" class="px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 text-[10px]">0</span>
                    </button>
                    <button type="button" id="tab-loan-btn" class="modal-tab-btn py-2 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        <span>Cicilan Pinjaman</span>
                        <span id="badge-loan-count" class="px-1.5 py-0.2 rounded-full bg-indigo-100 text-indigo-800 text-[10px]">0</span>
                    </button>
                </div>

                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <!-- Tab Keterlambatan -->
                    <div id="tab-late-content" class="space-y-4"></div>

                    <!-- Tab Pinjaman -->
                    <div id="tab-loan-content" class="hidden space-y-4"></div>
                </div>

                <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 text-right">
                    <button type="button" id="btn-close-modal-bottom" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-lg transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Inisialisasi Inputmask Rupiah Currency
                $('.currency-mask').inputmask({
                    alias: 'numeric',
                    groupSeparator: ",",
                    digits: 2,
                    digitsOptional: false,
                    placeholder: "0.00",
                    rightAlign: true,
                    autoGroup: true,
                    prefix: "",
                    allowMinus: false,
                    removeMaskOnSubmit: true
                });

                // Format existing values
                $('.currency-mask').each(function() {
                    const value = $(this).val();
                    if (value !== '' && !value.includes('.')) {
                        const numValue = parseFloat(value);
                        if (!isNaN(numValue)) {
                            $(this).val(numValue.toFixed(2));
                        }
                    }
                });

                // State Variable
                let lastCalculationData = null;
                const calcUrl = "{{ route('potongan-karyawan.calculate-late') }}";

                // Helper: Update label periode saat tanggal diubah
                function updatePeriodFromDate(dateVal) {
                    if (!dateVal) {
                        dateVal = $('#period_date').val();
                    }
                    if (dateVal) {
                        const parts = dateVal.split('-');
                        const year = parseInt(parts[0], 10);
                        const month = parseInt(parts[1], 10);

                        $('#year').val(year);
                        $('#month').val(month);

                        const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                        const shortMonthNames = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

                        const monthStr = `${monthNames[month - 1]} ${year}`;
                        $('#active-month-label').text(monthStr);
                        $('#header-period-badge').text(monthStr);

                        const prevM = month === 1 ? 12 : month - 1;
                        const prevY = month === 1 ? year - 1 : year;
                        const prevMonthPadded = String(prevM).padStart(2, '0');
                        const monthPadded = String(month).padStart(2, '0');
                        $('#start_date').val(`${prevY}-${prevMonthPadded}-25`);
                        $('#end_date').val(`${year}-${monthPadded}-25`);
                        $('#cutoff-label').text(`25 ${shortMonthNames[prevM - 1]} ${prevY} s/d 25 ${shortMonthNames[month - 1]} ${year}`);
                    }
                }

                if (typeof flatpickr !== 'undefined') {
                    flatpickr('#period_date', {
                        altInput: true,
                        altFormat: "d F Y",
                        dateFormat: "Y-m-d",
                        locale: "id",
                        onChange: function(selectedDates, dateStr) {
                            updatePeriodFromDate(dateStr);
                        }
                    });
                } else {
                    $('#period_date').on('change', function() {
                        updatePeriodFromDate($(this).val());
                    });
                }

                // Handler: Klik Tombol "Hitung Potongan Otomatis"
                $('#btn-calc-all').on('click', function () {
                    const periodDate = $('#period_date').val();
                    const year = $('#year').val();
                    const month = $('#month').val();

                    if (!periodDate) {
                        alert('Harap pilih Tanggal Periode terlebih dahulu.');
                        return;
                    }

                    const $btn = $(this);
                    const originalText = $('#btn-calc-all-text').text();
                    $btn.prop('disabled', true);
                    $('#btn-calc-all-text').html('<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menghitung Presensi &amp; Pinjaman...');

                    $.ajax({
                        url: calcUrl,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            period_date: periodDate,
                            year: year,
                            month: month
                        },
                        success: function (res) {
                            $btn.prop('disabled', false);
                            $('#btn-calc-all-text').text(originalText);

                            if (res.success) {
                                lastCalculationData = res;

                                // Tampilkan alert box ringkasan
                                $('#calc-result-box').removeClass('hidden');
                                $('#calc-result-headline').text(
                                    `Hasil Perhitungan: Keterlambatan (${res.summary.total_late_events}x) & Pinjaman (${res.summary.total_loan_employees} orang)`
                                );
                                $('#calc-result-summary').text(
                                    `Total Keterlambatan: Rp ${res.late.summary.formatted_total} | Total Pinjaman: Rp ${res.loans.summary.formatted_total} (Grand Total: Rp ${res.summary.formatted_grand_total}). Kolom tabel telah terisi otomatis.`
                                );

                                // 1. Isi input tabel untuk Keterlambatan
                                $.each(res.deductions, function (empId, data) {
                                    const inputSelector = `input[data-emp-id="${empId}"][data-comp-id="${res.component_id}"]`;
                                    const $input = $(inputSelector);
                                    if ($input.length) {
                                        $input.val(data.formatted_amount);
                                        $input.trigger('input');

                                        // Update info berapa kali karyawan terlambat
                                        const $lateBadge = $(`.late-count-badge[data-emp-id="${empId}"]`);
                                        if ($lateBadge.length) {
                                            if (data.late_count > 0) {
                                                $lateBadge.removeClass('text-emerald-600')
                                                    .addClass('text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded')
                                                    .html(`<i class="fa-solid fa-triangle-exclamation text-[10px] mr-0.5"></i> ${data.late_count}x Terlambat (> 08:30)`);
                                            } else {
                                                $lateBadge.removeClass('text-red-600 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded')
                                                    .addClass('text-emerald-600')
                                                    .html(`<i class="fa-solid fa-check text-[10px] mr-0.5"></i> 0x (Tepat Waktu)`);
                                            }
                                        }

                                        if (data.late_count > 0) {
                                            $input.addClass('bg-amber-100 font-bold text-amber-900 ring-2 ring-amber-400');
                                            setTimeout(() => {
                                                $input.removeClass('ring-2 ring-amber-400');
                                            }, 3000);
                                        }
                                    }
                                });

                                // 2. Isi input tabel untuk Cicilan Pinjaman & update teks saldo
                                $.each(res.loan_deductions, function (empId, data) {
                                    const inputSelector = `input[data-emp-id="${empId}"][data-comp-id="${res.loan_component_id}"]`;
                                    const $input = $(inputSelector);
                                    if ($input.length) {
                                        $input.val(data.formatted_amount);
                                        $input.trigger('input');

                                        // Update teks saldo live di bawah input
                                        const $saldoText = $(`.loan-saldo-text[data-emp-id="${empId}"]`);
                                        if ($saldoText.length) {
                                            if (data.saldo_before_total > 0) {
                                                const sisaClass = data.saldo_after_total <= 0 ? 'text-emerald-600 font-bold' : 'text-indigo-700 font-bold';
                                                $saldoText.html(`Rp ${data.formatted_saldo_before} <span class="text-slate-400 font-normal">&rarr; Sisa:</span> <span class="${sisaClass}">Rp ${data.formatted_saldo_after}</span>`);
                                            } else {
                                                $saldoText.html('<span class="text-emerald-600 font-bold"><i class="fa-solid fa-check"></i> Lunas</span>');
                                            }
                                        }

                                        if (data.installment_total > 0) {
                                            $input.addClass('bg-indigo-100 font-bold text-indigo-900 ring-2 ring-indigo-400');
                                            setTimeout(() => {
                                                $input.removeClass('ring-2 ring-indigo-400');
                                            }, 3000);
                                        }
                                    }
                                });

                                alert(`Perhitungan selesai!\n- Keterlambatan (> 08:30 WIB): ${res.summary.total_late_events}x (Rp ${res.late.summary.formatted_total})\n- Cicilan Pinjaman Aktif: ${res.summary.total_loan_employees} orang (Rp ${res.loans.summary.formatted_total})\n- Grand Total Potongan: Rp ${res.summary.formatted_grand_total}\n\nNilai potongan telah diisikan ke kolom formulir. Silakan klik tombol 'Simpan Potongan' untuk menyimpan.`);
                            }
                        },
                        error: function () {
                            $btn.prop('disabled', false);
                            $('#btn-calc-all-text').text(originalText);
                            alert('Terjadi kesalahan saat menghitung potongan otomatis.');
                        }
                    });
                });

                // Tab Switcher pada Modal Detail
                $('#tab-late-btn').on('click', function () {
                    $(this).addClass('border-amber-500 text-amber-700').removeClass('border-transparent text-slate-500');
                    $('#tab-loan-btn').removeClass('border-indigo-600 text-indigo-700').addClass('border-transparent text-slate-500');
                    $('#tab-late-content').removeClass('hidden');
                    $('#tab-loan-content').addClass('hidden');
                });

                $('#tab-loan-btn').on('click', function () {
                    $(this).addClass('border-indigo-600 text-indigo-700').removeClass('border-transparent text-slate-500');
                    $('#tab-late-btn').removeClass('border-amber-500 text-amber-700').addClass('border-transparent text-slate-500');
                    $('#tab-loan-content').removeClass('hidden');
                    $('#tab-late-content').addClass('hidden');
                });

                // Handler: Buka Modal Detail Potongan
                $('#btn-open-detail-modal').on('click', function () {
                    if (!lastCalculationData) return;

                    const res = lastCalculationData;
                    $('#modal-period-text').text(`Periode: ${res.period.label} | Tanggal Periode: ${$('#period_date').val()}`);
                    $('#badge-late-count').text(res.summary.total_late_events);
                    $('#badge-loan-count').text(res.summary.total_loan_employees);

                    // 1. Populate Rincian Keterlambatan
                    const $lateList = $('#tab-late-content');
                    $lateList.empty();
                    let hasLate = false;

                    $.each(res.deductions, function (empId, item) {
                        if (item.late_count > 0) {
                            hasLate = true;
                            let recordsHtml = '';
                            item.records.forEach((rec) => {
                                recordsHtml += `
                                    <tr class="border-b border-slate-100 text-xs">
                                        <td class="py-1.5 px-3 font-mono font-medium">${rec.date_label}</td>
                                        <td class="py-1.5 px-3 text-red-600 font-bold">${rec.check_in_time} WIB</td>
                                        <td class="py-1.5 px-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-100 text-red-700">Terlambat (&gt; ${rec.late_limit || '08:30'})</span>
                                        </td>
                                        <td class="py-1.5 px-3 text-right font-semibold text-slate-800">Rp ${res.late.rate_formatted}</td>
                                    </tr>
                                `;
                            });

                            $lateList.append(`
                                <div class="border border-slate-200 rounded-xl overflow-hidden">
                                    <div class="bg-amber-50/50 px-4 py-2.5 flex items-center justify-between border-b border-amber-200/80">
                                        <div>
                                            <span class="font-bold text-slate-800 text-sm">${item.name}</span>
                                            <span class="text-xs text-slate-500 ml-2">${item.jabatan}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">${item.late_count}x Terlambat</span>
                                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">Total: Rp ${parseFloat(item.deduction_amount).toLocaleString('id-ID')}</span>
                                        </div>
                                    </div>
                                    <table class="w-full">
                                        <thead class="bg-slate-100/60 text-[11px] uppercase text-slate-600">
                                            <tr>
                                                <th class="py-1.5 px-3 text-left">Tanggal</th>
                                                <th class="py-1.5 px-3 text-left">Jam Masuk</th>
                                                <th class="py-1.5 px-3 text-left">Status</th>
                                                <th class="py-1.5 px-3 text-right">Potongan Denda</th>
                                            </tr>
                                        </thead>
                                        <tbody>${recordsHtml}</tbody>
                                    </table>
                                </div>
                            `);
                        }
                    });

                    if (!hasLate) {
                        $lateList.html('<div class="p-4 text-center text-slate-500 text-sm">Tidak ada karyawan yang terlambat pada periode ini.</div>');
                    }

                    // 2. Populate Rincian Pinjaman
                    const $loanList = $('#tab-loan-content');
                    $loanList.empty();
                    let hasLoan = false;

                    $.each(res.loan_deductions, function (empId, item) {
                        if (item.installment_total > 0) {
                            hasLoan = true;
                            let loansHtml = '';
                            item.loans.forEach((l) => {
                                const lunasBadge = l.is_paid_off
                                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800"><i class="fa-solid fa-check mr-1"></i> Lunas</span>'
                                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-100 text-indigo-800">Berjalan</span>';

                                loansHtml += `
                                    <tr class="border-b border-slate-100 text-xs">
                                        <td class="py-2 px-3 font-semibold text-slate-800">Rp ${l.formatted_loan}</td>
                                        <td class="py-2 px-3 text-slate-500 font-mono">Rp ${l.formatted_paid_before}</td>
                                        <td class="py-2 px-3 font-bold text-indigo-700 font-mono">Rp ${l.formatted_saldo_before}</td>
                                        <td class="py-2 px-3 font-bold text-red-600 font-mono">Rp ${l.formatted_inst}</td>
                                        <td class="py-2 px-3 font-bold text-slate-800 font-mono">Rp ${l.formatted_saldo_after}</td>
                                        <td class="py-2 px-3 text-center">${lunasBadge}</td>
                                    </tr>
                                `;
                            });

                            $loanList.append(`
                                <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                                    <div class="bg-indigo-50/60 px-4 py-2.5 flex items-center justify-between border-b border-indigo-200/80">
                                        <div>
                                            <span class="font-bold text-slate-900 text-sm">${item.name}</span>
                                            <span class="text-xs text-slate-500 ml-2">${item.jabatan}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">Saldo Awal: Rp ${item.formatted_saldo_before}</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-600 text-white">Potongan: Rp ${parseFloat(item.installment_total).toLocaleString('id-ID')}</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${item.saldo_after_total <= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-800'}">Sisa Saldo: Rp ${item.formatted_saldo_after}</span>
                                        </div>
                                    </div>
                                    <table class="w-full">
                                        <thead class="bg-slate-100/80 text-[11px] uppercase text-slate-600">
                                            <tr>
                                                <th class="py-2 px-3 text-left">Pokok Pinjaman</th>
                                                <th class="py-2 px-3 text-left">Sudah Dibayar</th>
                                                <th class="py-2 px-3 text-left">Saldo Saat Ini</th>
                                                <th class="py-2 px-3 text-left">Potongan Bulan Ini</th>
                                                <th class="py-2 px-3 text-left">Sisa Saldo Akhir</th>
                                                <th class="py-2 px-3 text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>${loansHtml}</tbody>
                                    </table>
                                </div>
                            `);
                        }
                    });

                    if (!hasLoan) {
                        $loanList.html('<div class="p-4 text-center text-slate-500 text-sm">Tidak ada cicilan pinjaman aktif pada periode ini.</div>');
                    }

                    $('#modal-detail').removeClass('hidden');
                });

                // Tutup Modal
                $('#btn-close-modal, #btn-close-modal-bottom, #modal-backdrop').on('click', function () {
                    $('#modal-detail').addClass('hidden');
                });

                // Real-time Search / Filter Karyawan (Tanpa Mengganggu / Merusak Submit Form)
                // Cegah penekanan tombol Enter pada kolom pencarian agar tidak submit form tidak sengaja
                $('#search-employee').on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        return false;
                    }
                });

                $('#search-employee').on('input', function () {
                    const query = $(this).val().toLowerCase().trim();
                    let visibleCount = 0;
                    const totalCount = $('.employee-row').length;

                    if (query.length > 0) {
                        $('#btn-clear-search').removeClass('hidden');
                    } else {
                        $('#btn-clear-search').addClass('hidden');
                    }

                    $('.employee-row').each(function () {
                        const name = String($(this).data('emp-name') || '');
                        const jabatan = String($(this).data('emp-jabatan') || '');

                        if (name.includes(query) || jabatan.includes(query)) {
                            $(this).show();
                            visibleCount++;
                        } else {
                            $(this).hide();
                        }
                    });

                    if (query.length > 0) {
                        $('#employee-count-badge').text(`${visibleCount} dari ${totalCount} Karyawan`);
                    } else {
                        $('#employee-count-badge').text(`${totalCount} Karyawan`);
                    }

                    // Tampilkan baris "Tidak ditemukan" jika tidak ada yang cocok
                    if (visibleCount === 0) {
                        if ($('#no-employee-match-row').length === 0) {
                            const colSpan = $('#potongan-table thead tr th').length;
                            $('#potongan-table tbody').append(`
                                <tr id="no-employee-match-row">
                                    <td colspan="${colSpan}" class="py-10 text-center text-slate-400 text-xs">
                                        <i class="fa-solid fa-user-slash text-2xl mb-1 text-slate-300 block"></i>
                                        Tidak ada karyawan yang cocok dengan kata kunci pencarian.
                                    </td>
                                </tr>
                            `);
                        }
                    } else {
                        $('#no-employee-match-row').remove();
                    }
                });

                $('#btn-clear-search').on('click', function () {
                    $('#search-employee').val('').trigger('input').focus();
                });

                // Proteksi Anti-Double-Submit pada Form Utama
                let formSubmitting = false;
                $('#potongan-form').on('submit', function (e) {
                    if (formSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    formSubmitting = true;
                    $('#btn-submit-form').prop('disabled', true);
                    $('#btn-submit-text').html('<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menyimpan Potongan...');
                });
            });
        </script>
    @endpush
</x-app-layout>
