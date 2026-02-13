<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <x-primary-link href="{{ route('employee.create') }}">
                    Create New Employee
                </x-primary-link>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ __('Manage Employee Data') }}
                    </h2>
                </div>

                <div class="p-6 text-gray-900">


                    @if ($message = Session::get('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                            role="alert">
                            <span class="block sm:inline">{{ $message }}</span>
                        </div>
                    @endif

                    <x-data-table id="emp-data-table" :headers="['No', 'Name', 'Jabatan', 'Project', 'Status PTKP', 'Action']" />
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
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'jabatan_name', name: 'jabatan.jabatan' },
                        { data: 'project_name', name: 'project.name', orderable: true, searchable: true },
                        {
                            data: 'status_ptkp',
                            name: 'status_ptkp',
                            render: function (data, type, row) {
                                if (!data) return '-';
                                const map = {
                                    'TK0': 'TK/0 - Tidak Kawin, 0 Tanggungan (TER A)',
                                    'TK1': 'TK/1 - Tidak Kawin, 1 Tanggungan (TER A)',
                                    'TK2': 'TK/2 - Tidak Kawin, 2 Tanggungan (TER B)',
                                    'TK3': 'TK/3 - Tidak Kawin, 3 Tanggungan (TER B)',
                                    'K0': 'K/0 - Kawin, 0 Tanggungan (TER A)',
                                    'K1': 'K/1 - Kawin, 1 Tanggungan (TER B)',
                                    'K2': 'K/2 - Kawin, 2 Tanggungan (TER B)',
                                    'K3': 'K/3 - Kawin, 3 Tanggungan (TER C)',
                                };
                                return `<div><span class="">${data} - ${map[data] ? map[data].split(' - ')[1] : ''}</span></div>`;
                            }
                        },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });
            });
        </script>

    @endpush
</x-app-layout>