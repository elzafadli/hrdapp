<x-app-layout>
    <div class="py-6" x-data="{ activeTab: '{{ $activeTab ?? 'periods' }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Card Header Navigasi -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-2xs">
                            <i class="fa-solid fa-calculator text-lg"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-800 leading-tight">
                                {{ __('Hasil & Proses Payroll (Payroll Results)') }}
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Kelola daftar periode payroll, jalankan proses penggajian bulanan, rincian BPJS, integrasi keterlambatan, dan slip gaji.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('potongan-karyawan.index') }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 shadow-2xs transition">
                            <i class="fa-solid fa-scissors text-xs text-slate-500"></i>
                            <span>Kelola Potongan Karyawan</span>
                        </a>

                        <button type="button" @click="activeTab = 'detail'; $nextTick(() => { document.getElementById('payroll-process-card')?.scrollIntoView({ behavior: 'smooth' }); })"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-semibold rounded-lg shadow-sm transition cursor-pointer">
                            <i class="fa-solid fa-bolt text-xs"></i>
                            <span>Proses Periode Payroll</span>
                        </button>
                    </div>
                </div>
            </div>

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

            <!-- ========================================================================================= -->
            <!-- TOP TAB NAVIGATION BAR (Daftar Periode, Detail Payroll, Payroll BPJS) -->
            <!-- ========================================================================================= -->
            <div class="bg-white rounded-xl border border-slate-200 p-2 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Tab 1: Daftar Periode -->
                    <button type="button" @click="activeTab = 'periods'"
                        :class="activeTab === 'periods' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                        <i class="fa-solid fa-calendar-days text-sm"></i>
                        <span>Daftar Periode Payroll</span>
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                            :class="activeTab === 'periods' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                            {{ $periods->count() }}
                        </span>
                    </button>

                    <!-- Tab 2: Detail Payroll -->
                    <button type="button" @click="activeTab = 'detail'"
                        :class="activeTab === 'detail' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                        <i class="fa-solid fa-table-list text-sm"></i>
                        <span>Detail Payroll: <strong>{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</strong></span>
                    </button>

                    <!-- Tab 3: Payroll BPJS -->
                    <button type="button" @click="activeTab = 'bpjs'"
                        :class="activeTab === 'bpjs' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                        <i class="fa-solid fa-shield-halved text-sm" :class="activeTab === 'bpjs' ? 'text-amber-300' : 'text-amber-500'"></i>
                        <span>Payroll BPJS</span>
                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                            :class="activeTab === 'bpjs' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                            {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('M Y') }}
                        </span>
                    </button>
                </div>

                <!-- Right Action per Tab -->
                <div class="flex items-center gap-2 px-1">
                    <template x-if="activeTab === 'periods'">
                        <button type="button" @click="activeTab = 'detail'; $nextTick(() => { document.getElementById('payroll-process-card')?.scrollIntoView({ behavior: 'smooth' }); })"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg border border-indigo-200 transition">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Proses Periode Baru</span>
                        </button>
                    </template>

                    <template x-if="activeTab === 'detail'">
                        <a href="{{ route('payroll-results.export', ['month' => $month, 'year' => $year]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Export Payroll</span>
                        </a>
                    </template>

                    <template x-if="activeTab === 'bpjs'">
                        <a href="{{ route('payroll-results.export-deductions', ['month' => $month, 'year' => $year]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-2xs transition">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Export BPJS</span>
                        </a>
                    </template>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- TAB 1: DAFTAR PERIODE PAYROLL (Kompak, Bersih, Tanpa Detail di Bawah) -->
            <!-- ========================================================================================= -->
            <div x-show="activeTab === 'periods'" class="space-y-6">
                <!-- Banner Ketentuan Cut-Off Penggajian & Integrasi Potongan -->
                <div class="bg-gradient-to-r from-amber-50 via-white to-indigo-50/50 border border-amber-200 rounded-xl p-4 shadow-2xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500 text-white shrink-0 mt-0.5">
                                <i class="fa-solid fa-calendar-days text-sm"></i>
                            </span>
                            <div>
                                <h4 class="font-bold text-xs sm:text-sm text-slate-800">
                                    Jadwal Penggajian &amp; Cut-Off Presensi: <span class="text-indigo-600">Tanggal 25</span>
                                </h4>
                                <p class="text-xs text-slate-600 mt-0.5">
                                    Klik tombol <strong>Detail (Tab Baru)</strong> pada baris periode untuk melihat rincian gaji, slip, atau perhitungan BPJS di tab baru.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">
                                <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> Cut-off: <strong>Tgl 25 s/d 25</strong>
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-2xs">
                                <i class="fa-solid fa-coins text-amber-500"></i> Denda: <strong>Rp 15.000</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 text-sm">
                                <i class="fa-solid fa-list-check"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm sm:text-base leading-snug">Daftar Periode Payroll Tersimpan</h3>
                                <p class="text-xs text-slate-500">Histori seluruh periode penggajian yang tersimpan dalam sistem</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                Total: <strong>{{ $periods->count() }} Periode</strong>
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table id="payroll-periods-table" class="min-w-full divide-y divide-slate-200 text-xs sm:text-sm">
                                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                                    <tr>
                                        <th class="px-4 py-3 text-center w-12">No</th>
                                        <th class="px-4 py-3 text-left">Periode Gaji</th>
                                        <th class="px-4 py-3 text-left">Rentang Cut-Off</th>
                                        <th class="px-4 py-3 text-center">Karyawan</th>
                                        <th class="px-4 py-3 text-right">Penghasilan Bruto</th>
                                        <th class="px-4 py-3 text-right">Total Potongan</th>
                                        <th class="px-4 py-3 text-right">Take Home Pay</th>
                                        <th class="px-4 py-3 text-center">Terakhir Diproses</th>
                                        <th class="px-4 py-3 text-center w-48">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($periods as $index => $p)
                                        @php
                                            $isActive = ((int) $p->month === (int) $month && (int) $p->year === (int) $year);
                                            $orderKey = sprintf('%04d%02d', $p->year, $p->month);
                                        @endphp
                                        <tr class="hover:bg-slate-50/80 transition {{ $isActive ? 'bg-indigo-50/40 font-medium' : '' }}">
                                            <td class="px-4 py-3 text-center text-slate-500 font-semibold" data-order="{{ $orderKey }}">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap" data-order="{{ $orderKey }}">
                                                <div class="flex items-center gap-2">
                                                    <div class="font-bold text-slate-900 text-sm">
                                                        {{ $p->period_label }}
                                                    </div>
                                                    @if ($isActive)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                            <i class="fa-solid fa-check"></i> Aktif
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                                    Tahun {{ $p->year }} • Periode {{ $p->month }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap" data-order="{{ $p->start_date }}">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                                    <i class="fa-regular fa-calendar text-amber-600"></i>
                                                    {{ $p->cutoff_range }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap" data-order="{{ $p->total_employees }}">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                    <i class="fa-solid fa-users text-blue-500"></i>
                                                    {{ $p->total_employees }} Orang
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-emerald-700 font-mono" data-order="{{ $p->total_gross }}">
                                                Rp {{ number_format($p->total_gross, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-700 font-mono" data-order="{{ $p->total_deductions }}">
                                                Rp {{ number_format($p->total_deductions, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap font-bold text-indigo-700 font-mono" data-order="{{ $p->total_thp }}">
                                                Rp {{ number_format($p->total_thp, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap text-xs text-slate-500 font-mono" data-order="{{ $p->last_processed_at ?? '' }}">
                                                {{ !empty($p->last_processed_at) ? \Carbon\Carbon::parse($p->last_processed_at)->translatedFormat('d M Y H:i') : '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <!-- Tombol Buka Detail di Tab Baru -->
                                                    <a href="{{ route('payroll-results.index', ['month' => $p->month, 'year' => $p->year, 'tab' => 'detail']) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer"
                                                        title="Buka Detail Payroll di Tab Baru">
                                                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                        <span>Detail</span>
                                                    </a>

                                                    <a href="{{ route('payroll-results.export', ['month' => $p->month, 'year' => $p->year]) }}"
                                                        class="inline-flex items-center gap-1 p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition"
                                                        title="Export Excel Periode Ini">
                                                        <i class="fa-solid fa-file-excel"></i>
                                                    </a>

                                                    <form action="{{ route('payroll-results.destroy-period') }}" method="POST" class="inline"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh data hasil payroll periode {{ $p->period_label }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="month" value="{{ $p->month }}">
                                                        <input type="hidden" name="year" value="{{ $p->year }}">
                                                        <button type="submit"
                                                            class="inline-flex items-center gap-1 p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 hover:text-rose-700 border border-rose-200 rounded-lg text-xs font-semibold transition"
                                                            title="Hapus Seluruh Data Periode Ini">
                                                            <i class="fa-regular fa-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-6 py-8 text-center text-slate-500">
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <i class="fa-regular fa-folder-open text-3xl text-slate-300"></i>
                                                    <p class="font-medium text-slate-600">Belum ada periode payroll yang tersimpan.</p>
                                                    <p class="text-xs text-slate-400">Silakan gunakan tab "Detail Payroll" untuk memproses payroll periode baru.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- TAB 2: DETAIL PAYROLL PERIODE (HEADER KPI, RE-PROCESS FORM, TABEL LENGKAP & SLIP) -->
            <!-- ========================================================================================= -->
            <div x-show="activeTab === 'detail'" class="space-y-6">
                <!-- Header Info & KPI Cards Periode Terpilih -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 text-sm">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base leading-tight">
                                    Header Payroll: <span class="text-indigo-600">{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Rentang Cut-Off: <strong>{{ $cutoffPeriod['label'] ?? ($cutoffPeriod['start_date']->translatedFormat('d M Y') . ' s/d ' . $cutoffPeriod['end_date']->translatedFormat('d M Y')) }}</strong>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="activeTab = 'periods'"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 transition">
                                <i class="fa-solid fa-arrow-left text-xs"></i>
                                <span>Lihat Daftar Periode</span>
                            </button>

                            <button type="button" @click="activeTab = 'bpjs'"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold rounded-lg border border-amber-200 transition">
                                <i class="fa-solid fa-shield-halved text-xs text-amber-600"></i>
                                <span>Lihat BPJS Periode Ini</span>
                            </button>

                            <a href="{{ route('payroll-results.export', ['month' => $month, 'year' => $year]) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold rounded-lg shadow-2xs transition">
                                <i class="fa-solid fa-file-excel"></i>
                                <span>Export Excel</span>
                            </a>
                        </div>
                    </div>

                    <!-- Grid 4 KPI Summary Cards -->
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50/50">
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

                <!-- Formulir Proses / Hitung Ulang Payroll & Integrasi Keterlambatan -->
                <div id="payroll-process-card" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-50 text-amber-600 text-sm">
                                <i class="fa-solid fa-sliders"></i>
                            </span>
                            <h3 class="font-bold text-slate-800 text-sm sm:text-base">Proses / Hitung Ulang Payroll Periode</h3>
                        </div>
                    </div>

                    <div class="p-6">
                        <form id="payroll-process-form" action="{{ route('payroll-results.process') }}" method="POST" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="process_month" :value="__('Pilih Bulan Penggajian')" class="font-semibold text-slate-700 text-xs mb-1" />
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
                                        Centang opsi ini jika Anda hanya ingin memproses perhitungan tunjangan hari raya pada periode tersebut.
                                    </p>
                                </div>
                            </div>

                            <div class="p-4 bg-gradient-to-r from-amber-50 via-white to-indigo-50/40 border border-amber-200 rounded-xl">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-start gap-2.5">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-500 text-white shrink-0 mt-0.5">
                                            <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                                        </span>
                                        <div>
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-900">Perhitungan Potongan Keterlambatan Presensi Otomatis</h4>
                                            <p class="text-xs text-slate-600 mt-0.5">
                                                Denda keterlambatan (&gt; 08:30 WIB) dihitung dari log presensi karyawan cut-off 25 s/d 25 dan langsung disinkronkan ke tabel <strong>Potongan Karyawan</strong>.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <button type="button" id="btn-sync-late-payroll"
                                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs font-semibold rounded-lg shadow-2xs transition cursor-pointer">
                                            <i class="fa-solid fa-bolt text-xs"></i>
                                            <span id="btn-sync-late-text">Hitung Keterlambatan Presensi</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="payroll-late-result-notice" class="hidden mt-3 pt-2.5 border-t border-amber-200 text-xs text-amber-900 font-medium"></div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                                <div class="text-xs text-slate-500">
                                    <i class="fa-solid fa-circle-info text-slate-400 mr-1"></i>
                                    Data hasil payroll periode ini akan diperbarui dan menggantikan perhitungan sebelumnya jika ada.
                                </div>

                                <div class="flex items-center gap-2.5">
                                    <button type="submit" id="submitPayrollBtn"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                                        <i class="fa-solid fa-calculator text-xs"></i>
                                        <span id="submitPayrollBtnText">Proses Payroll Periode Ini</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Card Tabel Detail Payroll Karyawan -->
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
                                    Rincian Payroll Karyawan (Detail) - {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
                                </h3>
                                <p class="text-xs text-slate-500">Rincian komponen tunjangan, potongan, total penerimaan, dan cetak slip gaji</p>
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

                            <a href="{{ route('payroll-results.export', ['month' => $month, 'year' => $year]) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-2xs transition">
                                <i class="fa-solid fa-file-excel"></i>
                                <span>Export Excel</span>
                            </a>
                        </div>
                    </div>

                    <div class="p-6">
                        @if ($groupedResults->isNotEmpty())
                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto border border-slate-200 rounded-lg">
                                <table id="payroll-detail-table" class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-50 sticky top-0 z-10 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                                        <tr>
                                            <th scope="col" class="px-3.5 py-3 text-center w-24">Slip Gaji</th>
                                            <th scope="col" class="px-4 py-3 text-left">Company</th>
                                            <th scope="col" class="px-4 py-3 text-left">Project</th>
                                            <th scope="col" class="px-4 py-3 text-left">Employee</th>
                                            @foreach($allowanceColumns as $column)
                                                <th scope="col" class="px-4 py-3 text-right">
                                                    {{ $column->name }}
                                                </th>
                                            @endforeach
                                            <th scope="col" class="px-4 py-3 text-right font-bold text-emerald-700 bg-emerald-50 border-x border-emerald-100">
                                                Total Gaji
                                            </th>
                                            @foreach($deductionColumns as $column)
                                                <th scope="col" class="px-4 py-3 text-right">
                                                    {{ $column->name }}
                                                </th>
                                            @endforeach
                                            <th scope="col" class="px-4 py-3 text-right font-bold text-rose-700 bg-rose-50 border-x border-rose-100">
                                                Total Potongan
                                            </th>
                                            <th scope="col" class="px-4 py-3 text-right font-bold text-indigo-700 bg-indigo-50">
                                                Take Home Pay
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100 text-slate-700">
                                        @foreach ($groupedResults as $empId => $empResults)
                                            @php
                                                $employee = $empResults->first()->employee;
                                                $totalGaji = 0;
                                                $totalPotongan = 0;
                                            @endphp
                                            <tr class="payroll-row hover:bg-slate-50/80 transition" data-emp-name="{{ strtolower($employee->name ?? '') }}">
                                                <td class="px-3.5 py-3 whitespace-nowrap text-center">
                                                    <a href="{{ route('payroll-results.slip', ['emp_id' => $employee->id, 'month' => $month, 'year' => $year]) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 active:bg-indigo-200 text-indigo-700 border border-indigo-200 text-xs font-semibold rounded-md shadow-2xs transition">
                                                        <i class="fa-solid fa-receipt text-[11px]"></i>
                                                        <span>Slip</span>
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                                    {{ $employee->project->company->code ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                                    {{ $employee->project->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <div class="font-bold text-slate-900">{{ $employee->name ?? 'N/A' }}</div>
                                                    <div class="text-[11px] text-slate-500">{{ $employee->jabatan->jabatan ?? ($employee->nik ?? '-') }}</div>
                                                </td>
                                                @foreach($allowanceColumns as $column)
                                                    @php
                                                        $result = $empResults->firstWhere('payroll_component_id', $column->id);
                                                        $amount = $result ? $result->amount : 0;
                                                        $totalGaji += $amount;
                                                    @endphp
                                                    <td class="px-4 py-3 whitespace-nowrap text-right font-mono">
                                                        {{ $result && $result->amount > 0 ? number_format($result->amount, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-emerald-700 bg-emerald-50/60 font-mono border-x border-emerald-100">
                                                    {{ number_format($totalGaji, 2, ',', '.') }}
                                                </td>
                                                @foreach($deductionColumns as $column)
                                                    @php
                                                        $result = $empResults->firstWhere('payroll_component_id', $column->id);
                                                        $amount = $result ? $result->amount : 0;
                                                        $totalPotongan += $amount;
                                                    @endphp
                                                    <td class="px-4 py-3 whitespace-nowrap text-right font-mono">
                                                        {{ $result && $result->amount > 0 ? number_format($result->amount, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-rose-700 bg-rose-50/60 font-mono border-x border-rose-100">
                                                    {{ number_format($totalPotongan, 2, ',', '.') }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-indigo-700 bg-indigo-50/60 font-mono">
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
                                            <td colspan="4" class="px-4 py-3.5 whitespace-nowrap text-slate-800 font-bold uppercase text-[11px]">
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
                                        Silakan tekan tombol <strong>"Proses Payroll Periode Ini"</strong> pada kartu di atas.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ========================================================================================= -->
            <!-- TAB 3: PAYROLL BPJS (RINCIAN SUBSIDI PERUSAHAAN & POTONGAN BPJS KARYAWAN) -->
            <!-- ========================================================================================= -->
            <div x-show="activeTab === 'bpjs'" class="space-y-6">
                @php
                    $subsidiColumns = $payrollColumns->where('type', 'subsidi');
                    $bpjsColumns = $payrollColumns->where('type', 'bpjs');
                @endphp

                <!-- Header Info BPJS -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-50 text-amber-600 text-sm">
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base leading-tight">
                                    Rincian BPJS Karyawan &amp; Perusahaan: <span class="text-indigo-600">{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Komparasi iuran BPJS yang ditanggung perusahaan (Subsidi) vs dipotong dari penghasilan karyawan
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @click="activeTab = 'detail'"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200 transition">
                                <i class="fa-solid fa-arrow-left text-xs"></i>
                                <span>Kembali ke Detail Payroll</span>
                            </button>

                            <a href="{{ route('payroll-results.export-deductions', ['month' => $month, 'year' => $year]) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white text-xs font-semibold rounded-lg shadow-2xs transition">
                                <i class="fa-solid fa-file-excel"></i>
                                <span>Export BPJS Excel</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card Tabel BPJS -->
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
                                <p class="text-xs text-slate-500">Iuran BPJS Ketenagakerjaan (JKK, JKM, JHT, JKN) &amp; BPJS Kesehatan</p>
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
                                <table id="bpjs-detail-table" class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-50 sticky top-0 z-10 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                                        <!-- Top Header: Group Colspan -->
                                        <tr>
                                            <th rowspan="2" class="px-3.5 py-3 text-center w-24 border-b border-slate-200">Slip</th>
                                            <th rowspan="2" class="px-4 py-3 text-left border-b border-slate-200">Company</th>
                                            <th rowspan="2" class="px-4 py-3 text-left border-b border-slate-200">Project</th>
                                            <th rowspan="2" class="px-4 py-3 text-left border-b border-slate-200">Employee</th>

                                            @if($subsidiColumns->isNotEmpty())
                                                <th colspan="{{ $subsidiColumns->count() + 1 }}" class="px-3 py-2 text-center font-bold text-amber-900 bg-amber-100 border-x border-amber-200">
                                                    <i class="fa-solid fa-building text-xs mr-1 text-amber-700"></i> Dibayar Perusahaan (Subsidi BPJS)
                                                </th>
                                            @endif

                                            @if($bpjsColumns->isNotEmpty())
                                                <th colspan="{{ $bpjsColumns->count() + 1 }}" class="px-3 py-2 text-center font-bold text-rose-900 bg-rose-100 border-x border-rose-200">
                                                    <i class="fa-solid fa-user-tag text-xs mr-1 text-rose-700"></i> Dibayar Karyawan (Potongan BPJS)
                                                </th>
                                            @endif
                                        </tr>

                                        <!-- Sub Header: Column Names -->
                                        <tr class="bg-slate-100 text-[10px]">
                                            @foreach($subsidiColumns as $col)
                                                <th class="px-3 py-2 text-right text-amber-800 bg-amber-50 font-semibold border-b border-slate-200">
                                                    {{ $col->name }}
                                                </th>
                                            @endforeach
                                            @if($subsidiColumns->isNotEmpty())
                                                <th class="px-3 py-2 text-right font-bold text-amber-900 bg-amber-100 border-b border-amber-200">
                                                    Total Subsidi
                                                </th>
                                            @endif

                                            @foreach($bpjsColumns as $col)
                                                <th class="px-3 py-2 text-right text-rose-800 bg-rose-50 font-semibold border-b border-slate-200">
                                                    {{ $col->name }}
                                                </th>
                                            @endforeach
                                            @if($bpjsColumns->isNotEmpty())
                                                <th class="px-3 py-2 text-right font-bold text-rose-900 bg-rose-100 border-b border-rose-200">
                                                    Total Potongan
                                                </th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100 text-slate-700">
                                        @foreach ($groupedResults as $empId => $empResults)
                                            @php
                                                $employee = $empResults->first()->employee;
                                                $totalSubsidi = 0;
                                                $totalBpjsPotongan = 0;
                                            @endphp
                                            <tr class="bpjs-row hover:bg-slate-50/80 transition" data-emp-name="{{ strtolower($employee->name ?? '') }}">
                                                <td class="px-3.5 py-3 whitespace-nowrap text-center">
                                                    <a href="{{ route('payroll-results.slip', ['emp_id' => $employee->id, 'month' => $month, 'year' => $year]) }}"
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold rounded-md shadow-2xs transition">
                                                        <i class="fa-solid fa-receipt text-[11px]"></i>
                                                        <span>Slip</span>
                                                    </a>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                                    {{ $employee->project->company->code ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-slate-800">
                                                    {{ $employee->project->name ?? 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <div class="font-bold text-slate-900">{{ $employee->name ?? 'N/A' }}</div>
                                                    <div class="text-[11px] text-slate-500">{{ $employee->jabatan->jabatan ?? '-' }}</div>
                                                </td>

                                                <!-- Kolom Subsidi Perusahaan -->
                                                @foreach($subsidiColumns as $col)
                                                    @php
                                                        $res = $empResults->firstWhere('payroll_component_id', $col->id);
                                                        $amt = $res ? (float) $res->amount : 0;
                                                        $totalSubsidi += $amt;
                                                    @endphp
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-mono bg-amber-50/20">
                                                        {{ $amt > 0 ? number_format($amt, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                @if($subsidiColumns->isNotEmpty())
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-bold text-amber-800 bg-amber-50/70 font-mono border-r border-amber-200">
                                                        {{ number_format($totalSubsidi, 2, ',', '.') }}
                                                    </td>
                                                @endif

                                                <!-- Kolom Potongan Karyawan -->
                                                @foreach($bpjsColumns as $col)
                                                    @php
                                                        $res = $empResults->firstWhere('payroll_component_id', $col->id);
                                                        $amt = $res ? (float) $res->amount : 0;
                                                        $totalBpjsPotongan += $amt;
                                                    @endphp
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-mono bg-rose-50/20">
                                                        {{ $amt > 0 ? number_format($amt, 2, ',', '.') : '-' }}
                                                    </td>
                                                @endforeach
                                                @if($bpjsColumns->isNotEmpty())
                                                    <td class="px-3 py-3 whitespace-nowrap text-right font-bold text-rose-800 bg-rose-50/70 font-mono">
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
                                            <td colspan="4" class="px-4 py-3.5 whitespace-nowrap text-slate-800 font-bold uppercase text-[11px]">
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
                    submitBtnText.textContent = 'Proses Payroll Periode Ini';
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

            // Handler Hitung & Sinkronkan Keterlambatan Presensi via AJAX
            const btnSyncLate = document.getElementById('btn-sync-late-payroll');
            if (btnSyncLate) {
                btnSyncLate.addEventListener('click', function () {
                    const month = document.getElementById('process_month').value;
                    const year = document.getElementById('process_year').value;

                    if (!month || !year) {
                        alert('Pilih Bulan dan Tahun terlebih dahulu.');
                        return;
                    }

                    const btnText = document.getElementById('btn-sync-late-text');
                    const origText = btnText.textContent;
                    btnSyncLate.disabled = true;
                    btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Menghitung...';

                    fetch("{{ route('payroll-results.sync-late') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            month: month,
                            year: year
                        })
                    })
                    .then(response => response.json())
                    .then(res => {
                        btnSyncLate.disabled = false;
                        btnText.textContent = origText;
                        if (res.success) {
                            const notice = document.getElementById('payroll-late-result-notice');
                            notice.classList.remove('hidden');
                            notice.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-600 mr-1.5"></i> ${res.message} (Ditemukan <strong>${res.data.summary.total_late_events}x</strong> keterlambatan pada <strong>${res.data.summary.total_late_employees}</strong> karyawan, Total Denda: <strong>Rp ${res.data.summary.formatted_total}</strong>).`;
                            alert(`Keterlambatan presensi berhasil dihitung!\nDitemukan: ${res.data.summary.total_late_events}x keterlambatan.\nTotal Denda: Rp ${res.data.summary.formatted_total}\n\nData denda telah disinkronkan ke Potongan Karyawan.`);
                        } else {
                            alert(res.message || 'Gagal memproses denda keterlambatan.');
                        }
                    })
                    .catch(err => {
                        btnSyncLate.disabled = false;
                        btnText.textContent = origText;
                        alert('Terjadi kesalahan koneksi saat memproses keterlambatan presensi.');
                    });
                });
            }

            // Quick live filter for employee rows (both payroll and bpjs tabs)
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

            // Inisialisasi DataTable untuk Daftar Periode Payroll
            $(document).ready(function () {
                if ($('#payroll-periods-table tbody tr td').length > 1) {
                    $('#payroll-periods-table').DataTable({
                        pageLength: 10,
                        order: [[1, 'desc']],
                        language: {
                            search: "Cari Periode:",
                            lengthMenu: "Tampilkan _MENU_ data",
                            info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ periode",
                            infoEmpty: "Tidak ada data periode",
                            zeroRecords: "Periode tidak ditemukan",
                            paginate: {
                                first: "Awal",
                                last: "Akhir",
                                next: "Lanjut",
                                previous: "Kembali"
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
