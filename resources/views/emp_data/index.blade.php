<x-app-layout>
    <div class="py-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Top Header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Data Karyawan</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Kelola seluruh data biodata, identitas, dan status kepegawaian karyawan</p>
                </div>
                <div>
                    <a href="{{ route('employee.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition">
                        <i class="fa-solid fa-user-plus"></i>
                        Tambah Karyawan
                    </a>
                </div>
            </div>

            @if ($message = Session::get('success'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                    <span>{{ $message }}</span>
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users text-blue-600"></i>
                        <h2 class="font-bold text-sm text-gray-900">Daftar Karyawan</h2>
                    </div>
                </div>

                <div class="p-6">
                    <x-data-table id="emp-data-table" :headers="['No', 'ID', 'Karyawan', 'Gender', 'Company', 'Jabatan', 'Aksi']" />
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#emp-data-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('employee.index') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                        { data: 'employee_code', name: 'nik_karyawan', width: '8%' },
                        { data: 'employee_info', name: 'name' },
                        { data: 'gender_label', name: 'gender', searchable: false, width: '10%' },
                        { data: 'company_name', name: 'company.code', orderable: false, width: '8%' },
                        { data: 'jabatan_name', name: 'jabatan.jabatan', width: '15%' },
                        { data: 'action', name: 'action', orderable: false, searchable: false, width: '12%' },
                    ],
                });
            });
        </script>
    @endpush
</x-app-layout>
