<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Message Alert -->
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-sm flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-circle-check text-emerald-500"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-sm flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(!$hasDetailsTable)
                <div class="bg-amber-50 border border-amber-200 text-amber-900 p-4 rounded-2xl text-sm flex items-start gap-3 shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5"></i>
                    <div>
                        <strong class="font-bold">Migrasi Database Diperlukan:</strong>
                        <p class="mt-0.5 text-amber-800">
                            Struktur Header-Detail (`approval_setting_details`) telah disiapkan. Silakan jalankan perintah berikut di terminal:
                            <code class="bg-amber-200/60 px-2 py-0.5 rounded font-mono text-xs font-bold text-amber-900 ml-1">php artisan migrate</code>
                        </p>
                    </div>
                </div>
            @endif

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                        Setting Approver Berjenjang
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Konfigurasi alur persetujuan bertingkat (Header-Detail) berdasarkan modul ESS dan jabatan pemohon.
                    </p>
                </div>
                <div>
                    <a href="{{ route('approval-settings.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Tambah Alur Approval</span>
                    </a>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
                <!-- Filter Section -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Filter Modul -->
                        <div class="w-56">
                            <x-input-label for="module_filter" :value="__('Filter Modul ESS')" class="text-xs font-semibold text-gray-600 mb-1" />
                            <x-select-input id="module_filter" :noSelect2="true" class="w-full">
                                <option value="all_filter">-- Tampilkan Semua Modul --</option>
                                @foreach($modules as $key => $lbl)
                                    <option value="{{ $key }}">{{ $lbl }}</option>
                                @endforeach
                            </x-select-input>
                        </div>

                        <!-- Filter Jabatan Pemohon -->
                        <div class="w-64">
                            <x-input-label for="jabatan_filter" :value="__('Filter Jabatan Pemohon')" class="text-xs font-semibold text-gray-600 mb-1" />
                            <x-select-input id="jabatan_filter" :noSelect2="true" class="w-full">
                                <option value="all_filter">-- Semua Jabatan Pemohon --</option>
                                <option value="default_only">Default (Semua Jabatan)</option>
                                @foreach($jabatans as $jab)
                                    <option value="{{ $jab->id }}">{{ $jab->jabatan }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                    </div>

                    <div class="text-xs text-gray-400 self-end sm:self-center">
                        <i class="fa-solid fa-circle-info mr-1 text-blue-500"></i> Alur spesifik jabatan & modul akan meng-override alur umum 'Semua Modul (All)'.
                    </div>
                </div>

                <!-- DataTable -->
                <div class="overflow-x-auto">
                    <table id="approvalSettingsTable" class="w-full text-left text-sm display nowrap" style="width:100%">
                        <thead class="bg-gray-50/80 text-gray-600 uppercase text-xs font-semibold border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3">Modul ESS</th>
                                <th class="px-4 py-3">Jabatan Pemohon</th>
                                <th class="px-4 py-3">Alur Persetujuan (Pipeline)</th>
                                <th class="px-4 py-3 text-center">Tingkatan</th>
                                <th class="px-4 py-3">Keterangan</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                const table = $('#approvalSettingsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: "{{ route('approval-settings.index') }}",
                        data: function (d) {
                            d.module_filter = $('#module_filter').val();
                            d.jabatan_filter = $('#jabatan_filter').val();
                        }
                    },
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'module_badge', name: 'module' },
                        { data: 'requester_jabatan', name: 'requester_jabatan_id' },
                        { data: 'workflow_pipeline', name: 'workflow_pipeline', orderable: false, searchable: false },
                        { data: 'total_levels', name: 'total_levels', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'description_text', name: 'description' },
                        { data: 'status_badge', name: 'is_active', className: 'text-center' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
                    ],
                });

                $('#module_filter, #jabatan_filter').on('change', function () {
                    table.ajax.reload();
                });
            });
        </script>
    @endpush
</x-app-layout>
