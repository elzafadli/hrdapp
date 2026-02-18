<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Payroll BPJS') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('payroll-results.index') }}"
                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Full Results
                </a>
                <a href="{{ route('payroll-results.create') }}"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Process Payroll
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex gap-4 items-end">
                        <form method="GET" action="{{ route('payroll-results.deductions') }}" class="flex gap-4 items-end flex-1">
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700">Month</label>
                                <select id="month" name="month"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ \DateTime::createFromFormat('!m', $m)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                                <input type="number" name="year" id="year" value="{{ $year }}" required
                                    class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            </div>
                            <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Filter
                            </button>
                        </form>
                        <a href="{{ route('payroll-results.export-deductions', ['month' => $month, 'year' => $year]) }}"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Export Excel
                        </a>
                    </div>

                    @php
                        // Split payroll columns by type - subsidi and bpjs
                        $subsidiColumns = $payrollColumns->where('type', 'subsidi');
                        $bpjsColumns = $payrollColumns->where('type', 'bpjs');
                    @endphp

                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10 text-xs">
                                <tr>
                                    <th scope="col"
                                        class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Company
                                    </th>
                                    <th scope="col"
                                        class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Project
                                    </th>
                                    <th scope="col"
                                        class="px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    @if($subsidiColumns->isNotEmpty())
                                        <th scope="col"
                                            colspan="{{ $subsidiColumns->count() + 1 }}"
                                            class="px-2 py-2 text-center text-xs font-bold text-yellow-800 uppercase tracking-wider bg-yellow-100">
                                            Dibayar Perusahaan
                                        </th>
                                    @endif
                                    @if($bpjsColumns->isNotEmpty())
                                        <th scope="col"
                                            colspan="{{ $bpjsColumns->count() + 1 }}"
                                            class="px-2 py-2 text-center text-xs font-bold text-red-800 uppercase tracking-wider bg-red-100">
                                            Dibayar Karyawan
                                        </th>
                                    @endif
                                </tr>
                                <tr>
                                    <th colspan="4"></th>
                                    @foreach($subsidiColumns as $column)
                                        <th scope="col"
                                            class="px-2 py-2 text-right text-xs font-medium text-yellow-700 uppercase tracking-wider bg-yellow-50">
                                            {{ $column->name }}
                                        </th>
                                    @endforeach
                                    @if($subsidiColumns->isNotEmpty())
                                        <th scope="col"
                                            class="px-2 py-2 text-right text-xs font-medium text-yellow-700 uppercase tracking-wider bg-yellow-100">
                                            Total Subsidi
                                        </th>
                                    @endif
                                    @foreach($bpjsColumns as $column)
                                        <th scope="col"
                                            class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $column->name }}
                                        </th>
                                    @endforeach
                                    @if($bpjsColumns->isNotEmpty())
                                        <th scope="col"
                                            class="px-2 py-2 text-right text-xs font-medium text-red-700 uppercase tracking-wider bg-red-50">
                                            Total Potongan
                                        </th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($groupedResults as $empId => $empResults)
                                    @php
                                        $employee = $empResults->first()->employee;
                                        $totalSubsidi = 0;
                                        $totalPotongan = 0;
                                    @endphp
                                    <tr>
                                        <td class="px-2 py-2 whitespace-nowrap text-center text-gray-900">
                                            {{ $employee->project->company->code ?? 'N/A' }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center text-gray-900">
                                            {{ $employee->project->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center font-medium text-gray-900">
                                            {{ $employee->name ?? 'N/A' }}
                                        </td>
                                        @foreach($subsidiColumns as $column)
                                            @php
                                                $result = $empResults->firstWhere('payroll_component_id', $column->id);
                                                $amount = $result ? $result->amount : 0;
                                                $totalSubsidi += $amount;
                                            @endphp
                                            <td class="px-2 py-2 whitespace-nowrap text-right">
                                                {{ $result ? number_format($result->amount, 2) : '-' }}
                                            </td>
                                        @endforeach
                                        @if($subsidiColumns->isNotEmpty())
                                            <td class="px-2 py-2 whitespace-nowrap text-right font-semibold text-yellow-700 bg-yellow-50">
                                                {{ number_format($totalSubsidi, 2) }}
                                            </td>
                                        @endif
                                        @foreach($bpjsColumns as $column)
                                            @php
                                                $result = $empResults->firstWhere('payroll_component_id', $column->id);
                                                $amount = $result ? $result->amount : 0;
                                                $totalPotongan += $amount;
                                            @endphp
                                            <td class="px-2 py-2 whitespace-nowrap text-right">
                                                {{ $result ? number_format($result->amount, 2) : '-' }}
                                            </td>
                                        @endforeach
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-semibold text-red-700 bg-red-50">
                                            {{ number_format($totalPotongan, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-2 py-2 whitespace-nowrap text-center"
                                            colspan="{{ 4 + $subsidiColumns->count() + ($subsidiColumns->isNotEmpty() ? 1 : 0) + $bpjsColumns->count() + ($bpjsColumns->isNotEmpty() ? 1 : 0) }}">
                                            No data available
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-100 font-semibold">
                                @php
                                    // Calculate totals for each column type
                                    $subsidiTotals = [];
                                    $deductionTotals = [];
                                    $grandTotalSubsidi = 0;
                                    $grandTotalPotongan = 0;

                                    foreach($subsidiColumns as $column) {
                                        $subsidiTotals[$column->id] = 0;
                                    }
                                    foreach($bpjsColumns as $column) {
                                        $deductionTotals[$column->id] = 0;
                                    }

                                    // Calculate totals from all employee results
                                    foreach($groupedResults as $empResults) {
                                        foreach($empResults as $result) {
                                            if(isset($subsidiTotals[$result->payroll_component_id])) {
                                                $subsidiTotals[$result->payroll_component_id] += $result->amount;
                                                $grandTotalSubsidi += $result->amount;
                                            }
                                            if(isset($deductionTotals[$result->payroll_component_id])) {
                                                $deductionTotals[$result->payroll_component_id] += $result->amount;
                                                $grandTotalPotongan += $result->amount;
                                            }
                                        }
                                    }
                                @endphp
                                <tr class="border-t-2 border-gray-300">
                                    <td colspan="4" class="px-2 py-2 whitespace-nowrap text-gray-900">
                                        Total
                                    </td>
                                    @foreach($subsidiColumns as $column)
                                        <td class="px-2 py-2 whitespace-nowrap text-right text-gray-900">
                                            {{ number_format($subsidiTotals[$column->id], 2) }}
                                        </td>
                                    @endforeach
                                    @if($subsidiColumns->isNotEmpty())
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-yellow-700 bg-yellow-100">
                                            {{ number_format($grandTotalSubsidi, 2) }}
                                        </td>
                                    @endif
                                    @foreach($bpjsColumns as $column)
                                        <td class="px-2 py-2 whitespace-nowrap text-right text-gray-900">
                                            {{ number_format($deductionTotals[$column->id], 2) }}
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-red-700 bg-red-100">
                                        {{ number_format($grandTotalPotongan, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
