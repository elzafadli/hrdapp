<x-app-layout>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Process Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ __('Process Payroll') }}
                    </h2>
                </div>
                <div class="px-6 py-3 text-gray-900">
                    <form action="{{ route('payroll-results.process') }}" method="POST" class="space-y-3">
                        @csrf

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700">
                                    Month
                                </label>
                                <select id="month" name="month" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border">
                                    <option value="">Select Month</option>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                            {{ \DateTime::createFromFormat('!m', $m)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700">
                                    Year
                                </label>
                                <input type="number" name="year" id="year" value="{{ $year }}" required
                                    min="2020" max="2099"
                                    class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md border">
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="is_thr" id="is_thr" value="1"
                                        class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                        onchange="updateThrMessage()">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_thr" class="font-medium text-gray-700">
                                        Process THR (Tunjangan Hari Raya)
                                    </label>
                                    <p class="text-gray-500">
                                        Check this box if processing holiday allowance instead of regular payroll
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3">
                            <a href="{{ route('payroll-results.index') }}"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <button type="submit" id="submitBtn"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Process Payroll
                            </button>
                        </div>

                        <script>
                            let isSubmitting = false;

                            function updateThrMessage() {
                                const isThr = document.getElementById('is_thr').checked;
                                const submitBtn = document.getElementById('submitBtn');

                                if (isThr) {
                                    submitBtn.textContent = 'Process THR';
                                } else {
                                    submitBtn.textContent = 'Process Payroll';
                                }
                            }

                            document.querySelector('form').addEventListener('submit', function(e) {
                                // Prevent duplicate submission
                                if (isSubmitting) {
                                    e.preventDefault();
                                    return false;
                                }

                                const isThr = document.getElementById('is_thr').checked;
                                const message = isThr
                                    ? 'Are you sure you want to process THR for selected month and year? This will replace any existing THR data for this period.'
                                    : 'Are you sure you want to process payroll for selected month and year? This will replace any existing payroll data for this period.';

                                if (!confirm(message)) {
                                    e.preventDefault();
                                    return false;
                                }

                                // Set submitting state
                                isSubmitting = true;

                                const submitBtn = document.getElementById('submitBtn');
                                const originalText = submitBtn.textContent;

                                // Disable button and show loading state
                                submitBtn.disabled = true;
                                submitBtn.textContent = 'Processing...';
                                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                                // Re-enable button if navigation doesn't happen (for error cases)
                                setTimeout(() => {
                                    isSubmitting = false;
                                    submitBtn.disabled = false;
                                    submitBtn.textContent = originalText;
                                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                                }, 10000);
                            });

                            // Prevent back button from resubmitting form data
                            if (window.performance && window.performance.navigation.type === 1) {
                                // Page was reloaded
                                history.replaceState(null, null, ' ');
                            }
                        </script>
                    </form>
                </div>
            </div>

            <!-- Payroll Results Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    @foreach($payrollColumns as $column)
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $column->name }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($groupedResults as $empId => $empResults)
                                    @php
                                        $employee = $empResults->first()->employee;
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('payroll-results.slip', ['emp_id' => $employee->id, 'month' => $month, 'year' => $year]) }}"
                                                target="_blank"
                                                class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition duration-150 ease-in-out">
                                                View Slip
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                            {{ $employee->name ?? 'N/A' }}
                                        </td>
                                        @foreach($payrollColumns as $column)
                                            @php
                                                $result = $empResults->firstWhere('payroll_component_id', $column->id);
                                            @endphp
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $result ? number_format($result->amount, 2) : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-center"
                                            colspan="{{ $payrollColumns->count() + 2 }}">
                                            No data available for selected month and year
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
