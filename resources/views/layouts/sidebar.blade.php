<aside
    class="w-64 bg-slate-900 text-white h-screen max-h-screen flex flex-col flex-shrink-0 overflow-hidden hidden sm:flex">
    {{-- Header Logo --}}
    <div class="h-16 flex items-center px-5 border-b border-gray-800 flex-shrink-0">
        <x-application-logo class="block h-8 w-auto fill-current text-white mr-3" />
        <span class="text-lg font-extrabold tracking-tight">HRD System</span>
    </div>

    {{-- Scrollable Menu Container --}}
    <div class="flex-1 min-h-0 px-3 py-3 overflow-y-auto overflow-x-hidden sidebar-scroll">
        <ul class="space-y-1 font-medium text-[13.5px] pb-6">
            @if(auth()->user()?->canAccessFinanceBackoffice())
                {{-- SECTION: MASTER DATA --}}
                <li class="pt-1 pb-1 px-2.5">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Master Data</span>
                </li>

                {{-- Employees Dropdown --}}
                <li
                    x-data="{ open: {{ request()->routeIs('employee.*') || request()->routeIs('employee-attendance.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') || request()->routeIs('payroll-results.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                        class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('employee.*') || request()->routeIs('employee-attendance.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') || request()->routeIs('payroll-results.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                        <i
                            class="fa-solid fa-users w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('employee.*') || request()->routeIs('employee-attendance.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') || request()->routeIs('payroll-results.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                        <span class="flex-1 ms-3 text-left whitespace-nowrap">Employees</span>
                        <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150"
                            :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                        <li>
                            <a href="{{ route('employee.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('employee.*') && !request()->routeIs('employee-attendance.*') && !request()->routeIs('emp-allowance.*') && !request()->routeIs('emp-loans.*') && !request()->routeIs('emp-bpjs.*') && !request()->routeIs('potongan-karyawan.*') && !request()->routeIs('payroll-results.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-user-group text-xs w-4 text-center {{ request()->routeIs('employee.*') && !request()->routeIs('employee-attendance.*') && !request()->routeIs('emp-allowance.*') && !request()->routeIs('emp-loans.*') && !request()->routeIs('emp-bpjs.*') && !request()->routeIs('potongan-karyawan.*') && !request()->routeIs('payroll-results.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Data Karyawan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('employee-attendance.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('employee-attendance.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-clipboard-user text-xs w-4 text-center {{ request()->routeIs('employee-attendance.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Presensi Karyawan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('emp-allowance.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('emp-allowance.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-hand-holding-dollar text-xs w-4 text-center {{ request()->routeIs('emp-allowance.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Tunjangan Karyawan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('emp-loans.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('emp-loans.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-money-bill-transfer text-xs w-4 text-center {{ request()->routeIs('emp-loans.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Pinjaman Karyawan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('potongan-karyawan.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('potongan-karyawan.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-scissors text-xs w-4 text-center {{ request()->routeIs('potongan-karyawan.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Potongan Karyawan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('payroll-results.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-results.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-calculator text-xs w-4 text-center {{ request()->routeIs('payroll-results.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Payroll Karyawan</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Setting Dropdown (Master Data & Operasional) --}}
                <li
                    x-data="{ open: {{ request()->routeIs('employee-jabatan.*') || request()->routeIs('approval-settings.*') || request()->routeIs('holidays.*') || request()->routeIs('work-schedules.*') || request()->routeIs('emp-schedules.*') || request()->routeIs('special-leave-types.*') || request()->routeIs('permit-types.*') || request()->routeIs('attendance-settings.*') || request()->routeIs('leave-balances.*') || request()->routeIs('payroll-settings.*') || request()->routeIs('payroll-components.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                        class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('employee-jabatan.*') || request()->routeIs('approval-settings.*') || request()->routeIs('holidays.*') || request()->routeIs('work-schedules.*') || request()->routeIs('emp-schedules.*') || request()->routeIs('special-leave-types.*') || request()->routeIs('permit-types.*') || request()->routeIs('attendance-settings.*') || request()->routeIs('leave-balances.*') || request()->routeIs('payroll-settings.*') || request()->routeIs('payroll-components.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                        <i
                            class="fa-solid fa-sliders w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('employee-jabatan.*') || request()->routeIs('approval-settings.*') || request()->routeIs('holidays.*') || request()->routeIs('work-schedules.*') || request()->routeIs('emp-schedules.*') || request()->routeIs('special-leave-types.*') || request()->routeIs('permit-types.*') || request()->routeIs('attendance-settings.*') || request()->routeIs('leave-balances.*') || request()->routeIs('payroll-settings.*') || request()->routeIs('payroll-components.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                        <span class="flex-1 ms-3 text-left whitespace-nowrap">Setting</span>
                        <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150"
                            :class="{ 'rotate-180': open }"></i>
                    </button>
                    <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                        {{-- Sub-Kategori: Struktur & Approval --}}
                        <li class="pt-2 pb-1 px-4 flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Struktur &
                                Approval</span>
                        </li>
                        <li>
                            <a href="{{ route('employee-jabatan.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('employee-jabatan.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-sitemap text-xs w-4 text-center {{ request()->routeIs('employee-jabatan.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Jabatan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('approval-settings.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('approval-settings.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-user-check text-xs w-4 text-center {{ request()->routeIs('approval-settings.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Approver</span>
                            </a>
                        </li>

                        {{-- Sub-Kategori: Waktu & Jadwal Kerja --}}
                        <li class="pt-3 pb-1 px-4 border-t border-gray-800/60 mt-1.5 flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Waktu & Jadwal
                                Kerja</span>
                        </li>
                        <li>
                            <a href="{{ route('holidays.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('holidays.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-calendar-days text-xs w-4 text-center {{ request()->routeIs('holidays.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Hari Libur Nasional</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('work-schedules.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('work-schedules.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-business-time text-xs w-4 text-center {{ request()->routeIs('work-schedules.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Jadwal & Jam Kerja</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('emp-schedules.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('emp-schedules.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-user-clock text-xs w-4 text-center {{ request()->routeIs('emp-schedules.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Penugasan Shift</span>
                            </a>
                        </li>

                        {{-- Sub-Kategori: Kebijakan Cuti & Izin --}}
                        <li class="pt-3 pb-1 px-4 border-t border-gray-800/60 mt-1.5 flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kebijakan Cuti & Izin</span>
                        </li>
                        <li>
                            <a href="{{ route('special-leave-types.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('special-leave-types.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-layer-group text-xs w-4 text-center {{ request()->routeIs('special-leave-types.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Tipe Cuti Khusus</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('permit-types.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('permit-types.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-clipboard-list text-xs w-4 text-center {{ request()->routeIs('permit-types.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Tipe Izin</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('attendance-settings.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('attendance-settings.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i class="fa-solid fa-location-dot text-xs w-4 text-center"></i>
                                <span>Lokasi & Radius Presensi</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('leave-balances.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('leave-balances.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-wallet text-xs w-4 text-center {{ request()->routeIs('leave-balances.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Saldo Kuota Cuti</span>
                            </a>
                        </li>

                        {{-- Sub-Kategori: Penggajian & Payroll --}}
                        <li class="pt-3 pb-1 px-4 border-t border-gray-800/60 mt-1.5 flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Penggajian &amp; Payroll</span>
                        </li>
                        <li>
                            <a href="{{ route('payroll-settings.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-settings.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-sliders text-xs w-4 text-center {{ request()->routeIs('payroll-settings.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Payroll Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('payroll-components.index') }}"
                                class="flex items-center gap-2.5 w-full px-3 py-1.5 pl-5 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-components.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                                <i
                                    class="fa-solid fa-cubes text-xs w-4 text-center {{ request()->routeIs('payroll-components.*') ? 'text-blue-400' : 'text-slate-500 group-hover:text-slate-300' }}"></i>
                                <span>Components</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            {{-- SECTION: ESS --}}
            <li class="pt-2.5 pb-1 px-2.5">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">ESS</span>
            </li>

            {{-- Timeline --}}
            <li>
                <a href="{{ route('timeline.index') }}"
                    class="flex items-center px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('timeline.*') ? 'bg-blue-600 text-white font-bold shadow-xs' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i
                        class="fa-solid fa-newspaper w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('timeline.*') ? 'text-white' : 'text-blue-400 group-hover:text-white' }}"></i>
                    <span class="ms-3 flex-1">Timeline</span>
                </a>
            </li>

            {{-- Profile ESS --}}
            <li>
                <a href="{{ route('ess.profile') }}"
                    class="flex items-center px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('ess.profile') ? 'bg-blue-600 text-white font-bold shadow-xs' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i
                        class="fa-solid fa-id-badge w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('ess.profile') ? 'text-white' : 'text-blue-400 group-hover:text-white' }}"></i>
                    <span class="ms-3 flex-1">Profile</span>
                </a>
            </li>

            {{-- Kalender ESS --}}
            <li>
                <a href="{{ route('calendar.index') }}"
                    class="flex items-center px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('calendar.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i
                        class="fa-solid fa-calendar-days w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('calendar.*') ? 'text-blue-400' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="ms-3 flex-1">Kalender ESS</span>
                </a>
            </li>

            {{-- Presensi Dropdown --}}
            <li x-data="{ open: {{ request()->routeIs('attendance.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('attendance.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i
                        class="fa-solid fa-fingerprint w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('attendance.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Presensi</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150"
                        :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                    <li>
                        <a href="{{ route('attendance.check-in') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('attendance.check-in') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Presensi Masuk
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('attendance.check-out') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('attendance.check-out') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Presensi Keluar
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('attendance.history') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('attendance.history') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Riwayat Presensi
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Approval Inbox Terpusat --}}
            <li>
                <a href="{{ route('approval-inbox.index') }}"
                    class="flex items-center px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('approval-inbox.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i
                        class="fa-solid fa-inbox w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('approval-inbox.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="ms-3 flex-1">Approval</span>
                    @php
                        $inboxBadgeCount = 0;
                        try {
                            $inboxUser = auth()->user();
                            $inboxEmp = $inboxUser?->employee;
                            if ($inboxEmp) {
                                $inboxMorphClasses = [\App\Models\LeaveRequest::class, \App\Models\OvertimeRequest::class];
                                $inboxQuery = \App\Models\DocumentApproval::actionablePending();
                                if (\Illuminate\Support\Facades\Schema::hasColumn('document_approvals', 'approver_emp_id')) {
                                    $inboxQuery->where(function ($q) use ($inboxEmp) {
                                        $q->where('approver_emp_id', $inboxEmp->id)
                                          ->orWhere(function ($sub) use ($inboxEmp) {
                                              $sub->whereNull('approver_emp_id')
                                                  ->where('jabatan_id', $inboxEmp->jabatan_id);
                                          });
                                    });
                                } else {
                                    $inboxQuery->where('jabatan_id', $inboxEmp->jabatan_id);
                                }
                                $inboxBadgeCount = $inboxQuery->where(function ($q) use ($inboxEmp, $inboxMorphClasses) {
                                        foreach ($inboxMorphClasses as $class) {
                                            $q->orWhere(function ($sub) use ($inboxEmp, $class) {
                                                $sub->where('approvable_type', $class)
                                                    ->whereHasMorph('approvable', [$class], fn($i) => $i->where('emp_id', '!=', $inboxEmp->id));
                                            });
                                        }
                                    })->count();
                            }
                        } catch (\Exception $e) {
                        }
                    @endphp
                    @if($inboxBadgeCount > 0)
                        <span
                            class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full bg-amber-500 text-white ms-1">
                            {{ $inboxBadgeCount > 99 ? '99+' : $inboxBadgeCount }}
                        </span>
                    @endif
                </a>
            </li>

            {{-- Izin & Cuti Dropdown --}}
            <li
                x-data="{ open: {{ request()->routeIs('leave.*') || request()->routeIs('special-leave.*') || request()->routeIs('permit.*') || request()->routeIs('sick.*') || request()->routeIs('overtime.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('leave.*') || request()->routeIs('special-leave.*') || request()->routeIs('permit.*') || request()->routeIs('sick.*') || request()->routeIs('overtime.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i
                        class="fa-solid fa-umbrella-beach w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('leave.*') || request()->routeIs('special-leave.*') || request()->routeIs('permit.*') || request()->routeIs('sick.*') || request()->routeIs('overtime.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Izin & Cuti</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150"
                        :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                    <li>
                        <a href="{{ route('leave.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('leave.*') || request()->routeIs('special-leave.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Cuti
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('permit.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('permit.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Izin
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sick.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('sick.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Sakit
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('overtime.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('overtime.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Lembur
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Slip Gaji ESS --}}
            <li>
                <a href="{{ route('ess.payslip.index') }}"
                    class="flex items-center px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('ess.payslip.*') ? 'bg-blue-600 text-white font-bold shadow-xs' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i
                        class="fa-solid fa-receipt w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('ess.payslip.*') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }}"></i>
                    <span class="ms-3 flex-1">Slip Gaji</span>
                </a>
            </li>
        </ul>
    </div>

    {{-- Footer Sidebar: Ubah Password (Kiri) & Logout (Kanan) --}}
    <div class="p-3 border-t border-gray-800 bg-slate-950/80 flex items-center gap-2 shrink-0">
        <a href="{{ route('ess.password.edit') }}"
            class="flex-1 inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white bg-slate-800/80 hover:bg-slate-800 border border-slate-700/60 transition {{ request()->routeIs('ess.password.*') ? 'bg-amber-600/30 text-amber-300 border-amber-500/60 font-bold' : '' }}"
            title="Ubah Password Akun">
            <i class="fa-solid fa-key text-amber-400 text-xs"></i>
            <span>Password</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="flex-1">
            @csrf
            <button type="submit"
                class="w-full inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-semibold text-rose-300 hover:text-white bg-rose-950/40 hover:bg-rose-600 border border-rose-800/40 hover:border-rose-600 transition cursor-pointer"
                title="Keluar dari Aplikasi">
                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
