<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payroll Slip') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="text-center mb-8">
                        <h1 class="text-2xl font-bold">PAYROLL SLIP</h1>
                        <p class="text-gray-600">{{ \DateTime::createFromFormat('!m', $month)->format('F') }}
                            {{ $year }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6 border-b pb-4">
                        <div>
                            <p class="text-sm text-gray-600">Employee Name</p>
                            <p class="font-bold">{{ $employee->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Employee ID</p>
                            <p class="font-bold">{{ $employee->nip ?? $employee->id }}</p>
                        </div>
                    </div>

                    <table class="w-full mb-6">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-300">
                                <th class="text-left py-2 px-4">COMPONENT</th>
                                <th class="text-right py-2 px-4">AMOUNT (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- INCOME -->
                            <tr>
                                <td colspan="2" class="py-2 px-4 font-bold bg-gray-50 border-t">A. INCOME / ALLOWANCES
                                </td>
                            </tr>
                            @php $totalIncome = 0; @endphp
                            @foreach($allowances as $item)
                                @php $totalIncome += $item->amount; @endphp
                                <tr>
                                    <td class="py-1 px-4 pl-8">{{ $item->payrollComponent->name }}</td>
                                    <td class="text-right py-1 px-4">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t border-gray-300 font-bold">
                                <td class="py-2 px-4 pl-8">TOTAL INCOME</td>
                                <td class="text-right py-2 px-4">{{ number_format($totalIncome, 2) }}</td>
                            </tr>

                            <!-- SUBSIDIES / BPJS (Often Company Share, not affecting THP usually, but listing them) -->
                            <!-- Depending on logic, if these are DEDUCTED from company but not employee, or are benefits. -->
                            <!-- Based on controller, they are just records. I will list them but separate. -->
                            <tr>
                                <td colspan="2" class="py-2 px-4 font-bold bg-gray-50 border-t mt-4">B. SUBSIDIES / BPJS
                                </td>
                            </tr>
                            @php $totalSubsidi = 0; @endphp
                            @foreach($subsidies->merge($bpjs) as $item)
                                @php $totalSubsidi += $item->amount; @endphp
                                <tr>
                                    <td class="py-1 px-4 pl-8">{{ $item->payrollComponent->name }}</td>
                                    <td class="text-right py-1 px-4">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t border-gray-300 font-bold">
                                <td class="py-2 px-4 pl-8">TOTAL SUBSIDIES</td>
                                <td class="text-right py-2 px-4">{{ number_format($totalSubsidi, 2) }}</td>
                            </tr>
                            <tr class="bg-blue-50 border-t border-gray-300 font-bold text-blue-900">
                                <td class="py-2 px-4 pl-8">BRUTO INCOME (A + B)</td>
                                <td class="text-right py-2 px-4">{{ number_format($totalIncome + $totalSubsidi, 2) }}</td>
                            </tr>

                            <!-- DEDUCTIONS -->
                            <tr>
                                <td colspan="2" class="py-2 px-4 font-bold bg-gray-50 border-t mt-4">C. DEDUCTIONS</td>
                            </tr>
                            @php $totalDeduction = 0; @endphp
                            @foreach($deductions as $item)
                                @php $totalDeduction += $item->amount; @endphp
                                <tr>
                                    <td class="py-1 px-4 pl-8">{{ $item->payrollComponent->name }}</td>
                                    <td class="text-right py-1 px-4">{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-t border-gray-300 font-bold">
                                <td class="py-2 px-4 pl-8">TOTAL DEDUCTIONS</td>
                                <td class="text-right py-2 px-4">{{ number_format($totalDeduction, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-200 border-t-2 border-gray-400 text-lg">
                                <td class="py-3 px-4 font-bold">TAKE HOME PAY (A - C)</td>
                                <td class="text-right py-3 px-4 font-bold">
                                    {{ number_format($totalIncome - $totalDeduction, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mt-8 text-center no-print">
                        <button onclick="window.print()"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Print Slip
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</x-app-layout>