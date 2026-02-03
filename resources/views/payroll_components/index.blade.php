<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-end mb-4">
                <x-primary-link href="{{ route('payroll-components.create') }}">
                    Create New Component
                </x-primary-link>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ __('Manage Payroll Components') }}
                    </h2>
                </div>

                <div class="p-6 text-gray-900">


                    @if ($message = Session::get('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                            role="alert">
                            <span class="block sm:inline">{{ $message }}</span>
                        </div>
                    @endif

                    <x-data-table id="payroll-components-table" :headers="['No', 'Name', 'Type', 'Action']" />
                </div>
            </div>
        </div>
    </div>

    @push('scripts')

        <script>
            $(document).ready(function () {

                $('#payroll-components-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('payroll-components.index') }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'name', name: 'name' },
                        { data: 'type', name: 'type' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });
            });
        </script>

    @endpush
</x-app-layout>