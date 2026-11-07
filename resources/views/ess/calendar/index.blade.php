<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl text-gray-900 leading-tight flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-calendar-days text-lg"></i>
                    </div>
                    <span>Kalender ESS</span>
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Visualisasi terpadu jadwal pengajuan cuti, izin, sakit, lembur, dan hari libur nasional. Klik pengajuan untuk melihat detail lengkap tanpa perlu membuka dokumen satu per satu.
                </p>
            </div>

            <!-- Quick Action Shortcuts -->
            <div class="flex items-center gap-2">
                <a href="{{ route('leave.create') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    <i class="fa-solid fa-plus text-[11px]"></i>
                    <span>Ajukan Cuti</span>
                </a>
                <a href="{{ route('overtime.create') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                    <i class="fa-solid fa-business-time text-[11px]"></i>
                    <span>Ajukan Lembur</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div
        x-data="{
            isLoading: false,
            showDetailModal: false,
            showDayModal: false,
            activeEvent: null,
            activeDayEvents: [],
            activeDayTitle: '',
            activeDayHoliday: null,

            // Filter Reaktif Client-Side (Instant 0ms, Tanpa Reload!)
            filterType: '{{ $filterType }}',
            filterStatus: '{{ $filterStatus }}',
            scope: '{{ !empty($isApprover) ? $scope : 'my' }}',
            mobileTab: 'calendar', // 'calendar' or 'agenda'

            // Data Kalender & Bulan
            month: {{ $month }},
            year: {{ $year }},
            currentMonthName: '{{ $currentMonthName }}',
            prevMonth: {{ json_encode($prevMonth) }},
            nextMonth: {{ json_encode($nextMonth) }},
            rawDays: {{ json_encode($calendarGrid) }},
            selectedDay: null,

            init() {
                this.updateSelectedDay();
            },

            updateSelectedDay() {
                this.selectedDay = this.rawDays.find(d => d.is_today && d.is_current_month)
                    || this.rawDays.find(d => d.is_current_month && this.getDayEventCount(d) > 0)
                    || this.rawDays.find(d => d.is_current_month)
                    || this.rawDays[0];
            },

            // Saat tanggal diklik: langsung buka pop-up modal agenda tanpa perlu scroll ke bawah
            selectDay(day) {
                this.selectedDay = day;
                this.openDayList(day);
            },

            // Fungsi Cek Kelayakan Event Berdasarkan Filter Client-Side
            isEventVisible(ev) {
                if (ev.status === 'cancelled') return false;
                if (this.scope === 'my' && !ev.is_mine) return false;
                if (this.filterType !== 'all' && ev.type !== this.filterType) return false;
                if (this.filterStatus !== 'all') {
                    if (this.filterStatus === 'approved' && ev.status !== 'approved' && ev.status !== 'completed') return false;
                    if (this.filterStatus === 'pending' && ev.status !== 'pending') return false;
                    if (this.filterStatus === 'rejected' && ev.status !== 'rejected') return false;
                }
                return true;
            },

            // Dapatkan Daftar Event Tersaring untuk Satu Hari Tertentu
            getDayEvents(day) {
                if (!day || !day.events) return [];
                return day.events.filter(ev => this.isEventVisible(ev));
            },

            getDayEventCount(day) {
                return this.getDayEvents(day).length;
            },

            // Kalkulasi Statistik Dinamis Sesuai Filter Client-Side Saat Ini
            get stats() {
                let total = 0, approved = 0, pending = 0, rejected = 0, leaveDays = 0, otHours = 0;
                const counted = new Set();

                this.rawDays.forEach(day => {
                    if (!day.is_current_month || !day.events) return;
                    day.events.forEach(ev => {
                        if (!this.isEventVisible(ev)) return;
                        if (!counted.has(ev.id)) {
                            counted.add(ev.id);
                            total++;
                            if (ev.status === 'approved' || ev.status === 'completed') {
                                approved++;
                                if (ev.type === 'overtime') {
                                    otHours += parseFloat(ev.duration_numeric) || 0;
                                } else {
                                    leaveDays += parseFloat(ev.duration_numeric) || 1;
                                }
                            } else if (ev.status === 'pending') {
                                pending++;
                            } else if (ev.status === 'rejected') {
                                rejected++;
                            }
                        }
                    });
                });

                return {
                    total_submissions: total,
                    approved: approved,
                    pending: pending,
                    rejected: rejected,
                    total_leave_days: leaveDays.toFixed(1).replace('.0', ''),
                    total_ot_hours: otHours.toFixed(1).replace('.0', ''),
                };
            },

            // Dapatkan Seluruh Event Bulan Berjalan yang Tersaring (Untuk Tab Agenda Mobile)
            get monthAgendaEvents() {
                const list = [];
                const counted = new Set();

                this.rawDays.forEach(day => {
                    if (!day.is_current_month || !day.events) return;
                    day.events.forEach(ev => {
                        if (this.isEventVisible(ev) && !counted.has(ev.id)) {
                            counted.add(ev.id);
                            list.push(ev);
                        }
                    });
                });

                return list;
            },

            // Navigasi Bulan via AJAX Mulus (Dengan Loading Block Overlay & Anti-Double-Submit)
            async fetchMonth(m, y) {
                if (this.isLoading) return;
                this.isLoading = true;

                try {
                    const url = `{{ route('calendar.index') }}?month=${m}&year=${y}`;
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) throw new Error('Network error');

                    const data = await res.json();
                    this.month = data.month;
                    this.year = data.year;
                    this.currentMonthName = data.currentMonthName;
                    this.prevMonth = data.prevMonth;
                    this.nextMonth = data.nextMonth;
                    this.rawDays = data.calendarGrid;

                    this.updateSelectedDay();
                    window.history.pushState(null, '', url);
                } catch (e) {
                    window.location.href = `{{ route('calendar.index') }}?month=${m}&year=${y}`;
                } finally {
                    this.isLoading = false;
                }
            },

            openEvent(event) {
                this.activeEvent = event;
                this.showDetailModal = true;
            },
            closeDetail() {
                this.showDetailModal = false;
                this.activeEvent = null;
            },
            openDayList(dayObj) {
                this.activeDayTitle = dayObj.day_name + ', ' + dayObj.date;
                this.activeDayHoliday = dayObj.holiday;
                this.activeDayEvents = this.getDayEvents(dayObj);
                this.showDayModal = true;
            },
            closeDayList() {
                this.showDayModal = false;
                this.activeDayEvents = [];
                this.activeDayHoliday = null;
            },
            selectEventFromDay(event) {
                this.showDayModal = false;
                this.openEvent(event);
            }
        }"
        class="py-4 sm:py-6 px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6 max-w-7xl mx-auto"
    >
        {{-- 1. RINGKASAN STATISTIK BULAN INI (REAL-TIME SESUAI FILTER CLIENT) --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-4">
            <!-- Total Pengajuan -->
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-gray-200/90 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">Total Pengajuan</span>
                    <span class="text-xl sm:text-2xl font-extrabold text-gray-900 mt-0.5 sm:mt-1 block" x-text="stats.total_submissions"></span>
                    <span class="text-[10px] sm:text-[11px] text-gray-500 truncate block" x-text="'Bulan ' + currentMonthName"></span>
                </div>
                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <i class="fa-solid fa-list-check text-base sm:text-lg"></i>
                </div>
            </div>

            <!-- Disetujui -->
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-gray-200/90 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">Disetujui</span>
                    <span class="text-xl sm:text-2xl font-extrabold text-emerald-600 mt-0.5 sm:mt-1 block" x-text="stats.approved"></span>
                    <span class="text-[10px] sm:text-[11px] text-emerald-700/80 font-medium">Telah disetujui</span>
                </div>
                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <i class="fa-solid fa-circle-check text-base sm:text-lg"></i>
                </div>
            </div>

            <!-- Menunggu Persetujuan -->
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-gray-200/90 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">Menunggu</span>
                    <span class="text-xl sm:text-2xl font-extrabold text-amber-500 mt-0.5 sm:mt-1 block" x-text="stats.pending"></span>
                    <span class="text-[10px] sm:text-[11px] text-amber-600 font-medium">Dalam alur approval</span>
                </div>
                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center shrink-0 border border-amber-100">
                    <i class="fa-solid fa-hourglass-half text-base sm:text-lg animate-pulse"></i>
                </div>
            </div>

            <!-- Ditolak -->
            <div class="bg-white rounded-2xl p-3 sm:p-4 border border-gray-200/90 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">Ditolak</span>
                    <span class="text-xl sm:text-2xl font-extrabold text-rose-600 mt-0.5 sm:mt-1 block" x-text="stats.rejected"></span>
                    <span class="text-[10px] sm:text-[11px] text-gray-400">Tidak disetujui</span>
                </div>
                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0 border border-rose-100">
                    <i class="fa-solid fa-circle-xmark text-base sm:text-lg"></i>
                </div>
            </div>

            <!-- Total Durasi Kerja Terjadwal -->
            <div class="col-span-2 lg:col-span-1 bg-white rounded-2xl p-3 sm:p-4 border border-gray-200/90 shadow-2xs flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-semibold text-gray-400 uppercase tracking-wider block">Durasi Efektif</span>
                    <div class="flex items-baseline gap-1 mt-0.5 sm:mt-1">
                        <span class="text-lg sm:text-xl font-extrabold text-blue-600" x-text="stats.total_leave_days"></span>
                        <span class="text-xs text-gray-500 font-medium">Hari</span>
                        <span class="text-xs text-gray-300 mx-1">•</span>
                        <span class="text-lg sm:text-xl font-extrabold text-emerald-600" x-text="stats.total_ot_hours"></span>
                        <span class="text-xs text-gray-500 font-medium">Jam</span>
                    </div>
                    <span class="text-[10px] text-gray-400">Cuti / Izin & Lembur</span>
                </div>
                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                    <i class="fa-solid fa-clock-rotate-left text-base sm:text-lg"></i>
                </div>
            </div>
        </div>

        {{-- 2. CONTROL TOOLBAR (NAVIGASI BULAN & FILTER CLIENT-SIDE INSTAN) --}}
        <div class="bg-white rounded-2xl border border-gray-200/90 shadow-xs p-3.5 sm:p-5 space-y-3.5 sm:space-y-4 relative overflow-hidden">

            {{-- LOADING BLOCK OVERLAY PADA FILTER CARD --}}
            <div x-show="isLoading"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-white/85 backdrop-blur-[2px] z-30 flex flex-col items-center justify-center gap-2 rounded-2xl select-none"
                 style="display: none;">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-50 text-blue-600 shadow-sm border border-blue-100">
                    <i class="fa-solid fa-circle-notch fa-spin text-lg"></i>
                </div>
                <span class="text-xs font-bold text-gray-700 tracking-wide flex items-center gap-1.5">
                    Memuat kalender...
                </span>
            </div>

            {{-- Baris 1: Navigasi Bulan (AJAX) + Scope Filter (Client-Side) --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
                <!-- Navigasi Bulan (Responsif & AJAX) -->
                <div class="flex items-center justify-between sm:justify-start gap-1.5 sm:gap-2 w-full sm:w-auto">
                    <!-- Prev Month -->
                    <button type="button"
                        @click="fetchMonth(prevMonth.month, prevMonth.year)"
                        :disabled="isLoading"
                        class="w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center text-gray-600 hover:text-gray-900 transition shadow-2xs shrink-0 disabled:opacity-50"
                        title="Bulan Sebelumnya">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>

                    <!-- Current Month Heading -->
                    <div class="px-3 sm:px-4 py-1.5 bg-gray-50 border border-gray-200 rounded-xl flex items-center gap-1.5 sm:gap-2 flex-1 sm:flex-initial sm:min-w-[190px] justify-center">
                        <i class="fa-regular fa-calendar text-blue-600 text-xs sm:text-sm"></i>
                        <span class="font-extrabold text-gray-900 text-xs sm:text-sm whitespace-nowrap" x-text="currentMonthName"></span>
                    </div>

                    <!-- Next Month -->
                    <button type="button"
                        @click="fetchMonth(nextMonth.month, nextMonth.year)"
                        :disabled="isLoading"
                        class="w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 flex items-center justify-center text-gray-600 hover:text-gray-900 transition shadow-2xs shrink-0 disabled:opacity-50"
                        title="Bulan Berikutnya">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>

                    <!-- Tombol Hari Ini -->
                    <button type="button"
                        @click="fetchMonth({{ now()->month }}, {{ now()->year }})"
                        :disabled="isLoading"
                        class="px-2.5 sm:px-3 py-1.5 rounded-xl border border-blue-200 bg-blue-50/70 text-blue-700 hover:bg-blue-100 text-xs font-bold transition shrink-0 disabled:opacity-50">
                        Hari Ini
                    </button>
                </div>

                <!-- Right Controls: Scope & Status Dropdown (Client-Side Instant!) -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
                    @if(!empty($isApprover))
                        <!-- Scope Selector (Semua Tim vs Pengajuan Saya) - Hanya untuk Approver / Atasan -->
                        <div class="inline-flex rounded-xl p-1 bg-gray-100 border border-gray-200/80 text-xs font-semibold w-full sm:w-auto">
                            <button type="button"
                                @click="scope = 'team'"
                                :class="scope === 'team' ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900'"
                                class="flex-1 sm:flex-initial px-3 py-1.5 rounded-lg transition text-center">
                                <i class="fa-solid fa-users mr-1 text-[11px]"></i>
                                Semua Tim
                            </button>
                            <button type="button"
                                @click="scope = 'my'"
                                :class="scope === 'my' ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900'"
                                class="flex-1 sm:flex-initial px-3 py-1.5 rounded-lg transition text-center">
                                <i class="fa-solid fa-user mr-1 text-[11px]"></i>
                                Pengajuan Saya
                            </button>
                        </div>
                    @endif

                    <!-- Filter Status Component (Client-Side Reaktif via x-model) -->
                    <div class="w-full sm:w-44">
                        <x-select-input
                            name="status_filter"
                            :noSelect2="true"
                            x-model="filterStatus"
                            class="w-full">
                            <option value="all">Semua Status</option>
                            <option value="approved">Disetujui</option>
                            <option value="pending">Menunggu</option>
                            <option value="rejected">Ditolak</option>
                        </x-select-input>
                    </div>
                </div>
            </div>

            {{-- Baris 2: Filter Pills Kategori Pengajuan (Instant Client-Side!) --}}
            <div class="pt-2.5 sm:pt-3 border-t border-gray-100 flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full text-xs" style="-webkit-overflow-scrolling: touch;">
                    <span class="text-gray-400 font-medium text-[11px] mr-1 hidden sm:inline">Kategori:</span>

                    <!-- Semua -->
                    <button type="button"
                        @click="filterType = 'all'"
                        :class="filterType === 'all' ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-xl transition font-medium whitespace-nowrap shrink-0">
                        Semua
                    </button>

                    <!-- Cuti Tahunan -->
                    <button type="button"
                        @click="filterType = 'annual'"
                        :class="filterType === 'annual' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-100'"
                        class="px-3 py-1.5 rounded-xl transition font-medium whitespace-nowrap shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-umbrella-beach text-[11px]"></i>
                        <span>Cuti Tahunan</span>
                    </button>

                    <!-- Cuti Khusus -->
                    <button type="button"
                        @click="filterType = 'special'"
                        :class="filterType === 'special' ? 'bg-purple-600 text-white font-bold shadow-xs' : 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-100'"
                        class="px-3 py-1.5 rounded-xl transition font-medium whitespace-nowrap shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-sparkles text-[11px]"></i>
                        <span>Cuti Khusus</span>
                    </button>

                    <!-- Izin -->
                    <button type="button"
                        @click="filterType = 'permit'"
                        :class="filterType === 'permit' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-100'"
                        class="px-3 py-1.5 rounded-xl transition font-medium whitespace-nowrap shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-file-signature text-[11px]"></i>
                        <span>Izin</span>
                    </button>

                    <!-- Sakit -->
                    <button type="button"
                        @click="filterType = 'sick'"
                        :class="filterType === 'sick' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-100'"
                        class="px-3 py-1.5 rounded-xl transition font-medium whitespace-nowrap shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-notes-medical text-[11px]"></i>
                        <span>Sakit</span>
                    </button>

                    <!-- Lembur -->
                    <button type="button"
                        @click="filterType = 'overtime'"
                        :class="filterType === 'overtime' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-100'"
                        class="px-3 py-1.5 rounded-xl transition font-medium whitespace-nowrap shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-business-time text-[11px]"></i>
                        <span>Lembur</span>
                    </button>
                </div>

                <!-- Petunjuk Legend Ringkas (Desktop) -->
                <div class="hidden xl:flex items-center gap-3 text-[11px] text-gray-500 font-medium">
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Cuti</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Khusus</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Izin</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> Sakit</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Lembur</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Libur</span>
                </div>
            </div>
        </div>

        {{-- MOBILE VIEW SWITCHER (GRID KALENDER VS DAFTAR AGENDA) --}}
        <div class="sm:hidden flex items-center rounded-xl bg-gray-200/80 p-1 border border-gray-200 text-xs font-semibold">
            <button type="button" @click="mobileTab = 'calendar'"
                :class="mobileTab === 'calendar' ? 'bg-white text-blue-600 font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-1.5 rounded-lg text-center transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-calendar-days text-[11px]"></i>
                <span>Grid Kalender</span>
            </button>
            <button type="button" @click="mobileTab = 'agenda'"
                :class="mobileTab === 'agenda' ? 'bg-white text-blue-600 font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                class="flex-1 py-1.5 rounded-lg text-center transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-list-ul text-[11px]"></i>
                <span x-text="'Agenda Bulan Ini (' + stats.total_submissions + ')'"></span>
            </button>
        </div>

        {{-- 3. GRID KALENDER UTAMA --}}
        <div x-show="mobileTab === 'calendar'" class="bg-white rounded-2xl border border-gray-200/90 shadow-sm overflow-hidden relative">

            {{-- LOADING BLOCK OVERLAY PADA GRID KALENDER --}}
            <div x-show="isLoading"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-white/85 backdrop-blur-[2px] z-30 flex flex-col items-center justify-center gap-2.5 rounded-2xl select-none"
                 style="display: none;">
                <div class="flex items-center justify-center w-11 h-11 rounded-full bg-blue-50 text-blue-600 shadow-sm border border-blue-100">
                    <i class="fa-solid fa-circle-notch fa-spin text-xl"></i>
                </div>
                <span class="text-xs font-bold text-gray-700 tracking-wide flex items-center gap-1.5">
                    Memuat data kalender...
                </span>
            </div>

            <!-- Header Hari (Senin s/d Minggu) - Responsif Singkat di Mobile -->
            <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50/80 text-center font-bold text-xs text-gray-600">
                <!-- Desktop Labels -->
                <div class="py-3 px-2 hidden sm:block">Senin</div>
                <div class="py-3 px-2 hidden sm:block">Selasa</div>
                <div class="py-3 px-2 hidden sm:block">Rabu</div>
                <div class="py-3 px-2 hidden sm:block">Kamis</div>
                <div class="py-3 px-2 hidden sm:block">Jumat</div>
                <div class="py-3 px-2 text-rose-600 hidden sm:block">Sabtu</div>
                <div class="py-3 px-2 text-rose-600 hidden sm:block">Minggu</div>

                <!-- Mobile Short Labels -->
                <div class="py-2 px-0.5 sm:hidden text-[11px]">Sen</div>
                <div class="py-2 px-0.5 sm:hidden text-[11px]">Sel</div>
                <div class="py-2 px-0.5 sm:hidden text-[11px]">Rab</div>
                <div class="py-2 px-0.5 sm:hidden text-[11px]">Kam</div>
                <div class="py-2 px-0.5 sm:hidden text-[11px]">Jum</div>
                <div class="py-2 px-0.5 sm:hidden text-[11px] text-rose-600">Sab</div>
                <div class="py-2 px-0.5 sm:hidden text-[11px] text-rose-600">Min</div>
            </div>

            <!-- Grid Kotak Tanggal (Render Reaktif via Template Alpine) -->
            <div class="grid grid-cols-7 divide-x divide-y divide-gray-200">
                <template x-for="day in rawDays" :key="day.date">
                    <div
                        @click="selectDay(day)"
                        class="min-h-[58px] sm:min-h-[135px] p-1 sm:p-2 flex flex-col justify-between transition group relative cursor-pointer sm:cursor-default"
                        :class="[
                            !day.is_current_month ? 'bg-gray-50/60 text-gray-400' : (day.is_weekend ? 'bg-rose-50/15' : 'bg-white'),
                            day.is_today ? 'ring-2 ring-inset ring-blue-500/80 bg-blue-50/20' : 'hover:bg-gray-50/60',
                            selectedDay && selectedDay.date === day.date ? 'ring-2 ring-blue-600 bg-blue-50/30' : ''
                        ]"
                    >
                        {{-- TAMPILAN MOBILE (sm:hidden): Tanggal Bersih + Dot Indikator Warna --}}
                        <div class="sm:hidden flex flex-col items-center justify-center py-1">
                            <span class="inline-flex items-center justify-center font-bold text-xs rounded-full w-6 h-6"
                                :class="[
                                    day.is_today ? 'bg-blue-600 text-white font-extrabold shadow-2xs' : (day.is_weekend || day.holiday ? 'text-rose-600' : (day.is_current_month ? 'text-gray-800' : 'text-gray-400')),
                                    selectedDay && selectedDay.date === day.date && !day.is_today ? 'bg-blue-100 text-blue-700 font-extrabold' : ''
                                ]"
                                x-text="day.day_number">
                            </span>

                            <!-- Indicator Dots (Mobile) -->
                            <div class="flex items-center justify-center gap-0.5 mt-1 h-3 flex-wrap">
                                <template x-if="day.holiday">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500" :title="day.holiday.name"></span>
                                </template>
                                <template x-for="(ev, idx) in getDayEvents(day).slice(0, 3)" :key="ev.id">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="{
                                            'bg-blue-500': ev.type === 'annual',
                                            'bg-purple-500': ev.type === 'special',
                                            'bg-amber-500': ev.type === 'permit',
                                            'bg-rose-500': ev.type === 'sick',
                                            'bg-emerald-500': ev.type === 'overtime'
                                        }"></span>
                                </template>
                                <template x-if="getDayEventCount(day) > 3">
                                    <span class="text-[8px] font-extrabold text-blue-600 leading-none" x-text="'+' + (getDayEventCount(day) - 3)"></span>
                                </template>
                            </div>
                        </div>

                        {{-- TAMPILAN DESKTOP (hidden sm:flex): Full Chips & Tombol --}}
                        <div class="hidden sm:flex flex-col justify-between h-full">
                            <!-- Top Cell Bar: Tanggal & Badge Hari Ini / Libur -->
                            <div class="flex items-start justify-between gap-1 mb-1.5">
                                <div class="flex items-center gap-1">
                                    <span class="inline-flex items-center justify-center font-bold text-xs sm:text-sm rounded-lg"
                                        :class="day.is_today ? 'w-6 h-6 bg-blue-600 text-white font-extrabold shadow-2xs' : (day.is_weekend || day.holiday ? 'text-rose-600' : (day.is_current_month ? 'text-gray-800' : 'text-gray-400'))"
                                        x-text="day.day_number">
                                    </span>

                                    <template x-if="day.is_today">
                                        <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded bg-blue-100 text-blue-700 tracking-wider">
                                            Hari Ini
                                        </span>
                                    </template>
                                </div>

                                <template x-if="getDayEventCount(day) > 0">
                                    <button type="button"
                                        @click.stop="openDayList(day)"
                                        class="text-[10px] font-bold text-gray-400 hover:text-blue-600 hover:bg-blue-50 px-1 rounded transition"
                                        title="Lihat semua pengajuan pada tanggal ini"
                                        x-text="getDayEventCount(day) + ' Dokumen'">
                                    </button>
                                </template>
                            </div>

                            <!-- Banner Hari Libur Nasional (jika ada) -->
                            <template x-if="day.holiday">
                                <div class="mb-1.5 px-2 py-0.5 rounded-md bg-rose-50 border border-rose-200 text-rose-700 text-[10px] font-bold truncate flex items-center gap-1"
                                    :title="day.holiday.name">
                                    <i class="fa-solid fa-flag text-[9px] shrink-0 text-rose-500"></i>
                                    <span class="truncate" x-text="day.holiday.name"></span>
                                </div>
                            </template>

                            <!-- Daftar Chips Pengajuan Pada Hari Tersebut -->
                            <div class="space-y-1 flex-1">
                                <template x-for="(ev, idx) in getDayEvents(day).slice(0, 3)" :key="ev.id">
                                    <button type="button"
                                        @click.stop="openEvent(ev)"
                                        class="w-full text-left px-2 py-1 rounded-lg border text-[11px] font-medium transition flex items-center justify-between gap-1 shadow-2xs hover:shadow-xs"
                                        :class="ev.type_badge_bg"
                                        :title="'Klik untuk melihat rincian pengajuan ' + ev.request_number">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="ev.status_dot"></span>
                                            <i class="fa-solid text-[10px] shrink-0 opacity-80" :class="ev.type_icon"></i>
                                            <span class="truncate font-semibold">
                                                <template x-if="scope === 'team'">
                                                    <span x-text="ev.emp_name.split(' ')[0] + ': '"></span>
                                                </template>
                                                <span x-text="ev.type_label"></span>
                                            </span>
                                        </div>
                                        <span class="text-[9px] font-mono shrink-0 opacity-70" x-text="ev.is_mine ? 'Saya' : ''"></span>
                                    </button>
                                </template>

                                <!-- Chip '+X Lainnya' jika lebih dari 3 pengajuan -->
                                <template x-if="getDayEventCount(day) > 3">
                                    <button type="button"
                                        @click.stop="openDayList(day)"
                                        class="w-full text-center py-0.5 text-[10px] font-bold text-blue-600 hover:text-blue-800 bg-blue-50/70 hover:bg-blue-100 rounded-md transition border border-blue-200/60"
                                        x-text="'+' + (getDayEventCount(day) - 3) + ' lainnya...'">
                                    </button>
                                </template>
                            </div>

                            <div class="h-0.5"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>



        {{-- AGENDA VIEW BULANAN (KHUSUS MOBILE: TAB 'AGENDA') --}}
        <div class="sm:hidden space-y-2.5" x-show="mobileTab === 'agenda'">
            <template x-if="monthAgendaEvents.length > 0">
                <div class="space-y-2.5">
                    <template x-for="ev in monthAgendaEvents" :key="ev.id">
                        <div @click="openEvent(ev)"
                            class="bg-white p-3.5 rounded-2xl border border-gray-200/90 shadow-2xs space-y-2 cursor-pointer active:scale-[0.99] transition hover:border-blue-300">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="ev.status_dot"></span>
                                    <span class="font-extrabold text-xs text-gray-900" x-text="ev.type_label"></span>
                                    <span class="text-[10px] font-mono text-gray-400" x-text="ev.request_number"></span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0" :class="ev.status_badge" x-text="ev.status_label"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-700">
                                <span class="font-semibold text-gray-900" x-text="ev.emp_name"></span>
                                <span class="font-bold text-blue-600" x-text="ev.duration_display"></span>
                            </div>
                            <div class="text-[11px] text-gray-500 flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar text-gray-400 text-[10px]"></i>
                                <span x-text="ev.date_range_display"></span>
                            </div>
                            <p class="text-[11px] text-gray-500 line-clamp-2 italic" x-text="ev.reason"></p>
                            <div class="pt-1.5 border-t border-gray-100 flex items-center justify-between text-[11px] text-blue-600 font-semibold">
                                <span>Lihat Rincian</span>
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="monthAgendaEvents.length === 0">
                <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center text-xs text-gray-400">
                    <i class="fa-regular fa-calendar-xmark text-3xl mb-2 text-gray-300 block"></i>
                    Tidak ada pengajuan pada bulan ini sesuai filter yang dipilih.
                </div>
            </template>
        </div>

        {{-- 4. MODAL DETAIL PENGAJUAN INTERAKTIF --}}
        <div
            x-show="showDetailModal"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
            @keydown.escape.window="closeDetail()"
        >
            <!-- Backdrop Blur -->
            <div
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="closeDetail()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <!-- Modal Dialog Content -->
            <div
                x-show="showDetailModal"
                class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden z-10 my-4 sm:my-8 transform transition-all border border-gray-100"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                @click.stop
            >
                <template x-if="activeEvent">
                    <div>
                        <!-- Accent Top Bar Gradient -->
                        <div class="h-2 bg-gradient-to-r" :class="activeEvent.type_accent"></div>

                        <!-- Header Modal -->
                        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b border-gray-100 flex items-center justify-between gap-3 bg-gray-50/60">
                            <div class="flex items-center gap-2.5 sm:gap-3 truncate">
                                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl flex items-center justify-center shrink-0 border border-gray-200 bg-white shadow-2xs">
                                    <i class="fa-solid text-lg sm:text-xl text-blue-600" :class="activeEvent.type_icon"></i>
                                </div>
                                <div class="truncate">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-base sm:text-lg font-extrabold text-gray-900" x-text="activeEvent.type_label"></h3>
                                        <span class="px-2 sm:px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold border" :class="activeEvent.status_badge">
                                            <i class="fa-solid mr-1 text-[9px] sm:text-[10px]" :class="activeEvent.status_icon"></i>
                                            <span x-text="activeEvent.status_label"></span>
                                        </span>
                                    </div>
                                    <p class="text-[11px] sm:text-xs text-gray-400 font-mono mt-0.5 truncate" x-text="'No. Pengajuan: ' + activeEvent.request_number"></p>
                                </div>
                            </div>

                            <button type="button" @click="closeDetail()"
                                class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl border border-gray-200 bg-white hover:bg-gray-100 text-gray-400 hover:text-gray-600 flex items-center justify-center transition shadow-2xs shrink-0">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </button>
                        </div>

                        <!-- Body Modal (Scrollable) -->
                        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6 max-h-[calc(85vh-160px)] overflow-y-auto">

                            <!-- Data Pemohon -->
                            <div class="p-3.5 sm:p-4 rounded-2xl bg-gray-50/80 border border-gray-200/80 grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3 text-xs">
                                <div>
                                    <span class="block text-gray-400 font-semibold mb-0.5">Nama Pemohon</span>
                                    <span class="font-bold text-gray-900 text-sm" x-text="activeEvent.emp_name"></span>
                                </div>
                                <div>
                                    <span class="block text-gray-400 font-semibold mb-0.5">Jabatan</span>
                                    <span class="font-semibold text-gray-800" x-text="activeEvent.emp_jabatan"></span>
                                </div>
                                <div>
                                    <span class="block text-gray-400 font-semibold mb-0.5">NIP / ID</span>
                                    <span class="font-mono font-bold text-gray-700" x-text="activeEvent.emp_nip"></span>
                                </div>
                            </div>

                            <!-- Data Jadwal & Durasi -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                <div class="p-3.5 sm:p-4 rounded-2xl border border-gray-200/80 space-y-1">
                                    <span class="text-xs text-gray-400 font-semibold flex items-center gap-1.5">
                                        <i class="fa-regular fa-calendar-check text-blue-500"></i>
                                        Rentang Waktu
                                    </span>
                                    <div class="font-bold text-gray-900 text-sm mt-1" x-text="activeEvent.date_range_display"></div>
                                    <div class="text-xs text-gray-500" x-text="'Tipe Hari: ' + activeEvent.day_type_label"></div>
                                </div>

                                <div class="p-3.5 sm:p-4 rounded-2xl border border-gray-200/80 space-y-1">
                                    <span class="text-xs text-gray-400 font-semibold flex items-center gap-1.5">
                                        <i class="fa-regular fa-hourglass-half text-emerald-500"></i>
                                        Total Durasi
                                    </span>
                                    <div class="font-extrabold text-blue-600 text-sm mt-1" x-text="activeEvent.duration_display"></div>
                                    <template x-if="activeEvent.special_type_name">
                                        <div class="text-xs text-purple-600 font-semibold" x-text="'Kategori: ' + activeEvent.special_type_name"></div>
                                    </template>
                                </div>
                            </div>

                            <!-- Keperluan / Alasan Lengkap -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                    Keperluan / Alasan Pengajuan
                                </label>
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-gray-50 border border-gray-200 text-sm text-gray-800 whitespace-pre-line leading-relaxed"
                                    x-text="activeEvent.reason"></div>
                            </div>

                            <!-- Uraian Pekerjaan Lembur (jika ada) -->
                            <template x-if="activeEvent.overtime_task">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                        Rincian Tugas yang Dikerjakan (Lembur)
                                    </label>
                                    <div class="p-3.5 sm:p-4 rounded-2xl bg-emerald-50/50 border border-emerald-200/80 text-sm text-emerald-900 whitespace-pre-line leading-relaxed"
                                        x-text="activeEvent.overtime_task"></div>
                                </div>
                            </template>

                            <!-- Alasan Penolakan (jika ada) -->
                            <template x-if="activeEvent.rejection_reason">
                                <div class="p-3.5 sm:p-4 rounded-2xl bg-rose-50 border border-rose-200">
                                    <div class="flex items-center gap-2 text-rose-800 font-bold text-xs mb-1">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                        <span>Alasan Penolakan dari Approver:</span>
                                    </div>
                                    <p class="text-xs text-rose-700" x-text="activeEvent.rejection_reason"></p>
                                </div>
                            </template>

                            <!-- Alur Persetujuan (Approval Multi-Level Timeline) -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">
                                    Alur & Riwayat Persetujuan
                                </label>

                                <div class="space-y-2.5">
                                    <template x-if="activeEvent.approvals && activeEvent.approvals.length > 0">
                                        <div class="space-y-2.5">
                                            <template x-for="(app, idx) in activeEvent.approvals" :key="idx">
                                                <div class="p-3 sm:p-3.5 rounded-2xl border flex items-center justify-between gap-3 text-xs"
                                                    :class="app.status === 'approved' ? 'bg-emerald-50/40 border-emerald-200/80' : (app.status === 'rejected' ? 'bg-rose-50/40 border-rose-200/80' : 'bg-gray-50 border-gray-200')">
                                                    <div class="flex items-center gap-2.5 sm:gap-3">
                                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0"
                                                            :class="app.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : (app.status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')">
                                                            <span x-text="'L' + app.level"></span>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold text-gray-900" x-text="app.jabatan"></div>
                                                            <div class="text-[11px] text-gray-500 mt-0.5" x-text="'Approver: ' + app.approver_name"></div>
                                                            <template x-if="app.notes && app.notes !== '-'">
                                                                <div class="text-[11px] italic text-gray-600 mt-1" x-text="'Catatan: ' + app.notes"></div>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <div class="text-right shrink-0">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md font-bold text-[10px]"
                                                            :class="app.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (app.status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')">
                                                            <span x-text="app.status === 'approved' ? 'Disetujui' : (app.status === 'rejected' ? 'Ditolak' : 'Menunggu')"></span>
                                                        </span>
                                                        <template x-if="app.action_at">
                                                            <div class="text-[10px] text-gray-400 mt-1" x-text="app.action_at"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <template x-if="!activeEvent.approvals || activeEvent.approvals.length === 0">
                                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200 text-xs text-gray-500 text-center">
                                            Tidak ada alur persetujuan bertingkat (otomatis selesai).
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Lampiran Dokumen Pendukung -->
                            <template x-if="activeEvent.files && activeEvent.files.length > 0">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                        Lampiran Dokumen
                                    </label>
                                    <div class="space-y-2">
                                        <template x-for="f in activeEvent.files" :key="f.id">
                                            <a :href="f.url" target="_blank"
                                                class="p-3 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50/40 flex items-center justify-between text-xs transition group">
                                                <div class="flex items-center gap-2.5 truncate">
                                                    <i class="fa-solid fa-paperclip text-gray-400 group-hover:text-blue-600"></i>
                                                    <span class="font-medium text-gray-800 group-hover:text-blue-600 truncate" x-text="f.file_name"></span>
                                                </div>
                                                <span class="text-[11px] text-blue-600 font-semibold group-hover:underline flex items-center gap-1 shrink-0">
                                                    <span>Unduh / Buka</span>
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                                </span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>

                        </div>

                        <!-- Footer Modal Actions -->
                        <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                            <button type="button" @click="closeDetail()"
                                class="px-4 py-2.5 rounded-xl border border-gray-300 text-xs font-bold text-gray-700 bg-white hover:bg-gray-100 transition shadow-2xs">
                                Tutup
                            </button>

                            <a :href="activeEvent.route_show"
                                class="inline-flex items-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-200 transition">
                                <span>Buka Halaman Lengkap</span>
                                <i class="fa-solid fa-arrow-right text-[11px]"></i>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- 5. MODAL POP-UP AGENDA TANGGAL (RESPONSIF MOBILE & DESKTOP) --}}
        <div
            x-show="showDayModal"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
            @keydown.escape.window="closeDayList()"
        >
            <div
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                @click="closeDayList()"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <div
                class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden z-10 my-4 sm:my-8 transform transition-all border border-gray-100"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                @click.stop
            >
                <!-- Modal Header -->
                <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/80">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                            <i class="fa-regular fa-calendar-check text-base"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900" x-text="activeDayTitle"></h3>
                            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Daftar pengajuan kerja pada tanggal ini</p>
                        </div>
                    </div>
                    <button type="button" @click="closeDayList()"
                        class="w-8 h-8 rounded-xl border border-gray-200 bg-white text-gray-400 hover:text-gray-600 flex items-center justify-center transition shrink-0">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <!-- Banner Hari Libur (jika ada) -->
                <template x-if="activeDayHoliday">
                    <div class="mx-5 sm:mx-6 mt-3 sm:mt-4 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-flag text-rose-500 shrink-0"></i>
                        <span x-text="'Hari Libur: ' + activeDayHoliday.name"></span>
                    </div>
                </template>

                <!-- List Pengajuan -->
                <div class="p-5 sm:p-6 space-y-3 max-h-[60vh] overflow-y-auto">
                    <template x-if="activeDayEvents && activeDayEvents.length > 0">
                        <div class="space-y-2.5">
                            <template x-for="ev in activeDayEvents" :key="ev.id">
                                <div
                                    @click="selectEventFromDay(ev)"
                                    class="p-4 rounded-2xl border border-gray-200 hover:border-blue-400 hover:bg-blue-50/30 cursor-pointer transition space-y-2 group active:scale-[0.99] shadow-2xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full shrink-0" :class="ev.status_dot"></span>
                                            <span class="font-extrabold text-sm text-gray-900 group-hover:text-blue-600" x-text="ev.type_label"></span>
                                            <span class="text-xs font-mono text-gray-400" x-text="'(' + ev.request_number + ')'"></span>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border shrink-0" :class="ev.status_badge" x-text="ev.status_label"></span>
                                    </div>

                                    <div class="flex items-center justify-between text-xs text-gray-700">
                                        <span class="font-semibold text-gray-900" x-text="ev.emp_name"></span>
                                        <span class="font-bold text-blue-600" x-text="ev.duration_display"></span>
                                    </div>

                                    <p class="text-xs text-gray-500 line-clamp-1 italic" x-text="ev.reason"></p>

                                    <div class="pt-1.5 border-t border-gray-100 flex items-center justify-between text-[11px] text-blue-600 font-semibold">
                                        <span>Ketuk untuk rincian lengkap</span>
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!activeDayEvents || activeDayEvents.length === 0">
                        <div class="py-8 text-center text-xs text-gray-400">
                            <i class="fa-regular fa-calendar-xmark text-3xl mb-2 text-gray-300 block"></i>
                            Tidak ada pengajuan pada tanggal ini.
                        </div>
                    </template>
                </div>

                <div class="px-5 sm:px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-xs text-gray-500" x-text="(activeDayEvents ? activeDayEvents.length : 0) + ' Pengajuan'"></span>
                    <button type="button" @click="closeDayList()"
                        class="px-4 py-2 rounded-xl border border-gray-300 text-xs font-bold text-gray-700 bg-white hover:bg-gray-100 transition shadow-2xs">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
