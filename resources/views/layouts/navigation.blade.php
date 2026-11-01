<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Mobile Logo & Brand (visible on mobile only) -->
                <a href="{{ route('timeline.index') }}" class="flex items-center gap-2 sm:hidden">
                    <x-application-logo class="block h-7 w-auto fill-current text-blue-600" />
                    <span class="font-bold text-gray-900 tracking-tight text-sm">HRD System</span>
                </a>
            </div>

            @php
                $navUser = Auth::user();
                if ($navUser) {
                    $navUser->loadMissing(['employee.jabatan', 'employee.company', 'employee.project.company']);
                }
                $navEmp = $navUser?->employee;
                $navDisplayName = $navEmp?->name ?? $navUser?->name;
                $navJabatan = $navEmp?->jabatan?->jabatan ?? ucfirst(str_replace('_', ' ', $navUser?->role ?? 'User'));
                $navComp = $navEmp?->effective_company ?? \App\Models\Company::first();
                $navCompCode = $navComp?->code ?? 'TJTM';
            @endphp

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center gap-2.5 px-3 py-1.5 border border-gray-200/80 rounded-xl text-left bg-white hover:bg-gray-50/80 focus:outline-none transition ease-in-out duration-150 shadow-xs">
                            <!-- Avatar Initials -->
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0 border border-blue-200">
                                {{ strtoupper(substr($navDisplayName ?? 'K', 0, 2)) }}
                            </div>

                            <!-- Name, Jabatan & Company -->
                            <div class="flex flex-col text-left leading-tight">
                                <span class="text-xs font-bold text-gray-900 tracking-tight">{{ $navDisplayName }}</span>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-500 mt-0.5">
                                    <span class="font-semibold text-blue-600">{{ $navJabatan }}</span>
                                    <span class="text-gray-300">•</span>
                                    <span class="font-mono font-medium text-gray-700 bg-gray-100 px-1 py-0.2 rounded text-[10px]">{{ $navCompCode }}</span>
                                </div>
                            </div>

                            <div class="ms-1 text-gray-400">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2.5 border-b border-gray-100 text-xs">
                            <span class="block font-bold text-gray-800">{{ $navDisplayName }}</span>
                            <span class="block text-gray-500 font-mono text-[11px]">{{ $navUser->email }}</span>
                            <div class="mt-1.5 flex items-center gap-1.5 text-[10px]">
                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold border border-blue-100">{{ $navJabatan }}</span>
                                <span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 font-mono font-bold">{{ $navCompCode }}</span>
                            </div>
                        </div>

                        <x-dropdown-link :href="route('ess.profile')">
                            <i class="fa-solid fa-id-badge mr-2 text-blue-500"></i>
                            {{ __('Profile Karyawan') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('profile.edit')">
                            <i class="fa-regular fa-user mr-2 text-gray-400"></i>
                            {{ __('Profile Akun') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                <i class="fa-solid fa-arrow-right-from-bracket mr-2 text-gray-400"></i>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('timeline.index')" :active="request()->routeIs('timeline.*')">
                <i class="fa-solid fa-newspaper mr-2 text-blue-500"></i>
                {{ __('Timeline') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ess.profile')" :active="request()->routeIs('ess.profile')">
                <i class="fa-solid fa-id-badge mr-2 text-blue-500"></i>
                {{ __('Profile') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') && !request()->routeIs('timeline.*')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if(auth()->user()?->canAccessFinanceBackoffice())
            <x-responsive-nav-link :href="route('employee.index')" :active="request()->routeIs('employee.*')">
                <i class="fa-solid fa-users mr-2 text-blue-500"></i>
                {{ __('Employee Data') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('emp-allowance.index')" :active="request()->routeIs('emp-allowance.*')">
                <i class="fa-solid fa-hand-holding-dollar mr-2 text-blue-500"></i>
                {{ __('Employee Allowance') }}
            </x-responsive-nav-link>
            @endif
            @php
                $navShowApprovalMenu = false;
                try {
                    if ($navEmp?->jabatan_id) {
                        $navShowApprovalMenu = \App\Models\ApprovalSetting::isJabatanAnApprover((int) $navEmp->jabatan_id);
                    }
                } catch (\Exception $e) {}
            @endphp
            @if($navShowApprovalMenu)
            <x-responsive-nav-link :href="route('approval-inbox.index')" :active="request()->routeIs('approval-inbox.*')">
                {{ __('Approval Inbox') }}
            </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('attendance.check-in')" :active="request()->routeIs('attendance.check-in')">
                {{ __('Presensi Masuk (ESS)') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('attendance.check-out')" :active="request()->routeIs('attendance.check-out')">
                {{ __('Presensi Keluar (ESS)') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')">
                <i class="fa-solid fa-calendar-days mr-2 text-blue-500"></i>
                {{ __('Kalender ESS') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('leave.index')" :active="request()->routeIs('leave.*')">
                {{ __('Cuti Tahunan (ESS)') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('special-leave.index')" :active="request()->routeIs('special-leave.*')">
                {{ __('Cuti Khusus (ESS)') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('permit.index')" :active="request()->routeIs('permit.*')">
                {{ __('Izin (ESS)') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('sick.index')" :active="request()->routeIs('sick.*')">
                {{ __('Izin Sakit (ESS)') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('overtime.index')" :active="request()->routeIs('overtime.*')">
                {{ __('Lembur (ESS)') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-bold text-base text-gray-900">{{ $navDisplayName }}</div>
                <div class="flex items-center gap-1.5 text-xs text-gray-500 mt-0.5">
                    <span class="font-semibold text-blue-600">{{ $navJabatan }}</span>
                    <span>•</span>
                    <span class="font-mono bg-gray-100 px-1 rounded text-[10px] font-bold text-gray-700">{{ $navCompCode }}</span>
                </div>
                <div class="text-xs text-gray-400 mt-1 font-mono">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('ess.profile')" :active="request()->routeIs('ess.profile')">
                    <i class="fa-solid fa-id-badge mr-2 text-blue-500"></i>
                    {{ __('Profile Karyawan') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>