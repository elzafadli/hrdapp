<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ isset($header) ? 'Edit Deductions for ' . \Carbon\Carbon::createFromDate($header->year, $header->month, 1)->format('F Y') : __('Create New Deduction Period') }}
                    </h2>
                </div>

                <form method="POST" action="{{ isset($header) ? route('potongan-karyawan.update', $header->id) : route('potongan-karyawan.store') }}">
                    @csrf
                    @isset($header) @method('PUT') @endisset

                    <div class="p-6 text-gray-900 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="year" :value="__('Year')" />
                                <x-text-input id="year" name="year" type="number" class="block w-full mt-1" value="{{ $header->year ?? date('Y') }}" min="2000" max="2100" required autofocus />
                                <x-input-error class="mt-2" :messages="$errors->get('year')" />
                            </div>

                            <div>
                                <x-input-label for="month" :value="__('Month')" />
                                <select id="month" name="month" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ isset($header) && $header->month == $i ? 'selected' : ($i == date('n') ? 'selected' : '') }}>
                                            {{ \Carbon\Carbon::createFromDate(2024, $i, 1)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('month')" />
                            </div>
                        </div>

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
                                                    @php
                                                        $deduction = $existingDeductions->firstWhere(function($item) use ($employee, $payrollComponent) {
                                                            return $item['employee_id'] == $employee->id && $item['component_id'] == $payrollComponent->id;
                                                        });
                                                        $deductionValue = $deduction ? $deduction['value'] : '';
                                                    @endphp
                                                    <input type="text"
                                                        name="deductions[{{ $employee->id }}_{{ $payrollComponent->id }}]"
                                                        class="block w-full currency-mask text-right border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                        value="{{ $deductionValue }}"
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
                        <x-primary-button>{{ isset($header) ? __('Save Deductions') : __('Create') }}</x-primary-button>

                        <x-secondary-link href="{{ route('potongan-karyawan.index') }}">
                            {{ __('Cancel') }}
                        </x-secondary-link>

                        <!-- @isset($header)
                            <form action="{{ route('potongan-karyawan.destroy', $header->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                    Delete
                                </button>
                            </form>
                        @endisset -->
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Initialize input mask
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

                // Format existing values (skip if empty or already formatted)
                $('.currency-mask').each(function() {
                    const value = $(this).val();
                    if (value !== '' && !value.includes('.')) {
                        const numValue = parseFloat(value);
                        if (!isNaN(numValue)) {
                            $(this).val(numValue.toFixed(2));
                        }
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
