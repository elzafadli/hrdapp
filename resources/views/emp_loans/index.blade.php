<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-4">
                <div>
                    @if($employee)
                        <x-secondary-link href="{{ route('employee.index') }}">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Back to Employees
                        </x-secondary-link>
                    @endif
                </div>
                <x-primary-link href="{{ route('emp-loans.create', ['emp_id' => $employee ? $employee->id : null]) }}">
                    <i class="fa-solid fa-plus mr-1"></i> Create New Loan
                </x-primary-link>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                            {{ $employee ? 'Manage Loans for ' . $employee->name : 'Manage Employee Loans' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Kelola data pinjaman karyawan dan posting anggaran ke modul Budgeting.</p>
                    </div>
                </div>

                <div class="p-6 text-gray-900">
                    @if ($message = Session::get('success'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg relative mb-4 flex items-center gap-2" role="alert">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            <span class="block sm:inline font-medium text-sm">{{ $message }}</span>
                        </div>
                    @endif

                    @if ($message = Session::get('warning'))
                        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg relative mb-4 flex items-center gap-2" role="alert">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                            <span class="block sm:inline font-medium text-sm">{{ $message }}</span>
                        </div>
                    @endif

                    @if (isset($errors) && $errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg relative mb-4" role="alert">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-circle-xmark text-red-600"></i>
                                <span class="font-semibold text-sm">Terjadi Kesalahan:</span>
                            </div>
                            <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <x-data-table id="emp-loan-table" :headers="['No', 'Employee', 'Project', 'Amount', 'Cicilan / Bulan', 'Start Date', 'End Date', 'Lampiran', 'Status', 'Budget Ref', 'Action']" />
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
                        { data: 'project_name', name: 'project.name' },
                        { data: 'formatted_amount', name: 'amount' },
                        { data: 'formatted_installment', name: 'installment_amount' },
                        { data: 'start_date', name: 'start_date' },
                        { data: 'end_date', name: 'end_date' },
                        { data: 'attachment_badge', name: 'attachment_badge', orderable: false, searchable: false },
                        { data: 'status', name: 'status' },
                        { data: 'budget_reference', name: 'budget_reference_number' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });
            });
        </script>
    @endpush
</x-app-layout>
