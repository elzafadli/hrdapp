<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        Edit Deductions for {{ \Carbon\Carbon::createFromDate($header->year, $header->month, 1)->format('F Y') }}
                    </h2>
                </div>

                <form method="POST" action="{{ route('potongan-karyawan.update', $header->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="p-6 text-gray-900">
                        <div class="border rounded-lg overflow-hidden">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3">Employee</th>
                                        @foreach($components as $payrollComponent)
                                            <th class="px-4 py-3 min-w-[150px]">{{ $payrollComponent->name }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employees as $employee)
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900">{{ $employee->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $employee->jabatan->jabatan ?? '-' }}</div>
                                            </td>
                                            @foreach($components as $payrollComponent)
                                                <td class="px-4 py-3">
                                                    <input type="text"
                                                        name="deductions[{{ $employee->id }}_{{ $payrollComponent->id }}]"
                                                        class="block w-full currency-mask text-right border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                        :value="getDeductionValue(existingDeductions, {{ $employee->id }}, {{ $payrollComponent->id }})"
                                                        placeholder="0.00"
                                                    />
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center gap-4">
                        <x-primary-button>{{ __('Save Deductions') }}</x-primary-button>

                        <x-secondary-link href="{{ route('potongan-karyawan.index') }}">
                            {{ __('Cancel') }}
                        </x-secondary-link>

                        <form action="{{ route('potongan-karyawan.destroy', $header->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Delete
                            </button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const existingDeductions = {{ $existingDeductions->toJson() }};

            document.addEventListener('DOMContentLoaded', function () {
                $('.currency-mask').inputmask({
                    alias: 'numeric',
                    groupSeparator: ",",
                    digits: 2,
                    digitsOptional: false,
                    placeholder: "0.00",
                    rightAlign: true,
                    autoGroup: true,
                    prefix: "",
                    allowMinus: false,
                    removeMaskOnSubmit: true
                });
            });

            function getDeductionValue(existingDeductions, employeeId, componentId) {
                const deduction = existingDeductions.find(d => d.employee_id == employeeId && d.component_id == componentId);
                return deduction ? deduction.value : '';
            }
        </script>
    @endpush
</x-app-layout>
