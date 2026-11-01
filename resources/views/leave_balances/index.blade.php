<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Messages -->
            @if ($message = Session::get('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-xs flex items-center gap-2" role="alert">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                    <span class="font-medium text-xs">{{ $message }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-xs" role="alert">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-circle-xmark text-red-600"></i>
                        <span class="font-semibold text-xs">Terjadi Kesalahan:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Header & Action Bar -->
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-scale-balanced text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Manajemen Kuota & Saldo Cuti Karyawan</h1>
                            <p class="text-xs text-gray-500 mt-0.5">Pantau dan kelola jatah kuota cuti tahunan, sisa saldo, serta penyesuaian hak cuti per karyawan.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <form action="{{ route('leave-balances.generate-annual') }}" method="POST" onsubmit="return confirm('Inisialisasi kuota cuti tahunan (default 12 hari) untuk seluruh karyawan yang belum memiliki saldo pada tahun {{ $year }}?');">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                            Inisialisasi Saldo Tahun {{ $year }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stats & Filter Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Filter Tahun -->
                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex flex-col justify-center">
                    <x-input-label for="filter_year" :value="__('Pilih Tahun Cuti')" />
                    <form method="GET" action="{{ route('leave-balances.index') }}" class="mt-1 flex items-center gap-2">
                        <x-select-input id="filter_year" name="year" :options="array_combine($years, $years)" :selected="$year" :noSelect2="true" onchange="this.form.submit()" class="w-full" />
                    </form>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Karyawan Berkuota</span>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ $totalEmployeesWithBalance }} <span class="text-xs font-normal text-gray-400">Orang</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-600">Total Sisa Saldo Tersedia</span>
                        <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ $totalRemainingDays }} <span class="text-xs font-normal text-gray-400">Hari</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-umbrella-beach"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-600">Total Cuti Terpakai</span>
                        <p class="text-2xl font-black text-amber-700 mt-0.5">{{ $totalUsedDays }} <span class="text-xs font-normal text-gray-400">Hari</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pb-4 mb-4 border-b border-gray-100">
                    <div>
                        <x-input-label for="filter_project" :value="__('Filter Proyek / Unit')" />
                        <x-select-input id="filter_project" name="filter_project" :options="$projects" placeholder="Semua Proyek" onchange="$('#leave-balance-table').DataTable().ajax.reload()" class="mt-1" />
                    </div>
                </div>

                <x-data-table id="leave-balance-table" :headers="['No', 'Karyawan', 'Proyek', 'Total Kuota', 'Terpakai', 'Sisa Saldo', 'Aksi']" />
            </div>

        </div>
    </div>

    <!-- Modal Penyesuaian Saldo Cuti -->
    <div id="balanceModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeBalanceModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <form id="balanceForm" method="POST" action="">
                    @csrf
                    @method('PUT')

                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="balanceModalTitle">Sesuaikan Saldo Cuti</h3>
                                <p class="text-xs text-gray-500 mt-0.5" id="balanceEmployeeName">-</p>
                            </div>
                            <button type="button" onclick="closeBalanceModal()" class="text-gray-400 hover:text-gray-500 text-lg">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <div>
                                    <span class="text-gray-500 block">Cuti Terpakai:</span>
                                    <span class="text-sm font-bold text-amber-700" id="balanceUsedLeave">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">Tahun Kuota:</span>
                                    <span class="text-sm font-bold text-gray-900" id="balanceYear">-</span>
                                </div>
                            </div>

                            <div>
                                <x-input-label for="total_leave" :value="__('Total Hak Kuota Cuti (Hari)')" :required="true" />
                                <x-text-input id="total_leave" name="total_leave" type="number" step="0.5" min="0" class="mt-1 block w-full" required />
                                <p class="text-[11px] text-gray-400 mt-1">Sisa saldo akan otomatis dihitung ulang: Total Kuota dikurangi Cuti Terpakai.</p>
                            </div>

                            <div>
                                <x-input-label for="notes" :value="__('Catatan Penyesuaian (Alasan Perubahan)')" />
                                <x-text-input id="notes" name="notes" type="text" placeholder="Contoh: Carry forward cuti tahun lalu 2 hari" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" onclick="closeBalanceModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#leave-balance-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('leave-balances.index') }}",
                        data: function (d) {
                            d.year = "{{ $year }}";
                            d.project_id = $('#filter_project').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'employee_info', name: 'employee.name' },
                        { data: 'project_name', name: 'employee.project.name' },
                        { data: 'total_leave_badge', name: 'total_leave' },
                        { data: 'used_leave_badge', name: 'used_leave' },
                        { data: 'remaining_leave_badge', name: 'remaining_leave' },
                        { data: 'action', orderable: false, searchable: false },
                    ],
                });
            });

            function editBalance(item) {
                $('#balanceForm').attr('action', "{{ url('leave-balances') }}/" + item.id);
                $('#balanceEmployeeName').text(item.name);
                $('#balanceYear').text(item.year);
                $('#balanceUsedLeave').text(item.used_leave + ' Hari');
                $('#total_leave').val(item.total_leave);
                $('#notes').val(item.notes || '');
                $('#balanceModal').removeClass('hidden');
            }

            function closeBalanceModal() {
                $('#balanceModal').addClass('hidden');
            }
        </script>
    @endpush
</x-app-layout>

