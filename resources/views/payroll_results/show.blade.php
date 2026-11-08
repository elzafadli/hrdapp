<x-app-layout>
    <div class="py-6" x-data="{ 
        activeTab: '{{ $activeTab ?? 'detail' }}',
        showReprocessModal: false 
    }" x-init="$watch('activeTab', () => $nextTick(() => alignFreezeColumns()))">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi & Info Periode -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Sisi Kiri: Tombol Kembali & Info Periode (Teks Jelas, Bukan Bentuk Tombol) -->
                    <div class="flex items-center gap-3">
                        <a href="{{ route('payroll-results.index') }}"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-200 transition shadow-2xs shrink-0"
                            title="Kembali ke Daftar Periode">
                            <i class="fa-solid fa-arrow-left text-xs"></i>
                            <span>Kembali</span>
                        </a>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h2 class="font-bold text-xl text-slate-900 leading-tight">
                                    Detail Payroll: <span class="text-indigo-600">{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</span>
                                </h2>
                                @if(($periodStatus ?? 'final') === 'final')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-check text-[10px]"></i> Final (ESS Rilis)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-clock text-[10px]"></i> Draft
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Tahun {{ $year }} • Periode {{ $month }} &nbsp;|&nbsp; 
                                Rentang Cut-Off: <strong>{{ $cutoffPeriod['label'] ?? ($cutoffPeriod['start_date']->translatedFormat('d M Y') . ' s/d ' . $cutoffPeriod['end_date']->translatedFormat('d M Y')) }}</strong> &nbsp;|&nbsp; 
                                <strong>{{ $totalEmployees }} Karyawan</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Kelompok Tombol Aksi yang Jelas -->
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <!-- Tombol Hitung Ulang (Membuka Modal Popup) -->
                        <button type="button" @click="showReprocessModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg border border-slate-300 shadow-2xs transition cursor-pointer">
                            <i class="fa-solid fa-rotate-right text-xs text-slate-500"></i>
                            <span>Hitung Ulang</span>
                        </button>

                        <!-- Tombol Finalisasi / Set Draft -->
                        <form action="{{ route('payroll-results.update-status') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            @if(($periodStatus ?? 'draft') === 'final')
                                <input type="hidden" name="status" value="draft">
                                <button type="submit" onclick="return confirm('Kembalikan status payroll periode {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }} ke Draft?\n\n- Slip gaji akan disembunyikan dari modul ESS karyawan.\n- Pemotongan saldo pinjaman periode ini akan dikembalikan ke saldo pinjaman karyawan.');"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold rounded-lg border border-amber-300 shadow-2xs transition cursor-pointer"
                                    title="Kembalikan status ke Draft">
                                    <i class="fa-solid fa-rotate-left text-xs text-amber-600"></i>
                                    <span>Kembalikan ke Draft</span>
                                </button>
                            @else
                                <input type="hidden" name="status" value="final">
                                <button type="submit" onclick="return confirm('Finalisasi payroll periode {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}?\n\n- Status akan diubah ke FINAL (dapat diakses karyawan di modul ESS).\n- Saldo pinjaman karyawan akan dipotong otomatis.');"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow-sm transition cursor-pointer"
                                    title="Finalisasi payroll dan potong saldo pinjaman karyawan">
                                    <i class="fa-solid fa-circle-check text-xs"></i>
                                    <span>Finalisasi Payroll &amp; Update Saldo</span>
                                </button>
                            @endif
                        </form>

                        <!-- Tombol Export Excel -->
                        <a href="{{ route('payroll-results.export', ['month' => $month, 'year' => $year]) }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Export Excel</span>
                        </a>
                    </div>
                </div>

                <!-- 4 KPI Summary Cards -->
                <div class="px-6 py-4 bg-slate-50/60 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Karyawan</p>
                            <p class="text-lg font-bold text-slate-800 mt-0.5">{{ $totalEmployees }} <span class="text-xs font-normal text-slate-500">Orang</span></p>
                        </div>
                    </div>

                    <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Penghasilan (Gross)</p>
                            <p class="text-lg font-bold text-emerald-700 mt-0.5">Rp {{ number_format($totalGross, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Potongan</p>
                            <p class="text-lg font-bold text-rose-700 mt-0.5">Rp {{ number_format($totalDeductions, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-2xs flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Take Home Pay</p>
                            <p class="text-lg font-bold text-indigo-700 mt-0.5">Rp {{ number_format($totalNet, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banner Peringatan Status Draft -->
            @if(($periodStatus ?? 'draft') === 'draft')
                <div class="bg-amber-50 border border-amber-200 text-amber-900 px-5 py-3.5 rounded-xl text-xs sm:text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-100 text-amber-700 text-sm shrink-0">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                        <div>
                            <div class="font-bold text-amber-900">Periode Masih Berstatus Draft</div>
                            <div class="text-xs text-amber-700 mt-0.5">
                                Slip gaji disembunyikan dari modul ESS dan saldo pinjaman karyawan <strong>belum dipotong</strong>. Klik tombol Finalisasi saat data sudah final.
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('payroll-results.update-status') }}" method="POST" class="shrink-0">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="status" value="final">
                        <button type="submit" onclick="return confirm('Finalisasi payroll periode {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}?\n\n- Status akan diubah ke FINAL (dapat diakses karyawan di modul ESS).\n- Saldo pinjaman karyawan akan dipotong otomatis.');"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow-xs transition cursor-pointer">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                            <span>Finalisasi &amp; Potong Saldo Sekarang</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- Flash Session Alerts -->
            @if ($message = Session::get('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2.5 shadow-2xs" role="alert">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
                    <span>{{ $message }}</span>
                </div>
            @endif

            @if ($warning = Session::get('warning'))
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2.5 shadow-2xs" role="alert">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base shrink-0"></i>
                    <span>{{ $warning }}</span>
                </div>
            @endif

            @if ($error = Session::get('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs sm:text-sm flex items-center gap-2.5 shadow-2xs" role="alert">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base shrink-0"></i>
                    <span>{{ $error }}</span>
                </div>
            @endif

            <!-- Modal Hitung Ulang Payroll (Tidak Memakan Ruang Halaman) -->
            <div x-show="showReprocessModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div x-show="showReprocessModal"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    @click="showReprocessModal = false"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showReprocessModal"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl">

                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2.5">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 text-sm">
                                    <i class="fa-solid fa-calculator"></i>
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-base" id="modal-title">Hitung Ulang Payroll Periode</h3>
                                    <p class="text-xs text-slate-500">Periode {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</p>
                                </div>
                            </div>
                            <button type="button" @click="showReprocessModal = false" class="text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        <form id="payroll-process-form" action="{{ route('payroll-results.process') }}" method="POST">
                            @csrf
                            <div class="p-6 space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="process_month" :value="__('Bulan Penggajian')" class="font-semibold text-slate-700 text-xs mb-1" />
                                        <x-select-input name="month" id="process_month" :noSelect2="true" required class="w-full">
                                            @foreach(range(1, 12) as $m)
                                                <option value="{{ $m }}" {{ (int) $m === (int) $month ? 'selected' : '' }}>
                                                    {{ \DateTime::createFromFormat('!m', $m)->format('F') }} (Periode {{ $m }})
                                                </option>
                                            @endforeach
                                        </x-select-input>
                                    </div>

                                    <div>
                                        <x-input-label for="process_year" :value="__('Tahun Penggajian')" class="font-semibold text-slate-700 text-xs mb-1" />
                                        <x-text-input type="number" name="year" id="process_year" value="{{ $year }}" required min="2020" max="2099" class="w-full" />
                                    </div>
                                </div>

                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-start gap-3">
                                    <div class="flex items-center h-5 mt-0.5">
                                        <input type="checkbox" name="is_thr" id="is_thr" value="1"
                                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded cursor-pointer"
                                            onchange="updateThrMessage()">
                                    </div>
                                    <div class="text-xs">
                                        <label for="is_thr" class="font-bold text-slate-800 cursor-pointer">
                                            Proses THR (Tunjangan Hari Raya)
                                        </label>
                                        <p class="text-slate-500 mt-0.5">
                                            Centang opsi ini jika hanya ingin memproses THR pada periode ini.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                                <button type="button" @click="showReprocessModal = false"
                                    class="px-4 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs sm:text-sm font-semibold rounded-lg transition">
                                    Batal
                                </button>
                                <button type="submit" id="submitPayrollBtn"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition">
                                    <i class="fa-solid fa-calculator text-xs"></i>
                                    <span id="submitPayrollBtnText">Jalankan Hitung Ulang</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tab Sub-Navigasi (Rincian Payroll vs Payroll BPJS) -->
            <div class="bg-white rounded-xl border border-slate-200 p-2 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="activeTab = 'detail'"
                        :class="activeTab === 'detail' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                        <i class="fa-solid fa-table-list text-sm"></i>
                        <span>Rincian Payroll &amp; Slip Gaji</span>
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                            :class="activeTab === 'detail' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                            {{ $totalEmployees }} Karyawan
                        </span>
                    </button>

                    <button type="button" @click="activeTab = 'bpjs'"
                        :class="activeTab === 'bpjs' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                        <i class="fa-solid fa-shield-halved text-sm" :class="activeTab === 'bpjs' ? 'text-amber-300' : 'text-amber-500'"></i>
                        <span>Payroll BPJS</span>
                    </button>
                </div>

                <div class="flex items-center gap-2 px-1">
                    <template x-if="activeTab === 'detail'">
                        <a href="{{ route('payroll-results.export', ['month' => $month, 'year' => $year]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Download Excel</span>
                        </a>
                    </template>

                    <template x-if="activeTab === 'bpjs'">
                        <a href="{{ route('payroll-results.export-deductions', ['month' => $month, 'year' => $year]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Download BPJS Excel</span>
                        </a>
                    </template>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- TAB CONTENT 1: RINCIAN PAYROLL & SLIP GAJI -->
            <!-- ========================================================================================= -->
            <div x-show="activeTab === 'detail'" class="space-y-6">
                @php
                    $allowanceColumns = $payrollColumns->whereIn('type', ['allowance', 'thr']);
                    $deductionColumns = $payrollColumns->whereIn('type', ['deduction', 'bpjs']);
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 text-sm">
                                <i class="fa-solid fa-table-list"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">
                                    Tabel Rincian Payroll Karyawan
                                </h3>
                                <p class="text-xs text-slate-500">Rincian seluruh komponen penerimaan, potongan, THP, serta link slip gaji per karyawan</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <div class="relative w-56 sm:w-64">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </div>
                                <input type="text" id="payroll-emp-search" placeholder="Cari nama karyawan..."
                                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:bg-white transition" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        @if ($groupedResults->isNotEmpty())
                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto border border-slate-200 rounded-lg">
                                <table id="payroll-detail-table" class="min-w-full text-xs border-separate border-spacing-0">
                                    <thead class="bg-slate-50 sticky top-0 z-30 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                                        <tr>
                                            <!-- 3 Frozen Columns (Excel-style) -->
                                            <th scope="col" style="position: sticky; left: 0; z-index: 35;" class="col-freeze-0 px-3.5 py-3 text-center bg-slate-50 border-b border-slate-200">
                                                Slip Gaji
                                            </th>
                                            <th scope="col" style="position: sticky; left: 70px; z-index: 35;" class="col-freeze-1 px-4 py-3 text-left bg-slate-50 border-b border-slate-200">
                                                Employee
                                            </th>
                                            <th scope="col" style="position: sticky; z-index: 35;" class="col-freeze-2 px-4 py-3 text-left bg-slate-50 border-b border-slate-200 border-r-2 border-slate-300 shadow-[3px_0_6px_-1px_rgba(0,0,0,0.12)]">
                                                Project
                                            </th>

                                            <!-- Scrollable Component Columns -->
                                            @foreach($allowanceColumns as $column)
                                                <th scope="col" class="px-4 py-3 text-right border-b border-slate-200 whitespace-nowrap bg-slate-50">
                                                    {{ $column->name }}
                                                </th>
                                            @endforeach
                                            <th scope="col" class="px-4 py-3 text-right font-bold text-emerald-700 bg-emerald-50 border-b border-slate-200 border-x border-emerald-100 whitespace-nowrap">
                                                Total Gaji
                                            </th>
                                            @foreach($deductionColumns as $column)
                                                <th scope="col" class="px-4 py-3 text-right border-b border-slate-200 whitespace-nowrap bg-slate-50">
                                                    {{ $column->name }}
                                                </th>
                                            @endforeach
                                            <th scope="col" class="px-4 py-3 text-right font-bold text-rose-700 bg-rose-50 border-b border-slate-200 border-x border-rose-100 whitespace-nowrap">
                                                Total Potongan
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-right font-bold text-indigo-700 bg-indigo-50 border-b border-slate-200 whitespace-nowrap">
                                                Take Home Pay
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white text-slate-700">
                                        @foreach ($groupedResults as $empId => $empResults)
                                            @php
                                                $employee = $empResults->first()->employee;
                                                $totalGaji = 0;
                                                $totalPotongan = 0;
                                            @endphp
                                            <tr class="payroll-row group hover:bg-slate-50/80 transition" data-emp-name="{{ strtolower($employee->name ?? '') }}">
                                                <!-- Frozen Column 1: Slip -->
                                                <td style="position: sticky; left: 0; z-index: 20;" class="col-freeze-0 px-3.5 py-3 whitespace-nowrap text-center bg-white group-hover:bg-slate-50 border-b border-slate-100">
                                                    <a href="{{ route('payroll-results.slip', ['emp_id' => $employee->id, 'month' => $month, 'year' => $year]) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 active:bg-indigo-200 text-indigo-700 border border-indigo-200 text-xs font-semibold rounded-md shadow-2xs transition">
                                                        <i class="fa-solid fa-receipt text-[11px]"></i>
                                                        <span>Slip</span>
                                                    </a>
                                                </td>

                                                <!-- Frozen Column 2: Employee (Moved before Project!) -->
                                                <td style="position: sticky; left: 70px; z-index: 20;" class="col-freeze-1 px-4 py-3 whitespace-nowrap bg-white group-hover:bg-slate-50 border-b border-slate-100">
                                                    <div class="font-bold text-slate-900 whitespace-nowrap">{{ $employee->name ?? 'N/A' }}</div>
                                                    <div class="text-[11px] text-slate-500 whitespace-nowrap">{{ $employee->jabatan->jabatan ?? ($employee->nik ?? '-') }}</div>
                                                </td>

                                                <!-- Frozen Column 3: Project (with Company) -->
                                                <td style="position: sticky; z-index: 20;" class="col-freeze-2 px-4 py-3 whitespace-nowrap bg-white group-hover:bg-slate-50 border-b border-slate-100 border-r-2 border-slate-300 shadow-[3px_0_6px_-1px_rgba(0,0,0,0.12)]">
                                                    <div class="font-bold text-slate-800 whitespace-nowrap">{{ $employee->project->name ?? 'N/A' }}</div>
                                                    <div class="text-[11px] text-slate-500 font-mono whitespace-nowrap">{{ $employee->project->company->code ?? '' }}</div>
                                                </td>

                                                <!-- Scrollable Columns -->
                                                @foreach($allowanceColumns as $column)
                                                    @php
                                                        $result = $empResults->firstWhere('payroll_component_id', $column->id);
                                                        $amount = $result ? $result->amount : 0;
                                                        $totalGaji += $amount;
                                                    @endphp
                                                    <td class="px-4 py-3 whitespace-nowrap text-right font-mono border-b border-slate-100">
                                                        {{ $result && $result->amount > 0 ? number_format($result->amount, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-emerald-700 bg-emerald-50/60 font-mono border-b border-slate-100 border-x border-emerald-100">
                                                    {{ number_format($totalGaji, 2, ',', '.') }}
                                                </td>
                                                @foreach($deductionColumns as $column)
                                                    @php
                                                        $result = $empResults->firstWhere('payroll_component_id', $column->id);
                                                        $amount = $result ? $result->amount : 0;
                                                        $totalPotongan += $amount;
                                                    @endphp
                                                    <td class="px-4 py-3 whitespace-nowrap text-right font-mono border-b border-slate-100">
                                                        {{ $result && $result->amount > 0 ? number_format($result->amount, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-rose-700 bg-rose-50/60 font-mono border-b border-slate-100 border-x border-rose-100">
                                                    {{ number_format($totalPotongan, 2, ',', '.') }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-indigo-700 bg-indigo-50/60 font-mono border-b border-slate-100">
                                                    {{ number_format($totalGaji - $totalPotongan, 2, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-slate-100 font-semibold border-t-2 border-slate-300">
                                        @php
                                            $allowanceTotals = [];
                                            $deductionTotals = [];
                                            $grandTotalGaji = 0;
                                            $grandTotalPotongan = 0;

                                            foreach($allowanceColumns as $column) {
                                                $allowanceTotals[$column->id] = 0;
                                            }
                                            foreach($deductionColumns as $column) {
                                                $deductionTotals[$column->id] = 0;
                                            }

                                            foreach($groupedResults as $empResults) {
                                                foreach($empResults as $result) {
                                                    if(isset($allowanceTotals[$result->payroll_component_id])) {
                                                        $allowanceTotals[$result->payroll_component_id] += $result->amount;
                                                        $grandTotalGaji += $result->amount;
                                                    }
                                                    if(isset($deductionTotals[$result->payroll_component_id])) {
                                                        $deductionTotals[$result->payroll_component_id] += $result->amount;
                                                        $grandTotalPotongan += $result->amount;
                                                    }
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <!-- Frozen Footer Header (Colspan 3) -->
                                            <td colspan="3" style="position: sticky; left: 0; z-index: 25;" class="px-4 py-3.5 whitespace-nowrap text-slate-800 font-bold uppercase text-[11px] bg-slate-100 border-r-2 border-slate-300 shadow-[3px_0_6px_-1px_rgba(0,0,0,0.12)]">
                                                Grand Total
                                            </td>
                                            @foreach($allowanceColumns as $column)
                                                <td class="px-4 py-3.5 whitespace-nowrap text-right text-slate-900 font-mono">
                                                    {{ number_format($allowanceTotals[$column->id], 2, ',', '.') }}
                                                </td>
                                            @endforeach
                                            <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-emerald-800 bg-emerald-100 border-x border-emerald-200 font-mono">
                                                {{ number_format($grandTotalGaji, 2, ',', '.') }}
                                            </td>
                                            @foreach($deductionColumns as $column)
                                                <td class="px-4 py-3.5 whitespace-nowrap text-right text-slate-900 font-mono">
                                                    {{ number_format($deductionTotals[$column->id], 2, ',', '.') }}
                                                </td>
                                            @endforeach
                                            <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-rose-800 bg-rose-100 border-x border-rose-200 font-mono">
                                                {{ number_format($grandTotalPotongan, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-indigo-900 bg-indigo-100 font-mono">
                                                {{ number_format($grandTotalGaji - $grandTotalPotongan, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                        <i class="fa-solid fa-receipt"></i>
                                    </span>
                                    <h4 class="font-bold text-slate-700 text-sm mt-1">Belum Ada Data Hasil Payroll</h4>
                                    <p class="text-xs text-slate-500 max-w-md">
                                        Hasil payroll untuk periode <strong>{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</strong> belum pernah diproses.
                                        Silakan gunakan formulir hitung ulang di atas.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- TAB CONTENT 2: PAYROLL BPJS -->
            <!-- ========================================================================================= -->
            <div x-show="activeTab === 'bpjs'" class="space-y-6">
                @php
                    $subsidiColumns = $payrollColumns->where('type', 'subsidi');
                    $bpjsColumns = $payrollColumns->where('type', 'bpjs');
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 text-amber-600 text-sm">
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm sm:text-base leading-tight">
                                    Tabel Rincian Subsidi &amp; Potongan BPJS
                                </h3>
                                <p class="text-xs text-slate-500">Iuran BPJS Ketenagakerjaan (JKK, JKM, JHT, JP) &amp; BPJS Kesehatan</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <div class="relative w-56 sm:w-64">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </div>
                                <input type="text" id="bpjs-emp-search" placeholder="Cari nama karyawan..."
                                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:bg-white transition" />
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        @if ($groupedResults->isNotEmpty() && ($subsidiColumns->isNotEmpty() || $bpjsColumns->isNotEmpty()))
                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto border border-slate-200 rounded-lg">
                                <table id="bpjs-detail-table" class="min-w-full text-xs border-separate border-spacing-0">
                                    <thead class="bg-slate-50 sticky top-0 z-30 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                                        <tr>
                                            <!-- 3 Frozen Columns (Excel-style) -->
                                            <th rowspan="2" scope="col" style="position: sticky; left: 0; z-index: 35;" class="col-freeze-0 px-3.5 py-3 text-center bg-slate-50 border-b border-slate-200">
                                                Slip
                                            </th>
                                            <th rowspan="2" scope="col" style="position: sticky; left: 70px; z-index: 35;" class="col-freeze-1 px-4 py-3 text-left bg-slate-50 border-b border-slate-200">
                                                Employee
                                            </th>
                                            <th rowspan="2" scope="col" style="position: sticky; z-index: 35;" class="col-freeze-2 px-4 py-3 text-left bg-slate-50 border-b border-slate-200 border-r-2 border-slate-300 shadow-[3px_0_6px_-1px_rgba(0,0,0,0.12)]">
                                                Project
                                            </th>

                                            @if($subsidiColumns->isNotEmpty())
                                                <th colspan="{{ $subsidiColumns->count() + 1 }}" class="px-3 py-2 text-center font-bold text-amber-900 bg-amber-100 border-x border-b border-amber-200 whitespace-nowrap">
                                                    <i class="fa-solid fa-building text-xs mr-1 text-amber-700"></i> Dibayar Perusahaan (Subsidi BPJS)
                                                </th>
                                            @endif

                                            @if($bpjsColumns->isNotEmpty())
                                                <th colspan="{{ $bpjsColumns->count() + 1 }}" class="px-3 py-2 text-center font-bold text-rose-900 bg-rose-100 border-x border-b border-rose-200 whitespace-nowrap">
                                                    <i class="fa-solid fa-user-tag text-xs mr-1 text-rose-700"></i> Dibayar Karyawan (Potongan BPJS)
                                                </th>
                                            @endif
                                        </tr>

                                        <tr class="bg-slate-100 text-[10px]">
                                            @foreach($subsidiColumns as $col)
                                                <th class="px-3 py-2 text-right text-amber-800 bg-amber-50 font-semibold border-b border-slate-200 whitespace-nowrap">
                                                    {{ $col->name }}
                                                </th>
                                            @endforeach
                                            @if($subsidiColumns->isNotEmpty())
                                                <th class="px-3 py-2 text-right font-bold text-amber-900 bg-amber-100 border-b border-amber-200 whitespace-nowrap">
                                                    Total Subsidi
                                                </th>
                                            @endif

                                            @foreach($bpjsColumns as $col)
                                                <th class="px-3 py-2 text-right text-rose-800 bg-rose-50 font-semibold border-b border-slate-200 whitespace-nowrap">
                                                    {{ $col->name }}
                                                </th>
                                            @endforeach
                                            @if($bpjsColumns->isNotEmpty())
                                                <th class="px-3 py-2 text-right font-bold text-rose-900 bg-rose-100 border-b border-rose-200 whitespace-nowrap">
                                                    Total Potongan
                                                </th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white text-slate-700">
                                        @foreach ($groupedResults as $empId => $empResults)
                                            @php
                                                $employee = $empResults->first()->employee;
                                                $totalSubsidi = 0;
                                                $totalBpjsPotongan = 0;
                                            @endphp
                                            <tr class="bpjs-row group hover:bg-slate-50/80 transition" data-emp-name="{{ strtolower($employee->name ?? '') }}">
                                                <!-- Frozen Column 1: Slip -->
                                                <td style="position: sticky; left: 0; z-index: 20;" class="col-freeze-0 px-3.5 py-3 whitespace-nowrap text-center bg-white group-hover:bg-slate-50 border-b border-slate-100">
                                                    <a href="{{ route('payroll-results.slip', ['emp_id' => $employee->id, 'month' => $month, 'year' => $year]) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-semibold rounded shadow-2xs transition">
                                                        <i class="fa-solid fa-receipt text-[10px]"></i>
                                                        <span>Slip</span>
                                                    </a>
                                                </td>

                                                <!-- Frozen Column 2: Employee (Moved before Project!) -->
                                                <td style="position: sticky; left: 70px; z-index: 20;" class="col-freeze-1 px-4 py-3 whitespace-nowrap bg-white group-hover:bg-slate-50 border-b border-slate-100">
                                                    <div class="font-bold text-slate-900 whitespace-nowrap">{{ $employee->name ?? 'N/A' }}</div>
                                                    <div class="text-[11px] text-slate-500 whitespace-nowrap">{{ $employee->jabatan->jabatan ?? ($employee->nik ?? '-') }}</div>
                                                </td>

                                                <!-- Frozen Column 3: Project (with Company) -->
                                                <td style="position: sticky; z-index: 20;" class="col-freeze-2 px-4 py-3 whitespace-nowrap bg-white group-hover:bg-slate-50 border-b border-slate-100 border-r-2 border-slate-300 shadow-[3px_0_6px_-1px_rgba(0,0,0,0.12)]">
                                                    <div class="font-bold text-slate-800 whitespace-nowrap">{{ $employee->project->name ?? 'N/A' }}</div>
                                                    <div class="text-[11px] text-slate-500 font-mono whitespace-nowrap">{{ $employee->project->company->code ?? '' }}</div>
                                                </td>

                                                {{-- Komponen Subsidi Perusahaan --}}
                                                @foreach($subsidiColumns as $col)
                                                    @php
                                                        $result = $empResults->firstWhere('payroll_component_id', $col->id);
                                                        $amt = $result ? $result->amount : 0;
                                                        $totalSubsidi += $amt;
                                                    @endphp
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-mono bg-amber-50/30 border-b border-slate-100">
                                                        {{ $amt > 0 ? number_format($amt, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                @if($subsidiColumns->isNotEmpty())
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-bold text-amber-900 bg-amber-100/70 border-r border-b border-amber-200 font-mono">
                                                        {{ number_format($totalSubsidi, 2, ',', '.') }}
                                                    </td>
                                                @endif

                                                {{-- Komponen Potongan Karyawan --}}
                                                @foreach($bpjsColumns as $col)
                                                    @php
                                                        $result = $empResults->firstWhere('payroll_component_id', $col->id);
                                                        $amt = $result ? $result->amount : 0;
                                                        $totalBpjsPotongan += $amt;
                                                    @endphp
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-mono bg-rose-50/30 border-b border-slate-100">
                                                        {{ $amt > 0 ? number_format($amt, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                @if($bpjsColumns->isNotEmpty())
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-bold text-rose-900 bg-rose-100/70 border-b border-slate-100 font-mono">
                                                        {{ number_format($totalBpjsPotongan, 2, ',', '.') }}
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-slate-100 font-semibold border-t-2 border-slate-300">
                                        @php
                                            $subsidiTotals = [];
                                            $bpjsTotals = [];
                                            $grandTotalSubsidi = 0;
                                            $grandTotalBpjsPotongan = 0;

                                            foreach($subsidiColumns as $col) {
                                                $subsidiTotals[$col->id] = 0;
                                            }
                                            foreach($bpjsColumns as $col) {
                                                $bpjsTotals[$col->id] = 0;
                                            }

                                            foreach($groupedResults as $empResults) {
                                                foreach($empResults as $result) {
                                                    if(isset($subsidiTotals[$result->payroll_component_id])) {
                                                        $subsidiTotals[$result->payroll_component_id] += $result->amount;
                                                        $grandTotalSubsidi += $result->amount;
                                                    }
                                                    if(isset($bpjsTotals[$result->payroll_component_id])) {
                                                        $bpjsTotals[$result->payroll_component_id] += $result->amount;
                                                        $grandTotalBpjsPotongan += $result->amount;
                                                    }
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <!-- Frozen Footer Header (Colspan 3) -->
                                            <td colspan="3" style="position: sticky; left: 0; z-index: 25;" class="px-4 py-3.5 whitespace-nowrap text-slate-800 font-bold uppercase text-[11px] bg-slate-100 border-r-2 border-slate-300 shadow-[3px_0_6px_-1px_rgba(0,0,0,0.12)]">
                                                Grand Total BPJS
                                            </td>
                                            @foreach($subsidiColumns as $col)
                                                <td class="px-4 py-3.5 whitespace-nowrap text-right text-slate-900 font-mono bg-amber-50/60">
                                                    {{ number_format($subsidiTotals[$col->id], 2, ',', '.') }}
                                                </td>
                                            @endforeach
                                            @if($subsidiColumns->isNotEmpty())
                                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-amber-900 bg-amber-100 border-r border-amber-200 font-mono">
                                                    {{ number_format($grandTotalSubsidi, 2, ',', '.') }}
                                                </td>
                                            @endif

                                            @foreach($bpjsColumns as $col)
                                                <td class="px-4 py-3.5 whitespace-nowrap text-right text-slate-900 font-mono bg-rose-50/60">
                                                    {{ number_format($bpjsTotals[$col->id], 2, ',', '.') }}
                                                </td>
                                            @endforeach
                                            @if($bpjsColumns->isNotEmpty())
                                                <td class="px-4 py-3.5 whitespace-nowrap text-right font-bold text-rose-900 bg-rose-100 font-mono">
                                                    {{ number_format($grandTotalBpjsPotongan, 2, ',', '.') }}
                                                </td>
                                            @endif
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </span>
                                    <h4 class="font-bold text-slate-700 text-sm mt-1">Belum Ada Data BPJS</h4>
                                    <p class="text-xs text-slate-500 max-w-md">
                                        Data komponen BPJS untuk periode <strong>{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</strong> belum tersedia atau belum diproses.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            let isSubmittingPayroll = false;

            function updateThrMessage() {
                const isThr = document.getElementById('is_thr').checked;
                const submitBtnText = document.getElementById('submitPayrollBtnText');

                if (isThr) {
                    submitBtnText.textContent = 'Proses THR (Tunjangan Hari Raya)';
                } else {
                    submitBtnText.textContent = 'Jalankan Hitung Ulang Payroll';
                }
            }

            // Anti double-submit form handler
            document.getElementById('payroll-process-form')?.addEventListener('submit', function (e) {
                if (isSubmittingPayroll) {
                    e.preventDefault();
                    return false;
                }

                const isThr = document.getElementById('is_thr').checked;
                const month = document.getElementById('process_month').value;
                const year = document.getElementById('process_year').value;

                const message = isThr
                    ? `Apakah Anda yakin ingin memproses THR untuk periode ${month}/${year}? Data THR periode ini akan diperbarui.`
                    : `Apakah Anda yakin ingin memproses payroll untuk periode ${month}/${year}? Perhitungan sebelumnya untuk periode ini akan digantikan.`;

                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }

                isSubmittingPayroll = true;
                const submitBtn = document.getElementById('submitPayrollBtn');
                const submitText = document.getElementById('submitPayrollBtnText');
                const origText = submitText.textContent;

                submitBtn.disabled = true;
                submitText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Sedang Memproses...';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                setTimeout(() => {
                    isSubmittingPayroll = false;
                    submitBtn.disabled = false;
                    submitText.textContent = origText;
                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                }, 15000);
            });

            // Quick live filter for employee rows
            const searchInput = document.getElementById('payroll-emp-search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    const query = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('.payroll-row');
                    rows.forEach(row => {
                        const name = row.getAttribute('data-emp-name') || '';
                        row.style.display = name.includes(query) ? '' : 'none';
                    });
                });
            }

            const searchBpjsInput = document.getElementById('bpjs-emp-search');
            if (searchBpjsInput) {
                searchBpjsInput.addEventListener('keyup', function () {
                    const query = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('.bpjs-row');
                    rows.forEach(row => {
                        const name = row.getAttribute('data-emp-name') || '';
                        row.style.display = name.includes(query) ? '' : 'none';
                    });
                });
            }

            // Dynamically calculate sticky offsets for frozen columns (Slip, Employee, Project) to eliminate empty gaps
            function alignFreezeColumns() {
                ['payroll-detail-table', 'bpjs-detail-table'].forEach(function (tableId) {
                    const table = document.getElementById(tableId);
                    if (!table || table.offsetParent === null) return;

                    const th0 = table.querySelector('.col-freeze-0');
                    const th1 = table.querySelector('.col-freeze-1');
                    if (!th0 || !th1) return;

                    const w0 = Math.round(th0.getBoundingClientRect().width);
                    const w1 = Math.round(th1.getBoundingClientRect().width);
                    const left2 = w0 + w1;

                    table.querySelectorAll('.col-freeze-0').forEach(function (el) {
                        el.style.left = '0px';
                    });
                    table.querySelectorAll('.col-freeze-1').forEach(function (el) {
                        el.style.left = w0 + 'px';
                    });
                    table.querySelectorAll('.col-freeze-2').forEach(function (el) {
                        el.style.left = left2 + 'px';
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', alignFreezeColumns);
            window.addEventListener('load', alignFreezeColumns);
            window.addEventListener('resize', alignFreezeColumns);
            setTimeout(alignFreezeColumns, 150);
            setTimeout(alignFreezeColumns, 600);
        </script>
    @endpush
</x-app-layout>

