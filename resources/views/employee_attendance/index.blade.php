<x-app-layout>
    <div class="py-6 bg-slate-50 min-h-screen"
        x-data="{
            modalOpen: false,
            selectedEmployee: null,
            lateDetails: [],
            filterTab: 'all', // 'all', 'clarified', 'unclarified'
            searchQuery: '{{ addslashes(request('search', '')) }}',
            allRows: @json(collect($recapData)->map(fn($r) => ['nama' => $r->nama, 'nid' => (string)$r->nid])),
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
            onMonthChange(monthVal, yearVal = '{{ $selectedYear }}') {
                if (!monthVal) return;
                const m = parseInt(monthVal);
                const y = parseInt(yearVal);
                
                // Tanggal akhir: 25 bulan yang dipilih
                const endDate = new Date(y, m - 1, 25);
                // Tanggal awal: 25 bulan sebelumnya
                const startDate = new Date(y, m - 2, 25);

                const pad = (n) => String(n).padStart(2, '0');
                const startStr = `${startDate.getFullYear()}-${pad(startDate.getMonth() + 1)}-${pad(startDate.getDate())}`;
                const endStr = `${endDate.getFullYear()}-${pad(endDate.getMonth() + 1)}-${pad(endDate.getDate())}`;

                const sInput = document.getElementById('start_date');
                const eInput = document.getElementById('end_date');
                if (sInput) sInput.value = startStr;
                if (eInput) eInput.value = endStr;
            },
            openLateModal(row, tab = 'all') {
                this.selectedEmployee = row;
                this.lateDetails = row.late_details || [];
                this.filterTab = tab;
                this.modalOpen = true;
            },
            closeLateModal() {
                this.modalOpen = false;
            },
            get filteredLateDetails() {
                if (this.filterTab === 'clarified') {
                    return this.lateDetails.filter(d => d.has_clarification);
                }
                if (this.filterTab === 'unclarified') {
                    return this.lateDetails.filter(d => !d.has_clarification);
                }
                return this.lateDetails;
            }
        }">
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
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 shadow-xs">
                        <i class="fa-regular fa-calendar text-blue-600"></i>
                        <span>{{ \Carbon\Carbon::parse($startDateStr)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDateStr)->format('d M Y') }}</span>
                    </span>
                </div>
            </div>

            {{-- Filter Section --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs p-5 mb-6">
                <form method="GET" action="{{ route('employee-attendance.index') }}" class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                            <i class="fa-solid fa-filter text-blue-600 text-xs"></i>
                            <span>Filter Presensi</span>
                        </div>
                        @if(request()->hasAny(['start_date', 'end_date', 'month', 'employee_id', 'search']))
                            <a href="{{ route('employee-attendance.index') }}" class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center gap-1">
                                <i class="fa-solid fa-rotate-left"></i> Reset Filter
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 pt-1">
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

                        {{-- Bulan Cepat (Cut-off 25) --}}
                        <div>
                            <x-input-label for="month" :value="__('Bulan (Cut-off 25)')" class="text-xs font-semibold text-gray-700 mb-1" />
                            <x-select-input id="month" name="month" :placeholder="__('-- Pilih Bulan --')" class="w-full" :noSelect2="true" @change="onMonthChange($event.target.value)">
                                @php
                                    $months = [
                                        '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
                                        '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
                                        '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                    ];
                                @endphp
                                @foreach($months as $val => $label)
                                    <option value="{{ $val }}" {{ (string)$selectedMonth === (string)$val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </x-select-input>
                        </div>

                        {{-- Nama / NID (Client-Side Instant Search) --}}
                        <div>
                            <x-input-label for="search" :value="__('Cari Nama / NID (Live)')" class="text-xs font-semibold text-gray-700 mb-1" />
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
                $totalOnTime = collect($recapData)->sum('jumlah_tepat_waktu');
                $totalClarified = collect($recapData)->sum('klarifikasi_terlambat');
                $totalUnclarified = collect($recapData)->sum('tidak_klarifikasi');
            @endphp
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-business-time text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total Hari Kerja</p>
                        <p class="text-xl font-bold text-gray-900 mt-0.5">{{ number_format($totalWorkingDays) }} <span class="text-xs font-normal text-gray-500">Hari</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-gray-200/80 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-clock-rotate-left text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Tepat Waktu</p>
                        <p class="text-xl font-bold text-blue-600 mt-0.5">{{ number_format($totalOnTime) }} <span class="text-xs font-normal text-gray-500">Kehadiran</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-emerald-100 bg-emerald-50/20 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-file-circle-check text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Klarifikasi Terlambat</p>
                        <p class="text-xl font-bold text-emerald-700 mt-0.5">{{ number_format($totalClarified) }} <span class="text-xs font-normal text-emerald-600">Kasus</span></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-rose-100 bg-rose-50/20 shadow-xs flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold text-rose-700 uppercase tracking-wider">Tidak Klarifikasi</p>
                        <p class="text-xl font-bold text-rose-700 mt-0.5">{{ number_format($totalUnclarified) }} <span class="text-xs font-normal text-rose-600">Kasus</span></p>
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
                                <th class="px-4 py-3.5 text-center">Tepat Waktu</th>
                                <th class="px-4 py-3.5 text-center">Klarifikasi Terlambat</th>
                                <th class="px-4 py-3.5 text-center">Tidak Klarifikasi</th>
                                <th class="px-4 py-3.5 text-center">Mata</th>
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

                                    {{-- Jumlah Tepat Waktu --}}
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            {{ $row->jumlah_tepat_waktu }}
                                        </span>
                                    </td>

                                    {{-- Klarifikasi Terlambat (Button Trigger Modal) --}}
                                    <td class="px-4 py-3.5 text-center">
                                        @if($row->klarifikasi_terlambat > 0)
                                            <button type="button"
                                                @click="openLateModal({{ json_encode($row) }}, 'clarified')"
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
                                                @click="openLateModal({{ json_encode($row) }}, 'unclarified')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-bold text-xs transition shadow-xs group"
                                                title="Klik untuk melihat data detail tanpa klarifikasi">
                                                <i class="fa-solid fa-circle-exclamation text-rose-500 group-hover:scale-110 transition-transform"></i>
                                                <span>{{ $row->tidak_klarifikasi }}</span>
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-60"></i>
                                            </button>
                                        @else
                                            <span class="inline-block px-2.5 py-0.5 text-xs text-gray-400 font-medium">0</span>
                                        @endif
                                    </td>

                                    {{-- Mata (Action Column with Eye Icon Button) --}}
                                    <td class="px-4 py-3.5 text-center">
                                        <button type="button"
                                            @click="openLateModal({{ json_encode($row) }}, 'all')"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-blue-600 text-slate-600 hover:text-white transition shadow-xs group"
                                            title="Lihat Detail Keterlambatan">
                                            <i class="fa-solid fa-eye text-xs group-hover:scale-110 transition-transform"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fa-solid fa-clipboard-question text-3xl mb-2 text-gray-300"></i>
                                        <p class="font-medium">Tidak ada data presensi yang ditemukan untuk periode ini.</p>
                                    </td>
                                </tr>
                            @endforelse

                            {{-- Pesan Ketika Tidak Ada Karyawan yang Cocok dengan Search Live --}}
                            <tr x-show="visibleRowCount === 0 && allRows.length > 0" style="display: none;">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
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

            {{-- MODAL DETAIL KETERLAMBATAN & KLARIFIKASI --}}
            <div
                x-show="modalOpen"
                x-cloak
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
                @keydown.escape.window="closeLateModal()"
            >
                {{-- Backdrop --}}
                <div
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    @click="closeLateModal()"
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
                    class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden"
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
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <h3 class="text-base font-bold text-gray-900">Detail Keterlambatan & Klarifikasi</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Karyawan: <span class="font-bold text-gray-800" x-text="selectedEmployee?.nama"></span>
                                (NID: <span class="font-mono font-bold text-gray-800" x-text="selectedEmployee?.nid"></span>)
                            </p>
                        </div>
                        <button type="button" @click="closeLateModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex items-center justify-center transition">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    {{-- Filter Tabs di Dalam Modal --}}
                    <div class="px-6 py-2.5 bg-slate-50 border-b border-gray-100 flex items-center gap-2 shrink-0">
                        <button type="button"
                            @click="filterTab = 'all'"
                            :class="filterTab === 'all' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition">
                            Semua Telat (<span x-text="lateDetails.length"></span>)
                        </button>
                        <button type="button"
                            @click="filterTab = 'clarified'"
                            :class="filterTab === 'clarified' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-file-circle-check text-[11px]"></i>
                            <span>Klarifikasi Terlambat (<span x-text="lateDetails.filter(d => d.has_clarification).length"></span>)</span>
                        </button>
                        <button type="button"
                            @click="filterTab = 'unclarified'"
                            :class="filterTab === 'unclarified' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
                            class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-[11px]"></i>
                            <span>Tidak Klarifikasi (<span x-text="lateDetails.filter(d => !d.has_clarification).length"></span>)</span>
                        </button>
                    </div>

                    {{-- Modal Body: Table Detail (Sesuai Layout Contoh Pengguna) --}}
                    <div class="p-6 overflow-y-auto flex-1">
                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-bold text-[11px] border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3">Nama</th>
                                        <th class="px-3 py-3 text-center">NID</th>
                                        <th class="px-4 py-3 text-center">Tgl Telat</th>
                                        <th class="px-4 py-3">Izin / Cuti</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3">Jam Masuk & Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(item, index) in filteredLateDetails" :key="index">
                                        <tr class="hover:bg-slate-50/80 transition">
                                            {{-- Nama --}}
                                            <td class="px-4 py-3 font-semibold text-gray-900" x-text="item.nama"></td>

                                            {{-- NID --}}
                                            <td class="px-3 py-3 text-center font-mono text-gray-700" x-text="item.nid"></td>

                                            {{-- Tgl Telat --}}
                                            <td class="px-4 py-3 text-center font-medium text-gray-800" x-text="item.tgl_telat"></td>

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

                                            {{-- Status --}}
                                            <td class="px-4 py-3 text-center">
                                                <template x-if="item.status">
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
                                                <template x-if="!item.status">
                                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] text-gray-400 bg-gray-50 border border-gray-100">
                                                        Belum Ada
                                                    </span>
                                                </template>
                                            </td>

                                            {{-- Jam Masuk & Catatan --}}
                                            <td class="px-4 py-3 text-gray-600">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-mono text-xs font-semibold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded" x-text="item.check_in_time"></span>
                                                    <span class="text-gray-500 truncate max-w-[200px]" x-text="item.notes" :title="item.notes"></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-if="filteredLateDetails.length === 0">
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                                <i class="fa-solid fa-circle-check text-2xl text-emerald-400 mb-2"></i>
                                                <p class="font-medium">Tidak ada data keterlambatan untuk kategori ini.</p>
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
                            Total Data: <span class="font-bold text-gray-800" x-text="filteredLateDetails.length"></span> Baris
                        </div>
                        <button type="button" @click="closeLateModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-xs font-semibold transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

