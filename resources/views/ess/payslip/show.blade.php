<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail Slip Gaji &bull; {{ $periodLabel }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 text-sm shadow-2xs">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 text-sm shadow-2xs">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Main Detail Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Header Card --}}
            <div class="px-6 py-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
                <div class="flex items-center gap-4">
                    <a href="{{ route('ess.payslip.index') }}"
                        class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition shadow-xs">
                        <i class="fa-solid fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900">
                                Rincian Slip Gaji: {{ $periodLabel }}
                            </h2>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                <i class="fa-solid fa-circle-check text-[10px]"></i> Final
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Periode: {{ $month }}/{{ $year }} &bull; Cut-Off: 
                            <span class="font-medium text-gray-700">
                                {{ $cutoffPeriod['label'] ?? ($cutoffPeriod['start_date']->translatedFormat('d M Y') . ' s/d ' . $cutoffPeriod['end_date']->translatedFormat('d M Y')) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('ess.payslip.print', ['month' => $month, 'year' => $year]) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-xs transition">
                        <i class="fa-solid fa-print text-xs"></i>
                        <span>Cetak Slip Gaji</span>
                    </a>
                </div>
            </div>

            <div class="p-6 space-y-6">

                <!-- Data Identitas Karyawan (Sesuai Golden Base Template) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-xl bg-gray-50 border border-gray-100">
                    <div>
                        <span class="block text-xs text-gray-400 font-medium">Nama Karyawan</span>
                        <span class="font-bold text-sm text-gray-800">{{ $employee->name ?? 'Karyawan' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 font-medium">NIK Karyawan</span>
                        <span class="font-bold text-sm font-mono text-gray-800">{{ $employee->nik_karyawan ?? ($employee->nik ?? '-') }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 font-medium">Jabatan</span>
                        <span class="font-bold text-sm text-gray-800">{{ $employee->jabatan->jabatan ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-400 font-medium">Unit / Proyek</span>
                        <span class="font-bold text-sm text-indigo-700">{{ $employee->project->name ?? ($employee->company->name ?? '-') }}</span>
                    </div>
                </div>

                <!-- 2 Kolom: Penerimaan (Kiri) vs Potongan (Kanan) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- KOLOM 1: PENERIMAAN / PENGHASILAN (GROSS) -->
                    <div class="bg-white rounded-xl border border-emerald-200 overflow-hidden shadow-2xs">
                        <div class="px-5 py-3.5 bg-emerald-50/80 border-b border-emerald-100 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-emerald-800 font-bold text-sm">
                                <i class="fa-solid fa-arrow-down-long text-emerald-600"></i>
                                <span>Penerimaan (Penghasilan)</span>
                            </div>
                            <span class="text-xs text-emerald-700 font-semibold">{{ $allowances->count() }} Komponen</span>
                        </div>

                        <div class="p-5">
                            <table class="w-full text-xs">
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($allowances as $item)
                                        <tr class="py-2.5">
                                            <td class="py-2 text-gray-700 font-medium">
                                                {{ $item->payrollComponent->name ?? 'Tunjangan' }}
                                                @if($item->payrollComponent && $item->payrollComponent->type === 'thr')
                                                    <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">THR</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-right font-mono font-semibold text-emerald-700">
                                                Rp {{ number_format($item->amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-4 text-center text-gray-400 italic">Tidak ada rincian penerimaan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-emerald-200 font-bold bg-emerald-50/40">
                                        <td class="py-3 px-2 text-emerald-900 text-xs uppercase tracking-wider">Total Penerimaan</td>
                                        <td class="py-3 px-2 text-right font-mono text-emerald-800 text-sm">
                                            Rp {{ number_format($totalIncome, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- KOLOM 2: POTONGAN -->
                    <div class="bg-white rounded-xl border border-rose-200 overflow-hidden shadow-2xs">
                        <div class="px-5 py-3.5 bg-rose-50/80 border-b border-rose-100 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-rose-800 font-bold text-sm">
                                <i class="fa-solid fa-arrow-up-long text-rose-600"></i>
                                <span>Potongan-Potongan</span>
                            </div>
                            <span class="text-xs text-rose-700 font-semibold">{{ $deductions->count() }} Komponen</span>
                        </div>

                        <div class="p-5">
                            <table class="w-full text-xs">
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($deductions as $item)
                                        <tr class="py-2.5">
                                            <td class="py-2 text-gray-700 font-medium">
                                                {{ $item->payrollComponent->name ?? 'Potongan' }}
                                            </td>
                                            <td class="py-2 text-right font-mono font-semibold text-rose-600">
                                                Rp {{ number_format($item->amount, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="py-4 text-center text-gray-400 italic">Tidak ada potongan pada periode ini</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-rose-200 font-bold bg-rose-50/40">
                                        <td class="py-3 px-2 text-rose-900 text-xs uppercase tracking-wider">Total Potongan</td>
                                        <td class="py-3 px-2 text-right font-mono text-rose-700 text-sm">
                                            Rp {{ number_format($totalDeduction, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Grand Summary Box: Take Home Pay (Gaji Bersih Diterima) -->
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 text-white shadow-md">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-1.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                <i class="fa-solid fa-check-double text-[10px]"></i> Total Gaji Ditransfer (Take Home Pay)
                            </span>
                            <h3 class="text-3xl lg:text-4xl font-extrabold font-mono tracking-tight text-white mt-2">
                                Rp {{ number_format($thp, 0, ',', '.') }}
                            </h3>
                            <p class="text-xs text-slate-300 italic pt-1">
                                Terbilang: <strong class="text-amber-300 font-normal underline decoration-amber-400/50">{{ terbilang($thp) }} Rupiah</strong>
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <a href="{{ route('ess.payslip.print', ['month' => $month, 'year' => $year]) }}"
                                target="_blank"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-950/50 transition">
                                <i class="fa-solid fa-print"></i>
                                <span>Cetak Slip Gaji Resmi</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Footer Notice & Kerahasiaan -->
                <div class="border-t border-gray-100 pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-gray-400">
                    <div>
                        <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i>
                        Jika terdapat ketidaksesuaian rincian potongan atau absensi, harap menghubungi bagian Finance/HRD.
                    </div>
                    <div class="font-mono text-[11px]">
                        ID Dokumen: PAY-{{ $year }}-{{ sprintf('%02d', $month) }}-{{ $employee->id }}
                    </div>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>
