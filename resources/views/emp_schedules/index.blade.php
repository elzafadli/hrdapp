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
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-user-clock text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Penugasan Jadwal & Shift Karyawan</h1>
                            <p class="text-xs text-gray-500 mt-0.5">Tentukan jadwal kerja aktif per karyawan atau lakukan penugasan massal (bulk assignment).</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ route('work-schedules.index') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition">
                        <i class="fa-solid fa-business-time text-xs"></i>
                        Master Pola Jadwal
                    </a>

                    <button type="button" onclick="openBulkAssignModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                        <i class="fa-solid fa-users-gear text-xs"></i>
                        Tugaskan Terpilih (Bulk)
                    </button>
                </div>
            </div>

            <!-- Filter & Table Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pb-4 mb-4 border-b border-gray-100">
                    <div>
                        <x-input-label for="filter_project" :value="__('Filter Proyek / Departemen')" />
                        <x-select-input id="filter_project" name="filter_project" :options="$projects" placeholder="Semua Proyek" onchange="$('#emp-schedule-table').DataTable().ajax.reload()" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="filter_schedule" :value="__('Filter Pola Jadwal')" />
                        <x-select-input id="filter_schedule" name="filter_schedule" :options="$schedules" placeholder="Semua Jadwal" onchange="$('#emp-schedule-table').DataTable().ajax.reload()" class="mt-1" />
                    </div>

                    <div class="flex items-end">
                        <button type="button" onclick="resetFilters()" class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filter
                        </button>
                    </div>
                </div>

                <x-data-table id="emp-schedule-table" :headers="['<input type=\'checkbox\' id=\'checkAll\' class=\'rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4\'>', 'No', 'Karyawan', 'Proyek', 'Jadwal Aktif', 'Aksi']" />
            </div>

        </div>
    </div>

    <!-- Modal Penugasan Jadwal Kerja -->
    <div id="assignModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeAssignModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="assignForm" method="POST" action="{{ route('emp-schedules.bulk-assign') }}">
                    @csrf
                    <div id="selectedEmpInputsContainer"></div>

                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="assignModalTitle">Penugasan Jadwal Kerja</h3>
                                <p class="text-xs text-gray-500 mt-0.5" id="assignModalSubtitle">Pilih pola jadwal kerja yang akan diterapkan.</p>
                            </div>
                            <button type="button" onclick="closeAssignModal()" class="text-gray-400 hover:text-gray-500 text-lg">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <x-input-label for="work_schedule_id" :value="__('Pola Jadwal Kerja')" :required="true" />
                                <x-select-input id="work_schedule_id" name="work_schedule_id" :options="$schedules" placeholder="Pilih Pola Jadwal Kerja" required class="mt-1" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="effective_date" :value="__('Tanggal Mulai Berlaku')" :required="true" />
                                    <x-text-input id="effective_date" name="effective_date" type="date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full" required />
                                </div>
                                <div>
                                    <x-input-label for="end_date" :value="__('Tanggal Berakhir (Opsional)')" />
                                    <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="notes" :value="__('Catatan Penugasan (Opsional)')" />
                                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs text-xs" placeholder="Contoh: Rotasi Shift Periode Q1..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" onclick="closeAssignModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                            Terapkan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                const table = $('#emp-schedule-table').DataTable({
                    processing: true,
                    serverSide: true,
                    order: [[1, 'asc']],
                    ajax: {
                        url: "{{ route('emp-schedules.index') }}",
                        data: function (d) {
                            d.project_id = $('#filter_project').val();
                            d.schedule_id = $('#filter_schedule').val();
                        }
                    },
                    columns: [
                        { data: 'checkbox', orderable: false, searchable: false, width: '30px' },
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'employee_info', name: 'name' },
                        { data: 'project_name', name: 'project.name' },
                        { data: 'current_schedule', orderable: false, searchable: false },
                        { data: 'action', orderable: false, searchable: false },
                    ],
                });

                // Check All toggle & prevent sorting on header click
                $(document).on('click', '#checkAll', function (e) {
                    e.stopPropagation();
                });

                $(document).on('change', '#checkAll', function () {
                    $('.emp-checkbox').prop('checked', $(this).is(':checked'));
                });
            });

            function resetFilters() {
                $('#filter_project').val('').trigger('change');
                $('#filter_schedule').val('').trigger('change');
                $('#emp-schedule-table').DataTable().ajax.reload();
            }

            function openAssignSingleModal(empId, empName) {
                $('#selectedEmpInputsContainer').html('<input type="hidden" name="emp_ids[]" value="' + empId + '">');
                $('#assignModalTitle').text('Atur Jadwal Kerja: ' + empName);
                $('#assignModalSubtitle').text('Terapkan jadwal kerja khusus untuk karyawan ini.');
                $('#assignModal').removeClass('hidden');
            }

            function openBulkAssignModal() {
                const selected = [];
                $('.emp-checkbox:checked').each(function () {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    alert('Pilih minimal satu karyawan terlebih dahulu dengan mencentang kotak di tabel.');
                    return;
                }

                let inputsHtml = '';
                selected.forEach(function (id) {
                    inputsHtml += '<input type="hidden" name="emp_ids[]" value="' + id + '">';
                });

                $('#selectedEmpInputsContainer').html(inputsHtml);
                $('#assignModalTitle').text('Penugasan Jadwal Massal');
                $('#assignModalSubtitle').text('Akan diterapkan ke ' + selected.length + ' karyawan terpilih.');
                $('#assignModal').removeClass('hidden');
            }

            function closeAssignModal() {
                $('#assignModal').addClass('hidden');
            }
        </script>
    @endpush
</x-app-layout>

