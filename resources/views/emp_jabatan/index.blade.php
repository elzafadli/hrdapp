<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <x-primary-link href="{{ route('employee-jabatan.create') }}">
                    Create New Jabatan
                </x-primary-link>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ __('Manage Jabatan') }}
                    </h2>
                </div>

                <div class="p-6 text-gray-900">


                    @if ($message = Session::get('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                            role="alert">
                            <span class="block sm:inline">{{ $message }}</span>
                        </div>
                    @endif

                    <x-data-table id="jabatan-table" :headers="['No', 'Jabatan', 'Action']" />
                </div>
            </div>
        </div>
    </div>

    @push('scripts')

        <script>
            $(document).ready(function () {

                $('#jabatan-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('employee-jabatan.index') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'jabatan', name: 'jabatan' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                    // Simple styling overrides to match Tailwind somewhat if standard DataTables CSS is loaded
                    // Actually, since we didn't install the DataTables tailwind plugin, we assume standard usage 
                    // but wrapped in Tailwind containers.
                });
            });
        </script>
        <!-- Add DataTables CSS -->

    @endpush
</x-app-layout>