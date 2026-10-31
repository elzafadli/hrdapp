<aside class="w-64 bg-slate-900 text-white h-screen max-h-screen flex flex-col flex-shrink-0 overflow-hidden hidden sm:block">
    {{-- Header Logo --}}
    <div class="h-16 flex items-center px-5 border-b border-gray-800 flex-shrink-0">
        <x-application-logo class="block h-8 w-auto fill-current text-white mr-3" />
        <span class="text-lg font-extrabold tracking-tight">HRD System</span>
    </div>

    {{-- Scrollable Menu Container --}}
    <div class="flex-1 min-h-0 px-3 py-3 overflow-y-auto overflow-x-hidden sidebar-scroll">
        <ul class="space-y-1 font-medium text-[13.5px]">
            {{-- SECTION: MASTER DATA --}}
            <li class="pt-1 pb-1 px-2.5">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Master Data</span>
            </li>

            {{-- Employees Dropdown --}}
            <li x-data="{ open: {{ request()->routeIs('employee.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('employee.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i class="fa-solid fa-users w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('employee.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Employees</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150" :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                    <li>
                        <a href="{{ route('employee.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('employee.*') && !request()->routeIs('emp-allowance.*') && !request()->routeIs('emp-loans.*') && !request()->routeIs('emp-bpjs.*') && !request()->routeIs('potongan-karyawan.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Data Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('emp-allowance.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('emp-allowance.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Tunjangan Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('emp-loans.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('emp-loans.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Pinjaman Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('potongan-karyawan.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('potongan-karyawan.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Potongan Karyawan
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Setting Dropdown (Jabatan & Approver) --}}
            <li x-data="{ open: {{ request()->routeIs('employee-jabatan.*') || request()->routeIs('approval-settings.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('employee-jabatan.*') || request()->routeIs('approval-settings.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i class="fa-solid fa-sliders w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('employee-jabatan.*') || request()->routeIs('approval-settings.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Setting</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150" :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                    <li>
                        <a href="{{ route('employee-jabatan.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('employee-jabatan.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Jabatan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('approval-settings.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('approval-settings.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Approver
                        </a>
                    </li>
                </ul>
            </li>

            {{-- SECTION: PAYROLL --}}
            <li class="pt-2.5 pb-1 px-2.5">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Payroll</span>
            </li>

            {{-- Setting Dropdown --}}
            <li x-data="{ open: {{ request()->routeIs('payroll-settings.*') || request()->routeIs('payroll-components.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('payroll-settings.*') || request()->routeIs('payroll-components.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i class="fa-solid fa-gears w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('payroll-settings.*') || request()->routeIs('payroll-components.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Setting</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150" :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                    <li>
                        <a href="{{ route('payroll-settings.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-settings.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Payroll Settings
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payroll-components.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-components.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Components
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Process Dropdown --}}
            <li x-data="{ open: {{ request()->routeIs('payroll-results.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('payroll-results.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i class="fa-solid fa-calculator w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('payroll-results.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Process</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150" :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                    <li>
                        <a href="{{ route('payroll-results.create') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-results.create') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Payroll Process
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payroll-results.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-results.index') || request()->routeIs('payroll-results.slip') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Payroll Results
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payroll-results.deductions') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('payroll-results.deductions*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Payroll BPJS
                        </a>
                    </li>
                </ul>
            </li>

            {{-- SECTION: ESS --}}
            <li class="pt-2.5 pb-1 px-2.5">
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">ESS</span>
            </li>

            {{-- Presensi Dropdown --}}
            <li x-data="{ open: {{ request()->routeIs('attendance.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('attendance.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i class="fa-solid fa-fingerprint w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('attendance.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Presensi</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150" :class="{ 'rotate-180': open }"></i>
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
            @php
                $showApprovalMenu = false;
                try {
                    $sidebarUser = auth()->user();
                    $sidebarEmp  = $sidebarUser?->employee;
                    if ($sidebarEmp?->jabatan_id) {
                        $showApprovalMenu = \App\Models\ApprovalSetting::isJabatanAnApprover((int) $sidebarEmp->jabatan_id);
                    }
                } catch (\Exception $e) { $showApprovalMenu = false; }
            @endphp
            @if($showApprovalMenu)
            <li>
                <a href="{{ route('approval-inbox.index') }}"
                    class="flex items-center px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('approval-inbox.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i class="fa-solid fa-inbox w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('approval-inbox.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="ms-3 flex-1">Approval</span>
                    @php
                        $inboxBadgeCount = 0;
                        try {
                            $inboxUser = auth()->user();
                            $inboxEmp  = $inboxUser?->employee;
                            if ($inboxEmp) {
                                $inboxMorphClasses = [\App\Models\LeaveRequest::class, \App\Models\OvertimeRequest::class];
                                $inboxBadgeCount = \App\Models\DocumentApproval::where('status', 'pending')
                                    ->where('jabatan_id', $inboxEmp->jabatan_id)
                                    ->where(function ($q) use ($inboxEmp, $inboxMorphClasses) {
                                        foreach ($inboxMorphClasses as $class) {
                                            $q->orWhere(function ($sub) use ($inboxEmp, $class) {
                                                $sub->where('approvable_type', $class)
                                                    ->whereHasMorph('approvable', [$class], fn ($i) => $i->where('emp_id', '!=', $inboxEmp->id));
                                            });
                                        }
                                    })->count();
                            }
                        } catch (\Exception $e) {}
                    @endphp
                    @if($inboxBadgeCount > 0)
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full bg-amber-500 text-white ms-1">
                            {{ $inboxBadgeCount > 99 ? '99+' : $inboxBadgeCount }}
                        </span>
                    @endif
                </a>
            </li>
            @endif

            {{-- Izin & Cuti Dropdown --}}
            <li x-data="{ open: {{ request()->routeIs('leave.*') || request()->routeIs('special-leave.*') || request()->routeIs('permit.*') || request()->routeIs('sick.*') || request()->routeIs('overtime.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full px-3 py-2 rounded-xl group transition duration-75 {{ request()->routeIs('leave.*') || request()->routeIs('special-leave.*') || request()->routeIs('permit.*') || request()->routeIs('sick.*') || request()->routeIs('overtime.*') ? 'bg-gray-800 text-white font-bold' : 'text-slate-300 hover:bg-gray-800/80 hover:text-white' }}">
                    <i class="fa-solid fa-umbrella-beach w-4 h-4 text-sm transition duration-75 {{ request()->routeIs('leave.*') || request()->routeIs('special-leave.*') || request()->routeIs('permit.*') || request()->routeIs('sick.*') || request()->routeIs('overtime.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Izin & Cuti</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 text-xs transition duration-150" :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-1 space-y-0.5">
                    <li>
                        <a href="{{ route('leave.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('leave.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Cuti Tahunan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('special-leave.index') }}"
                            class="flex items-center w-full px-3 py-1.5 pl-9 transition duration-75 rounded-lg text-[13px] group {{ request()->routeIs('special-leave.*') ? 'text-white bg-gray-800 font-semibold' : 'text-slate-400 hover:text-white hover:bg-gray-800/60' }}">
                            Cuti Khusus
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
        </ul>
    </div>
</aside>
