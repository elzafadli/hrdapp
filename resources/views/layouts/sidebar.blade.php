<aside class="w-64 bg-slate-900 text-white min-h-screen hidden sm:block">
    <div class="h-16 flex items-center px-6 border-b border-gray-700">
        <x-application-logo class="block h-9 w-auto fill-current text-white mr-2" />
        <span class="text-xl font-bold">HRD System</span>
    </div>

    <div class="h-full px-3 py-4 overflow-y-auto">
        <ul class="space-y-2 font-medium">
            <li>
                <a href="{{ route('dashboard') }}"
                    class="flex items-center p-2 rounded-lg group {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-gauge w-5 h-5 transition duration-75 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="ms-3">Dashboard</span>
                </a>
            </li>

            <li class="pt-4 pb-2">
                <span class="px-2 text-xs font-semibold text-gray-500 uppercase">Master Data</span>
            </li>

            <li
                x-data="{ open: {{ request()->routeIs('employee.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') ? 'true' : 'false' }} }">
                <button type="button" @click="open = !open"
                    class="flex items-center w-full p-2 rounded-lg group transition duration-75 {{ request()->routeIs('employee.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-users w-5 h-4 transition duration-75 {{ request()->routeIs('employee.*') || request()->routeIs('emp-allowance.*') || request()->routeIs('emp-loans.*') || request()->routeIs('emp-bpjs.*') || request()->routeIs('potongan-karyawan.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="flex-1 ms-3 text-left whitespace-nowrap">Employees</span>
                    <i class="fa-solid fa-chevron-down w-3 h-3 transition duration-75"
                        :class="{ 'rotate-180': open }"></i>
                </button>
                <ul x-show="open" x-collapse class="py-2 space-y-2">
                    <li>
                        <a href="{{ route('employee.index') }}"
                            class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-10 group {{ request()->routeIs('employee.*') && !request()->routeIs('emp-allowance.*') && !request()->routeIs('emp-loans.*') && !request()->routeIs('emp-bpjs.*') && !request()->routeIs('potongan-karyawan.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            Data Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('emp-allowance.index') }}"
                            class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-10 group {{ request()->routeIs('emp-allowance.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            Tunjangan Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('emp-loans.index') }}"
                            class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-10 group {{ request()->routeIs('emp-loans.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            Pinjaman Karyawan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('potongan-karyawan.index') }}"
                            class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-10 group {{ request()->routeIs('potongan-karyawan.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            Potongan Karyawan
                        </a>
                    </li>
                    <!-- <li>
                        <a href="{{ route('emp-bpjs.index') }}"
                            class="flex items-center w-full p-2 transition duration-75 rounded-lg pl-10 group {{ request()->routeIs('emp-bpjs.*') ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                            BPJS Karyawan
                        </a>
                    </li> -->
                </ul>
            </li>

            <li>
                <a href="{{ route('employee-jabatan.index') }}"
                    class="flex items-center p-2 rounded-lg group {{ request()->routeIs('employee-jabatan.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-briefcase w-5 h-4 transition duration-75 {{ request()->routeIs('employee-jabatan.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="ms-3">Jabatan</span>
                </a>
            </li>


            <li class="pt-4 pb-2">
                <span class="px-2 text-xs font-semibold text-gray-500 uppercase">Setting</span>
            </li>


            <li>
                <a href="{{ route('payroll-settings.index') }}"
                    class="flex items-center p-2 rounded-lg group {{ request()->routeIs('payroll-settings.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-gears w-5 h-4 transition duration-75 {{ request()->routeIs('payroll-settings.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="ms-3">Payroll Settings</span>
                </a>
            </li>

            <li>
                <a href="{{ route('payroll-components.index') }}"
                    class="flex items-center p-2 rounded-lg group {{ request()->routeIs('payroll-components.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-list w-5 h-4 transition duration-75 {{ request()->routeIs('payroll-components.*') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="ms-3">Components</span>
                </a>
            </li>

            <li class="pt-4 pb-2">
                <span class="px-2 text-xs font-semibold text-gray-500 uppercase">Payroll</span>
            </li>
            <li>
                <a href="{{ route('payroll-results.create') }}"
                    class="flex items-center p-2 rounded-lg group {{ request()->routeIs('payroll-results.create') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-calculator w-5 h-4 transition duration-75 {{ request()->routeIs('payroll-results.create') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="ms-3">Payroll Process</span>
                </a>
            </li>
            <li>
                <a href="{{ route('payroll-results.index') }}"
                    class="flex items-center p-2 rounded-lg group {{ request()->routeIs('payroll-results.index') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-file-invoice-dollar w-5 h-4 transition duration-75 {{ request()->routeIs('payroll-results.index') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="ms-3">Payroll Results</span>
                </a>
            </li>
            <li>
                <a href="{{ route('payroll-results.deductions') }}"
                    class="flex items-center p-2 rounded-lg group {{ request()->routeIs('payroll-results.deductions') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    <i
                        class="fa-solid fa-receipt w-5 h-4 transition duration-75 {{ request()->routeIs('payroll-results.deductions') ? 'text-white' : 'text-gray-400 group-hover:text-white' }}"></i>
                    <span class="ms-3">Payroll BPJS</span>
                </a>
            </li>
        </ul>
    </div>
</aside>