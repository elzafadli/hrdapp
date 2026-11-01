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
                        <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-calendar-xmark text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Hari Libur Nasional & Cuti Bersama</h1>
                            <p class="text-xs text-gray-500 mt-0.5">Kelola tanggal merah resmi dan cuti bersama yang berlaku pada perhitungan cuti, lembur, dan absensi.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Form Auto Generate -->
                    <form action="{{ route('holidays.auto-generate') }}" method="POST" onsubmit="return confirm('Generate daftar hari libur nasional resmi & cuti bersama SKB untuk tahun {{ $year }}? Data yang sudah ada tidak akan diduplikasi.');">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                            <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                            Auto-Generate SKB {{ $year }}
                        </button>
                    </form>

                    <!-- Tombol Tambah Manual -->
                    <button type="button" onclick="openCreateModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Tambah Libur
                    </button>
                </div>
            </div>

            <!-- Statistik Kartu & Filter Tahun -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Filter Tahun -->
                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex flex-col justify-center">
                    <x-input-label for="filter_year" :value="__('Pilih Tahun Kalender')" />
                    <form method="GET" action="{{ route('holidays.index') }}" class="mt-1 flex items-center gap-2">
                        <x-select-input id="filter_year" name="year" :options="array_combine($years, $years)" :selected="$year" :noSelect2="true" onchange="this.form.submit()" class="w-full" />
                    </form>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Tanggal Libur</span>
                        <p class="text-2xl font-black text-gray-900 mt-0.5">{{ $totalHolidays }} <span class="text-xs font-normal text-gray-400">Hari</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-red-600">Libur Nasional</span>
                        <p class="text-2xl font-black text-red-700 mt-0.5">{{ $nationalHolidays }} <span class="text-xs font-normal text-gray-400">Hari</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-flag"></i>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-600">Cuti Bersama</span>
                        <p class="text-2xl font-black text-amber-700 mt-0.5">{{ $cutiBersama }} <span class="text-xs font-normal text-gray-400">Hari</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-umbrella-beach"></i>
                    </div>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                    <h2 class="font-bold text-sm text-gray-900">Daftar Tanggal Libur Tahun {{ $year }}</h2>
                    <div class="flex items-center gap-2">
                        <select id="filter_type" onchange="$('#holidays-table').DataTable().ajax.reload()" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md py-1 px-3 text-xs shadow-xs">
                            <option value="">Semua Jenis Libur</option>
                            <option value="libur_nasional">Hanya Libur Nasional</option>
                            <option value="cuti_bersama">Hanya Cuti Bersama</option>
                        </select>
                    </div>
                </div>

                <x-data-table id="holidays-table" :headers="['No', 'Tanggal', 'Nama Hari Libur', 'Kategori', 'Keterangan', 'Aksi']" />
            </div>

        </div>
    </div>

    <!-- Modal Form Tambah / Edit -->
    <div id="holidayModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeHolidayModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="holidayForm" method="POST" action="{{ route('holidays.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">

                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                            <h3 class="text-base font-bold text-gray-900" id="modalTitle">Tambah Hari Libur</h3>
                            <button type="button" onclick="closeHolidayModal()" class="text-gray-400 hover:text-gray-500 text-lg">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <x-input-label for="holiday_date" :value="__('Tanggal Libur')" :required="true" />
                                <x-text-input id="holiday_date" name="holiday_date" type="date" class="mt-1 block w-full" required />
                                <x-input-error class="mt-1" :messages="$errors->get('holiday_date')" />
                            </div>

                            <div>
                                <x-input-label for="name" :value="__('Nama Hari Libur')" :required="true" />
                                <x-text-input id="name" name="name" type="text" placeholder="Contoh: Hari Raya Idul Fitri 1447 H" class="mt-1 block w-full" required />
                                <x-input-error class="mt-1" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="is_cuti_bersama" :value="__('Jenis Libur')" :required="true" />
                                <x-select-input id="is_cuti_bersama" name="is_cuti_bersama" :noSelect2="true" class="mt-1 block w-full"
                                    :options="['0' => 'Libur Nasional', '1' => 'Cuti Bersama']" />
                            </div>

                            <div>
                                <x-input-label for="description" :value="__('Keterangan (Opsional)')" />
                                <textarea id="description" name="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs text-xs" placeholder="Keterangan tambahan atau dasar SKB 3 Menteri..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-3 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button type="button" onclick="closeHolidayModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#holidays-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('holidays.index') }}",
                        data: function (d) {
                            d.year = "{{ $year }}";
                            d.type = $('#filter_type').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'formatted_date', name: 'holiday_date' },
                        { data: 'name', name: 'name' },
                        { data: 'badge_type', name: 'is_cuti_bersama' },
                        { data: 'description', name: 'description' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });
            });

            function openCreateModal() {
                $('#holidayForm').attr('action', "{{ route('holidays.store') }}");
                $('#formMethod').val('POST');
                $('#modalTitle').text('Tambah Hari Libur Baru');
                $('#holiday_date').val("{{ $year }}-01-01");
                $('#name').val('');
                $('#is_cuti_bersama').val('0');
                $('#description').val('');
                $('#holidayModal').removeClass('hidden');
            }

            function editHoliday(item) {
                $('#holidayForm').attr('action', "{{ url('holidays') }}/" + item.id);
                $('#formMethod').val('PUT');
                $('#modalTitle').text('Edit Hari Libur');
                $('#holiday_date').val(item.holiday_date ? item.holiday_date.substring(0, 10) : '');
                $('#name').val(item.name);
                $('#is_cuti_bersama').val(item.is_cuti_bersama ? '1' : '0');
                $('#description').val(item.description || '');
                $('#holidayModal').removeClass('hidden');
            }

            function closeHolidayModal() {
                $('#holidayModal').addClass('hidden');
            }
        </script>
    @endpush
</x-app-layout>

