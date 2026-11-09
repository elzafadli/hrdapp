<x-app-layout>
    <div class="py-6 bg-slate-50 min-h-screen" x-data="employeeAttendanceApp()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Top Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600">
                            <i class="fa-solid fa-clipboard-user text-base"></i>
                        </span>
                        <h1 class="text-xl font-bold text-gray-900 tracking-tight">Presensi Karyawan</h1>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Rekapitulasi kehadiran, keterlambatan, dan monitoring klarifikasi izin / cuti karyawan</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-gray-200 text-gray-700 shadow-2xs">
                        <i class="fa-regular fa-calendar text-blue-600"></i>
                        <span>{{ \Carbon\Carbon::parse($startDateStr)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDateStr)->format('d M Y') }}</span>
                    </span>

                    {{-- Tombol Import Excel --}}
                    <button type="button" @click="importModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition cursor-pointer">
                        <i class="fa-solid fa-file-import text-xs"></i>
                        <span>Import Excel</span>
                    </button>

                    {{-- Tombol Export Excel --}}
                    <a href="{{ route('employee-attendance.export', request()->query()) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-gray-300 shadow-2xs transition">
                        <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i>
                        <span>Export Excel</span>
                    </a>
                </div>
            </div>

            {{-- Flash Notification Alerts --}}
            @if(session('success'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-3 shadow-xs" x-data="{ show: true }" x-show="show">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base mt-0.5 shrink-0"></i>
                    <div class="flex-1 font-medium leading-relaxed">
                        <strong class="block font-bold">Berhasil!</strong>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-emerald-500 hover:text-emerald-700 p-1 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-3 shadow-xs" x-data="{ show: true }" x-show="show">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-base mt-0.5 shrink-0"></i>
                    <div class="flex-1 font-medium leading-relaxed">
                        <strong class="block font-bold">Terjadi Kesalahan!</strong>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-rose-500 hover:text-rose-700 p-1 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-3 shadow-xs" x-data="{ show: true }" x-show="show">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base mt-0.5 shrink-0"></i>
                    <div class="flex-1 font-medium leading-relaxed">
                        <strong class="block font-bold">Pemberitahuan:</strong>
                        <span>{{ session('warning') }}</span>
                    </div>
                    <button type="button" @click="show = false" class="text-amber-500 hover:text-amber-700 p-1 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            @endif

            {{-- Filter Section --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 mb-6">
                <form method="GET" action="{{ route('employee-attendance.index') }}" class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                            <i class="fa-solid fa-filter text-blue-600 text-xs"></i>
                            <span>Filter Presensi</span>
                        </div>
                        @if(request()->hasAny(['start_date', 'end_date', 'employee_id', 'search']))
                            <a href="{{ route('employee-attendance.index') }}" class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-rotate-left"></i> Reset Filter
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-1">
                        {{-- Tgl dari --}}
                        <div>
                            <x-input-label for="start_date" :value="__('Tgl Dari')" class="text-xs font-semibold text-gray-700 mb-1" />
                            <x-text-input type="date" id="start_date" name="start_date" value="{{ $startDateStr }}" class="w-full text-xs" />
                        </div>

                        {{-- Tgl ke --}}
                        <div>
                            <x-input-label for="end_date" :value="__('Tgl Ke')" class="text-xs font-semibold text-gray-700 mb-1" />
                            <x-text-input type="date" id="end_date" name="end_date" value="{{ $endDateStr }}" class="w-full text-xs" />
                        </div>

                        {{-- Nama / NID (Client-Side Instant Search) --}}
                        <div>
                            <x-input-label for="search" :value="__('Cari Nama / NID')" class="text-xs font-semibold text-gray-700 mb-1" />
                            <div class="relative">
                                <x-text-input type="text" id="search" name="search"
                                    x-model="searchQuery"
                                    placeholder="Ketik nama atau NID..."
                                    class="w-full text-xs pl-8 pr-7"
                                    @keydown.enter.prevent="" />
                                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <button type="button" x-show="searchQuery" @click="searchQuery = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs" style="display: none;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Tombol Terapkan --}}
                        <div class="flex items-end gap-2">
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-xs">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>Terapkan</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Summary Cards --}}
            @php
                $totalWorkingDays = collect($recapData)->sum('jumlah_hari_kerja');
                $totalAttendance = collect($recapData)->sum('total_masuk');
                $totalOnTime = collect($recapData)->sum('jumlah_tepat_waktu');
                $totalClarified = collect($recapData)->sum('klarifikasi_terlambat');
                $totalUnclarified = collect($recapData)->sum('tidak_klarifikasi');
                $totalAbsent = collect($recapData)->sum('total_tidak_masuk');
            @endphp
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                <div class="bg-white rounded-2xl p-3.5 border border-gray-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-business-time text-base"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Total Hari Kerja</p>
                        <p class="text-lg font-bold text-gray-900 mt-0.5">{{ number_format($totalWorkingDays) }} <span class="text-[11px] font-normal text-gray-500">Hari</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-3.5 border border-indigo-100 bg-indigo-50/20 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-calendar-check text-base"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-indigo-700 uppercase tracking-wider">Total Masuk</p>
                        <p class="text-lg font-bold text-indigo-700 mt-0.5">{{ number_format($totalAttendance) }} <span class="text-[11px] font-normal text-indigo-600">Hadir</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-3.5 border border-gray-200/80 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-clock-rotate-left text-base"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Tepat Waktu</p>
                        <p class="text-lg font-bold text-blue-600 mt-0.5">{{ number_format($totalOnTime) }} <span class="text-[11px] font-normal text-gray-500">Hari</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-3.5 border border-emerald-100 bg-emerald-50/20 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file-circle-check text-base"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-emerald-700 uppercase tracking-wider">Klarifikasi Telat</p>
                        <p class="text-lg font-bold text-emerald-700 mt-0.5">{{ number_format($totalClarified) }} <span class="text-[11px] font-normal text-emerald-600">Kasus</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-3.5 border border-amber-100 bg-amber-50/20 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-clock text-base"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-amber-700 uppercase tracking-wider">Tanpa Klarifikasi</p>
                        <p class="text-lg font-bold text-amber-700 mt-0.5">{{ number_format($totalUnclarified) }} <span class="text-[11px] font-normal text-amber-600">Kasus</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-3.5 border border-rose-100 bg-rose-50/20 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-user-xmark text-base"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-rose-700 uppercase tracking-wider">Tidak Masuk (Alpa)</p>
                        <p class="text-lg font-bold text-rose-700 mt-0.5">{{ number_format($totalAbsent) }} <span class="text-[11px] font-normal text-rose-600">Hari</span></p>
                    </div>
                </div>
            </div>

            {{-- Main Recap Table --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <h2 class="font-bold text-sm text-gray-900 tracking-tight">Daftar Rekap Presensi Karyawan</h2>
                    </div>
                    <span class="text-xs text-gray-500">
                        Menampilkan <strong class="text-gray-900" x-text="visibleRowCount"></strong> dari {{ count($recapData) }} data karyawan
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-bold text-[11px] border-b border-gray-200">
                            <tr>
                                <th class="px-5 py-3.5">Nama</th>
                                <th class="px-4 py-3.5 text-center">NID</th>
                                <th class="px-4 py-3.5 text-center">Total Hari Kerja</th>
                                <th class="px-4 py-3.5 text-center">Total Masuk</th>
                                <th class="px-4 py-3.5 text-center">Tepat Waktu</th>
                                <th class="px-4 py-3.5 text-center">Klarifikasi Terlambat</th>
                                <th class="px-4 py-3.5 text-center">Tidak Klarifikasi</th>
                                <th class="px-4 py-3.5 text-center">Tidak Masuk / Alpa</th>
                                <th class="px-4 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recapData as $row)
                                <tr x-show="matchesSearch('{{ addslashes($row->nama) }}', '{{ addslashes($row->nid) }}')"
                                    x-transition
                                    class="hover:bg-slate-50/80 transition-colors">
                                    {{-- Nama --}}
                                    <td class="px-5 py-3.5 font-semibold text-gray-900">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
                                                {{ strtoupper(substr($row->nama, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="text-gray-900 font-bold">{{ $row->nama }}</div>
                                                <div class="text-[11px] text-gray-400 font-normal">{{ $row->employee->jabatan?->name ?? 'Karyawan' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- NID --}}
                                    <td class="px-4 py-3.5 text-center font-mono font-semibold text-slate-700">
                                        {{ $row->nid }}
                                    </td>

                                    {{-- Jumlah Hari Kerja --}}
                                    <td class="px-4 py-3.5 text-center font-bold text-gray-800">
                                        {{ $row->jumlah_hari_kerja }}
                                    </td>

                                    {{-- Total Masuk --}}
                                    <td class="px-4 py-3.5 text-center">
                                        <button type="button"
                                            @click="openModal({{ json_encode($row) }}, 'all')"
                                            class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition shadow-2xs"
                                            title="Klik untuk melihat seluruh riwayat hari kerja">
                                            {{ $row->total_masuk }}
                                        </button>
                                    </td>

                                    {{-- Jumlah Tepat Waktu --}}
                                    <td class="px-4 py-3.5 text-center">
                                        <button type="button"
                                            @click="openModal({{ json_encode($row) }}, 'on_time')"
                                            class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition shadow-2xs"
                                            title="Klik untuk melihat riwayat tepat waktu">
                                            {{ $row->jumlah_tepat_waktu }}
                                        </button>
                                    </td>

                                    {{-- Klarifikasi Terlambat (Button Trigger Modal) --}}
                                    <td class="px-4 py-3.5 text-center">
                                        @if($row->klarifikasi_terlambat > 0)
                                            <button type="button"
                                                @click="openModal({{ json_encode($row) }}, 'clarified')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 font-bold text-xs transition shadow-xs group"
                                                title="Klik untuk melihat data detail klarifikasi">
                                                <i class="fa-solid fa-file-circle-check text-emerald-500 group-hover:scale-110 transition-transform"></i>
                                                <span>{{ $row->klarifikasi_terlambat }}</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-60"></i>
                                            </button>
                                        @else
                                            <span class="inline-block px-2.5 py-0.5 text-xs text-gray-400 font-medium">0</span>
                                        @endif
                                    </td>

                                    {{-- Tidak Klarifikasi (Button Trigger Modal) --}}
                                    <td class="px-4 py-3.5 text-center">
                                        @if($row->tidak_klarifikasi > 0)
                                            <button type="button"
                                                @click="openModal({{ json_encode($row) }}, 'unclarified')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 font-bold text-xs transition shadow-xs group"
                                                title="Klik untuk melihat data detail tanpa klarifikasi">
                                                <i class="fa-solid fa-clock text-amber-500 group-hover:scale-110 transition-transform"></i>
                                                <span>{{ $row->tidak_klarifikasi }}</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-60"></i>
                                            </button>
                                        @else
                                            <span class="inline-block px-2.5 py-0.5 text-xs text-gray-400 font-medium">0</span>
                                        @endif
                                    </td>

                                    {{-- Tidak Masuk / Alpa (Button Trigger Modal) --}}
                                    <td class="px-4 py-3.5 text-center">
                                        @if($row->total_tidak_masuk > 0)
                                            <button type="button"
                                                @click="openModal({{ json_encode($row) }}, 'absent')"
                                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition shadow-2xs group"
                                                title="Klik untuk melihat tanggal tidak masuk / alpa">
                                                <i class="fa-solid fa-user-xmark text-rose-500 text-[10px]"></i>
                                                <span>{{ $row->total_tidak_masuk }}</span>
                                            </button>
                                        @else
                                            <span class="inline-block px-2.5 py-0.5 text-xs text-gray-400 font-medium">0</span>
                                        @endif
                                    </td>

                                    {{-- Mata (Action Column with Eye Icon Button) --}}
                                    <td class="px-4 py-3.5 text-center">
                                        <button type="button"
                                            @click="openModal({{ json_encode($row) }}, 'all')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-blue-600 text-slate-600 hover:text-white transition shadow-xs group"
                                            title="Lihat Detail Riwayat Seluruh Hari Kerja">
                                            <i class="fa-solid fa-eye text-xs group-hover:scale-110 transition-transform"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fa-solid fa-clipboard-question text-3xl mb-2 text-gray-300"></i>
                                        <p class="font-medium">Tidak ada data presensi yang ditemukan untuk periode ini.</p>
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Pesan Ketika Tidak Ada Karyawan yang Cocok dengan Search Live --}}
                            <tr x-show="visibleRowCount === 0 && allRows.length > 0" style="display: none;">
                                <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-user-slash text-3xl mb-2 text-gray-300"></i>
                                    <p class="font-medium">Tidak ada data karyawan yang cocok dengan pencarian "<span class="font-bold text-gray-700" x-text="searchQuery"></span>".</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if($employees->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>

            {{-- MODAL DETAIL RIWAYAT PRESENSI & KLARIFIKASI --}}
            <div
                x-show="modalOpen"
                x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                @keydown.escape.window="closeModal()"
            >
                {{-- Backdrop --}}
                <div
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    @click="closeModal()"
                    x-show="modalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                ></div>

                {{-- Modal Card --}}
                <div
                    class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden"
                    x-show="modalOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                    @click.stop
                >
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                <h3 class="text-base font-bold text-gray-900">Detail Riwayat Presensi & Keterlambatan</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Karyawan: <span class="font-bold text-gray-800" x-text="selectedEmployee?.nama"></span>
                                (NID: <span class="font-mono font-bold text-gray-800" x-text="selectedEmployee?.nid"></span>)
                                · Total Masuk: <span class="font-bold text-indigo-600" x-text="(selectedEmployee?.total_masuk ?? attendanceList.length) + ' Kehadiran'"></span>
                            </p>
                        </div>
                        <button type="button" @click="closeModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex items-center justify-center transition">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    {{-- Filter Tabs di Dalam Modal --}}
                    <div class="px-6 py-2.5 bg-slate-50 border-b border-gray-100 flex flex-wrap items-center gap-2 shrink-0">
                        <button type="button"
                            @click="filterTab = 'all'"
                            :class="filterTab === 'all' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-calendar-days text-[11px]"></i>
                            <span>Semua Hari Kerja (<span x-text="attendanceList.length"></span>)</span>
                        </button>
                        <button type="button"
                            @click="filterTab = 'on_time'"
                            :class="filterTab === 'on_time' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-clock-rotate-left text-[11px]"></i>
                            <span>Tepat Waktu (<span x-text="attendanceList.filter(d => d.type === 'on_time').length"></span>)</span>
                        </button>
                        <button type="button"
                            @click="filterTab = 'clarified'"
                            :class="filterTab === 'clarified' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-file-circle-check text-[11px]"></i>
                            <span>Klarifikasi Terlambat (<span x-text="attendanceList.filter(d => d.type === 'clarified').length"></span>)</span>
                        </button>
                        <button type="button"
                            @click="filterTab = 'unclarified'"
                            :class="filterTab === 'unclarified' ? 'bg-amber-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-clock text-[11px]"></i>
                            <span>Tidak Klarifikasi (<span x-text="attendanceList.filter(d => d.type === 'unclarified').length"></span>)</span>
                        </button>
                        <button type="button"
                            @click="filterTab = 'absent'"
                            :class="filterTab === 'absent' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-user-xmark text-[11px]"></i>
                            <span>Tidak Masuk / Alpa (<span x-text="attendanceList.filter(d => d.type === 'absent').length"></span>)</span>
                        </button>
                        <button type="button"
                            x-show="attendanceList.some(d => d.type === 'leave')"
                            @click="filterTab = 'leave'"
                            :class="filterTab === 'leave' ? 'bg-teal-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-file-lines text-[11px]"></i>
                            <span>Izin / Cuti (<span x-text="attendanceList.filter(d => d.type === 'leave').length"></span>)</span>
                        </button>
                    </div>

                    {{-- Modal Body: Table Detail --}}
                    <div class="p-6 overflow-y-auto flex-1">
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-bold text-[11px] border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3">Nama</th>
                                        <th class="px-3 py-3 text-center">NID</th>
                                        <th class="px-4 py-3 text-center whitespace-nowrap">Hari & Tanggal</th>
                                        <th class="px-4 py-3 text-center">Status Presensi</th>
                                        <th class="px-4 py-3">Izin / Cuti</th>
                                        <th class="px-4 py-3 text-center">Status Klarifikasi</th>
                                        <th class="px-4 py-3">Jam Masuk & Catatan</th>
                                        <th class="px-4 py-3 text-center">Foto Presensi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(item, index) in filteredAttendanceList" :key="index">
                                        <tr class="hover:bg-slate-50/80 transition">
                                            {{-- Nama --}}
                                            <td class="px-4 py-3 font-semibold text-gray-900" x-text="item.nama"></td>

                                            {{-- NID --}}
                                            <td class="px-3 py-3 text-center font-mono text-gray-700" x-text="item.nid"></td>

                                            {{-- Hari & Tanggal --}}
                                            <td class="px-4 py-3 text-center whitespace-nowrap font-medium text-gray-800">
                                                <span class="font-bold text-gray-900" x-text="item.hari ? item.hari + ', ' : ''"></span>
                                                <span class="font-mono text-gray-700" x-text="item.tgl_telat"></span>
                                            </td>

                                            {{-- Status Presensi (Tepat Waktu / Terlambat / Tidak Masuk / Izin Cuti) --}}
                                            <td class="px-4 py-3 text-center">
                                                <template x-if="item.type === 'absent' || ((!item.check_in_time || item.check_in_time === '-') && item.type !== 'leave')">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                        <i class="fa-solid fa-user-xmark text-[10px]"></i> Tidak Masuk
                                                    </span>
                                                </template>
                                                <template x-if="item.type === 'leave'">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                                        <i class="fa-solid fa-file-lines text-[10px]"></i> <span x-text="item.attendance_status"></span>
                                                    </span>
                                                </template>
                                                <template x-if="item.type === 'on_time' || (!item.is_late && item.check_in_time && item.check_in_time !== '-')">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                        <i class="fa-solid fa-check text-[10px]"></i> Tepat Waktu
                                                    </span>
                                                </template>
                                                <template x-if="item.type === 'clarified' || item.type === 'unclarified' || (item.is_late && item.check_in_time && item.check_in_time !== '-')">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                        <i class="fa-solid fa-clock text-[10px]"></i> Terlambat
                                                    </span>
                                                </template>
                                                <template x-if="item.type === 'holiday_work'">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                        Lembur Libur
                                                    </span>
                                                </template>
                                                <template x-if="item.type === 'upcoming'">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-500">
                                                        Mendatang
                                                    </span>
                                                </template>
                                            </td>

                                            {{-- Izin / Cuti --}}
                                            <td class="px-4 py-3">
                                                <template x-if="item.izin_cuti">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                        <i class="fa-solid fa-file-lines text-[10px]"></i>
                                                        <span x-text="item.izin_cuti"></span>
                                                    </span>
                                                </template>
                                                <template x-if="!item.izin_cuti">
                                                    <span class="text-gray-300 italic font-mono">-</span>
                                                </template>
                                            </td>

                                            {{-- Status Klarifikasi --}}
                                            <td class="px-4 py-3 text-center">
                                                <template x-if="item.type === 'absent' || ((!item.check_in_time || item.check_in_time === '-') && item.type !== 'leave')">
                                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] text-rose-700 bg-rose-50 border border-rose-200 font-bold">
                                                        Alpa (Tanpa Izin)
                                                    </span>
                                                </template>
                                                <template x-if="item.type === 'leave'">
                                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] text-teal-700 bg-teal-50 border border-teal-200 font-semibold" x-text="item.status"></span>
                                                </template>
                                                <template x-if="item.type === 'on_time' || (!item.is_late && item.check_in_time && item.check_in_time !== '-')">
                                                    <span class="text-gray-300 italic font-mono text-[11px]">-</span>
                                                </template>
                                                <template x-if="item.type === 'clarified'">
                                                    <span
                                                        :class="{
                                                            'bg-emerald-50 text-emerald-700 border-emerald-200': item.status_badge === 'success',
                                                            'bg-amber-50 text-amber-700 border-amber-200': item.status_badge === 'warning',
                                                            'bg-rose-50 text-rose-700 border-rose-200': item.status_badge === 'danger',
                                                            'bg-gray-50 text-gray-700 border-gray-200': item.status_badge === 'secondary'
                                                        }"
                                                        class="inline-block px-2.5 py-1 rounded-md text-xs font-bold border"
                                                        x-text="item.status"
                                                    ></span>
                                                </template>
                                                <template x-if="item.type === 'unclarified'">
                                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] text-amber-700 bg-amber-50 border border-amber-200 font-semibold">
                                                        Belum Klarifikasi
                                                    </span>
                                                </template>
                                            </td>

                                            {{-- Jam Masuk & Catatan --}}
                                            <td class="px-4 py-3 text-gray-600">
                                                <div class="flex items-center gap-2">
                                                    <template x-if="item.check_in_time && item.check_in_time !== '-'">
                                                        <span :class="item.is_late ? 'text-amber-700 bg-amber-50 font-bold' : 'text-blue-700 bg-blue-50 font-semibold'"
                                                              class="font-mono text-xs px-1.5 py-0.5 rounded"
                                                              x-text="item.check_in_time"></span>
                                                    </template>
                                                    <template x-if="!item.check_in_time || item.check_in_time === '-'">
                                                        <span class="font-mono text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">-</span>
                                                    </template>
                                                    <span :class="item.type === 'absent' ? 'text-rose-600 font-medium' : (item.type === 'leave' ? 'text-teal-700' : 'text-gray-500')"
                                                          class="truncate max-w-[200px]"
                                                          x-text="item.notes"
                                                          :title="item.notes"></span>
                                                </div>
                                            </td>

                                            {{-- Foto Presensi / Lampiran Berkas --}}
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <template x-if="item.photo_url">
                                                        <div class="flex items-center gap-1.5">
                                                            <button type="button" @click="previewImage = item.photo_url" class="group relative inline-block shrink-0" title="Klik untuk memperbesar foto selfie">
                                                                <img :src="item.photo_url" alt="Foto Selfie Presensi" class="w-8 h-8 rounded-lg object-cover border border-blue-200 shadow-xs group-hover:scale-105 group-hover:ring-2 group-hover:ring-blue-400 transition">
                                                                <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[7px]">
                                                                    <i class="fa-solid fa-camera"></i>
                                                                </span>
                                                            </button>
                                                            <a :href="item.photo_url" target="_blank" class="p-1 rounded-md text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Buka foto di tab baru">
                                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                            </a>
                                                        </div>
                                                    </template>
                                                    <template x-if="item.attachment_url">
                                                        <a :href="item.attachment_url" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-[10px] font-semibold transition shadow-xs" title="Lihat berkas lampiran izin/cuti">
                                                            <i class="fa-solid fa-paperclip text-[9px]"></i>
                                                            <span>Berkas</span>
                                                        </a>
                                                    </template>
                                                    <template x-if="!item.photo_url && !item.attachment_url">
                                                        <span class="text-gray-300 italic text-[11px] font-mono">-</span>
                                                    </template>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-if="filteredAttendanceList.length === 0">
                                        <tr>
                                            <td colspan="8" class="px-6 py-10 text-center text-gray-400">
                                                <i class="fa-solid fa-circle-check text-2xl text-emerald-400 mb-2"></i>
                                                <p class="font-medium">Tidak ada data presensi untuk kategori ini.</p>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-3.5 bg-slate-50 border-t border-gray-100 flex items-center justify-between shrink-0">
                        <div class="text-xs text-gray-500">
                            Total Data: <span class="font-bold text-gray-800" x-text="filteredAttendanceList.length"></span> Baris
                        </div>
                        <button type="button" @click="closeModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-xs font-semibold transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            {{-- LIGHTBOX MODAL PREVIEW FOTO PRESENSI --}}
            <div
                x-show="previewImage"
                x-cloak
                class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6"
                @keydown.escape.window="previewImage = null"
            >
                <div
                    class="fixed inset-0 bg-black/80 backdrop-blur-xs transition-opacity"
                    @click="previewImage = null"
                    x-show="previewImage"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                ></div>

                <div
                    class="relative z-10 bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden p-4 space-y-3"
                    x-show="previewImage"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.stop
                >
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            <span class="text-xs font-bold text-gray-800">Foto Selfie Presensi Karyawan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="previewImage" target="_blank" class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold flex items-center gap-1 transition shadow-xs" title="Buka gambar ukuran penuh di tab baru">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                Tab Baru
                            </a>
                            <button type="button" @click="previewImage = null" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex items-center justify-center transition">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="rounded-xl overflow-hidden bg-slate-950 flex items-center justify-center min-h-[260px] max-h-[70vh] border border-gray-100">
                        <img :src="previewImage" alt="Foto Presensi" class="w-full h-auto object-contain max-h-[70vh]">
                    </div>
                </div>
            </div>

            {{-- MODAL IMPORT EXCEL PRESENSI --}}
            <div x-show="importModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="import-modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="importModalOpen" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                        @click="importModalOpen = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div x-show="importModalOpen" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100">

                        {{-- Modal Header --}}
                        <div class="px-6 py-4 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-base shadow-2xs">
                                    <i class="fa-solid fa-file-excel"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900" id="import-modal-title">
                                        Impor Presensi Mesin / Excel
                                    </h3>
                                    <p class="text-xs text-gray-500">
                                        Unggah berkas laporan mesin tap presensi (.xls / .xlsx)
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="importModalOpen = false"
                                class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        {{-- Modal Body / Form --}}
                        <form method="POST" action="{{ route('employee-attendance.import') }}" enctype="multipart/form-data" @submit="isImporting = true">
                            @csrf

                            <div class="p-6 space-y-4">
                                {{-- Info Banner Format --}}
                                <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 text-blue-900 text-xs flex items-start gap-2.5">
                                    <i class="fa-solid fa-circle-info text-blue-600 mt-0.5 shrink-0"></i>
                                    <div class="leading-relaxed">
                                        <span class="font-bold block">Format Laporan Mesin:</span>
                                        <span>Mendukung file ekspor dari mesin presensi fingerprint / biometric (.xls) seperti <strong>Punch Report / Access Records Report</strong> (contoh: <code>report 25 sept - 25 okt.xls</code>) atau berkas Excel master (.xlsx).</span>
                                    </div>
                                </div>

                                {{-- File Upload Dropzone --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                        Pilih Berkas Laporan Presensi <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="border-2 border-dashed border-gray-300 hover:border-emerald-500 rounded-2xl p-6 text-center transition cursor-pointer bg-slate-50/50 hover:bg-emerald-50/20 relative"
                                        @dragover.prevent="" @drop.prevent="handleFileDrop($event)">
                                        <input type="file" name="file" id="import_file" accept=".xls,.xlsx,.csv" required
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            @change="handleFileSelect($event)">

                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <template x-if="!selectedFileName">
                                                <div class="flex flex-col items-center">
                                                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2">
                                                        <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
                                                    </div>
                                                    <p class="text-xs font-bold text-gray-800">
                                                        Klik untuk memilih atau seret berkas ke sini
                                                    </p>
                                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                                        Format: .xls, .xlsx, .csv (Maksimal 20 MB)
                                                    </p>
                                                </div>
                                            </template>

                                            <template x-if="selectedFileName">
                                                <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-emerald-200 shadow-2xs text-left w-full">
                                                    <span class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                                        <i class="fa-solid fa-file-excel text-base"></i>
                                                    </span>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-xs font-bold text-gray-900 truncate" x-text="selectedFileName"></p>
                                                        <p class="text-[11px] text-gray-500" x-text="selectedFileSize"></p>
                                                    </div>
                                                    <span class="text-xs text-emerald-600 font-semibold shrink-0">
                                                        <i class="fa-solid fa-circle-check"></i> Siap
                                                    </span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Pengaturan Impor --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                    <div>
                                        <label for="tolerance_time" class="block text-xs font-semibold text-gray-700 mb-1">
                                            Toleransi Keterlambatan (WIB)
                                        </label>
                                        <input type="time" name="tolerance_time" id="tolerance_time" value="08:30"
                                            class="w-full text-xs rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-emerald-500">
                                        <p class="text-[10px] text-gray-400 mt-1">Tap masuk setelah jam ini dianggap terlambat.</p>
                                    </div>

                                    <div class="flex items-center pt-5">
                                        <label class="flex items-start gap-2 cursor-pointer">
                                            <input type="checkbox" name="update_existing" value="1" checked
                                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 mt-0.5">
                                            <span class="text-xs text-gray-700 leading-snug">
                                                Perbarui data jika tanggal presensi sudah ada
                                            </span>
                                        </label>
                                    </div>
                                </div>

                            </div>

                            {{-- Modal Footer --}}
                            <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex items-center justify-end gap-2.5">
                                <button type="button" @click="importModalOpen = false"
                                    class="px-4 py-2 text-xs font-semibold rounded-xl bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" :disabled="isImporting"
                                    class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition cursor-pointer disabled:opacity-50">
                                    <span x-show="!isImporting" class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Mulai Impor</span>
                                    </span>
                                    <span x-show="isImporting" style="display: none;" class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                                        <span>Memproses Berkas...</span>
                                    </span>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function employeeAttendanceApp() {
            return {
                modalOpen: false,
                importModalOpen: false,
                selectedFileName: '',
                selectedFileSize: '',
                isImporting: false,
                selectedEmployee: null,
                attendanceList: [],
                previewImage: null,
                filterTab: 'all', // 'all', 'on_time', 'clarified', 'unclarified'
                searchQuery: '{{ addslashes(request('search', '')) }}',
                allRows: @json(collect($recapData)->map(fn($r) => ['nama' => $r->nama, 'nid' => (string)$r->nid])),
                handleFileSelect(e) {
                    const file = e.target.files[0];
                    if (file) {
                        this.selectedFileName = file.name;
                        this.selectedFileSize = (file.size / 1024).toFixed(1) + ' KB';
                    } else {
                        this.selectedFileName = '';
                        this.selectedFileSize = '';
                    }
                },
                handleFileDrop(e) {
                    const file = e.dataTransfer.files[0];
                    if (file) {
                        const input = document.getElementById('import_file');
                        if (input) {
                            input.files = e.dataTransfer.files;
                            this.selectedFileName = file.name;
                            this.selectedFileSize = (file.size / 1024).toFixed(1) + ' KB';
                        }
                    }
                },
                matchesSearch(nama, nid) {
                    if (!this.searchQuery || !this.searchQuery.trim()) return true;
                    const q = this.searchQuery.toLowerCase().trim();
                    return (nama && nama.toLowerCase().includes(q)) ||
                           (nid && String(nid).toLowerCase().includes(q));
                },
                get visibleRowCount() {
                    if (!this.searchQuery || !this.searchQuery.trim()) return this.allRows.length;
                    const q = this.searchQuery.toLowerCase().trim();
                    return this.allRows.filter(r => (r.nama && r.nama.toLowerCase().includes(q)) || (r.nid && r.nid.toLowerCase().includes(q))).length;
                },
                openModal(row, tab = 'all') {
                    this.selectedEmployee = row;
                    this.attendanceList = row.attendance_details || row.late_details || [];
                    this.filterTab = tab;
                    this.modalOpen = true;
                },
                openLateModal(row, tab = 'all') {
                    this.openModal(row, tab);
                },
                closeModal() {
                    this.modalOpen = false;
                },
                closeLateModal() {
                    this.closeModal();
                },
                get filteredAttendanceList() {
                    if (this.filterTab === 'on_time') {
                        return this.attendanceList.filter(d => d.type === 'on_time');
                    }
                    if (this.filterTab === 'clarified') {
                        return this.attendanceList.filter(d => d.type === 'clarified');
                    }
                    if (this.filterTab === 'unclarified') {
                        return this.attendanceList.filter(d => d.type === 'unclarified');
                    }
                    if (this.filterTab === 'absent') {
                        return this.attendanceList.filter(d => d.type === 'absent');
                    }
                    if (this.filterTab === 'leave') {
                        return this.attendanceList.filter(d => d.type === 'leave');
                    }
                    return this.attendanceList;
                },
                get filteredLateDetails() {
                    return this.filteredAttendanceList;
                }
            };
        }
    </script>
</x-app-layout>

