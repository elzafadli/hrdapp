<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alerts -->
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2.5 shadow-2xs">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2.5 shadow-2xs">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Top Header -->
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-200">
                <div class="px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-md shadow-emerald-100 shrink-0">
                            <i class="fa-solid fa-file-invoice-dollar text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 leading-tight">Slip Gaji Karyawan</h2>
                            <p class="text-xs text-gray-500 mt-1">
                                Employee Self Service (ESS) &bull; Karyawan: 
                                <span class="font-semibold text-gray-700">{{ $employee->name ?? Auth::user()->name }}</span> 
                                ({{ $employee->jabatan->jabatan ?? 'Karyawan' }})
                            </p>
                        </div>
                    </div>

                    <!-- Filter Tahun Form menggunakan Component -->
                    <form method="GET" action="{{ route('ess.payslip.index') }}" class="flex items-center gap-2.5">
                        <div class="w-36">
                            <x-select-input name="year" id="year-filter" :noSelect2="true" class="w-full text-xs font-semibold" onchange="this.form.submit()">
                                @if($availableYears->isEmpty())
                                    <option value="{{ now()->year }}">{{ now()->year }}</option>
                                @else
                                    @foreach($availableYears as $yearOption)
                                        <option value="{{ $yearOption }}" {{ (int)$selectedYear === (int)$yearOption ? 'selected' : '' }}>
                                            Tahun {{ $yearOption }}
                                        </option>
                                    @endforeach
                                @endif
                            </x-select-input>
                        </div>
                        @if(request()->filled('year'))
                            <a href="{{ route('ess.payslip.index') }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left text-xs"></i>
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- 3 KPI Summary Cards Sesuai Standar Golden Template ESS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Card 1: Gaji Bersih Terakhir -->
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 text-white shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-xs font-semibold uppercase tracking-wider">Take Home Pay Terakhir</p>
                        <h3 class="text-2xl lg:text-3xl font-extrabold mt-1 font-mono">
                            Rp {{ number_format($latestThp, 0, ',', '.') }}
                        </h3>
                        <p class="text-xs text-emerald-200 mt-2">
                            Periode: <strong>{{ $latestPeriodLabel }}</strong>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-wallet text-2xl text-white"></i>
                    </div>
                </div>

                <!-- Card 2: Periode Terakhir -->
                <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Slip Gaji Terakhir</p>
                        <h3 class="text-xl font-bold text-gray-800 mt-1">
                            {{ $latestPeriodLabel }}
                        </h3>
                        <p class="text-xs text-emerald-600 font-semibold mt-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-xs"></i> Status: Terverifikasi Final
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 shrink-0">
                        <i class="fa-solid fa-calendar-check text-xl"></i>
                    </div>
                </div>

                <!-- Card 3: Total Slip Tersedia -->
                <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Total Slip Diterbitkan</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-1">
                            {{ $totalPayslips }} <span class="text-sm font-normal text-gray-500">Bulan</span>
                        </h3>
                        <p class="text-xs text-gray-400 mt-2">
                            Tahun {{ $selectedYear }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 shrink-0">
                        <i class="fa-solid fa-file-lines text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Banner Kerahasiaan & Keamanan Dokumen -->
            <div class="bg-gradient-to-r from-amber-50/80 via-white to-amber-50/50 border border-amber-200 rounded-2xl p-4 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-lock text-sm"></i>
                    </div>
                    <div class="text-xs text-amber-900 leading-relaxed">
                        <span class="font-bold">Dokumen Sangat Rahasia (Confidential):</span>
                        Informasi slip gaji ini hanya ditujukan untuk Anda pribadi selaku pemilik data. 
                        Sistem membatasi akses secara otomatis hanya pada slip yang telah berstatus <strong>Final</strong> dan telah diverifikasi oleh HRD &amp; Finance.
                    </div>
                </div>
            </div>

            <!-- Tabel Riwayat Slip Gaji Mandiri -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/40">
                    <div>
                        <h3 class="font-bold text-gray-800 text-base">Riwayat Slip Gaji Saya</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Daftar periode penggajian yang telah disahkan dan siap diakses</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="fa-solid fa-circle-check text-[10px]"></i> Hanya Status Final
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-[11px] font-bold tracking-wider border-b border-gray-200">
                                <th class="py-3 px-4 text-center w-12">No</th>
                                <th class="py-3 px-4">Periode Penggajian</th>
                                <th class="py-3 px-4">Rentang Cut-Off</th>
                                <th class="py-3 px-4 text-right">Penghasilan Bruto</th>
                                <th class="py-3 px-4 text-right">Total Potongan</th>
                                <th class="py-3 px-4 text-right">Take Home Pay</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center w-36">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse($periods as $index => $p)
                                <tr class="hover:bg-gray-50/80 transition duration-150">
                                    <td class="py-3.5 px-4 text-center text-gray-400 font-semibold">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 text-sm">
                                            {{ $p->period_label }}
                                        </div>
                                        <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                            Tahun {{ $p->year }} • Periode {{ $p->month }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                            <i class="fa-regular fa-calendar text-amber-600"></i>
                                            {{ $p->cutoff_range }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap font-mono font-semibold text-emerald-700">
                                        Rp {{ number_format($p->total_income, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap font-mono font-semibold text-rose-600">
                                        Rp {{ number_format($p->total_deduction, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap font-mono font-bold text-gray-900 text-sm">
                                        Rp {{ number_format($p->thp, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-circle-check text-[10px]"></i> Final
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Tombol Detail -->
                                            <a href="{{ route('ess.payslip.show', ['month' => $p->month, 'year' => $p->year]) }}"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition"
                                                title="Lihat Rincian Slip Gaji">
                                                <i class="fa-solid fa-eye text-[11px]"></i>
                                                <span>Detail</span>
                                            </a>

                                            <!-- Tombol Cetak / PDF -->
                                            <a href="{{ route('ess.payslip.print', ['month' => $p->month, 'year' => $p->year]) }}"
                                                target="_blank"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
                                                title="Cetak Slip Gaji">
                                                <i class="fa-solid fa-print text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-gray-400">
                                        <div class="w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                            <i class="fa-solid fa-file-invoice text-2xl"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-700">Belum Ada Slip Gaji Final</p>
                                        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                                            Slip gaji untuk periode ini belum tersedia atau belum dipublikasikan oleh tim Payroll / HRD.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
