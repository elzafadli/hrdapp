<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between mb-4">
                <div>
                    @if($employee)
                        <x-secondary-link href="{{ route('employee.index') }}">
                            Back to Employees
                        </x-secondary-link>
                    @endif
                </div>
                <x-primary-link href="{{ route('emp-loans.create', ['emp_id' => $employee ? $employee->id : null]) }}">
                    Create New Loan
                </x-primary-link>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ $employee ? 'Manage Loans for ' . $employee->name : 'Manage Employee Loans' }}
                    </h2>
                </div>

                <div class="p-6 text-gray-900">
                    @if ($message = Session::get('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                            role="alert">
                            <span class="block sm:inline">{{ $message }}</span>
                        </div>
                    @endif

                    <x-data-table id="emp-loan-table" :headers="['No', 'Employee', 'Amount', 'Loan Date', 'Status', 'Action']" />
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function () {
                $('#emp-loan-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('emp-loans.index', ['emp_id' => $employee ? $employee->id : null]) }}",
                    columns: [
                        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                        { data: 'employee_name', name: 'employee.name' },
                        { data: 'formatted_amount', name: 'amount' },
                        { data: 'loan_date', name: 'loan_date' },
                        { data: 'status', name: 'status' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });
            });
        </script>
    @endpush
</x-app-layout>