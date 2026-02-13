<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ $empLoan->exists ? 'Edit Employee Loan' : 'Create Employee Loan' }}
                    </h2>
                </div>

                <form method="POST"
                    action="{{ $empLoan->exists ? route('emp-loans.update', $empLoan) : route('emp-loans.store') }}">
                    @csrf
                    @if($empLoan->exists)
                        @method('PUT')
                    @endif

                    <div class="p-6 text-gray-900">
                        <!-- Employee Select -->
                         
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <!-- Loan Date -->
                                <div>
                                    <x-input-label for="loan_date" :value="__('Tanggal Pinjaman')" />
                                    <x-text-input id="loan_date" name="loan_date" type="date" class="mt-1 block w-full"
                                        :value="old('loan_date', $empLoan->loan_date ? $empLoan->loan_date->format('Y-m-d') : date('Y-m-d'))" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('loan_date')" />
                                </div>
                            </div>
                            <div>
                                <x-input-label for="emp_id" :value="__('Employee')" />
                                <x-select-input id="emp_id" name="emp_id" class="mt-1 block w-full"
                                    :options="$employees"
                                    :selected="old('emp_id', $empLoan->emp_id ?? $selectedEmpId)"
                                    placeholder="{{ __('Select Employee') }}" required />
                                <x-input-error class="mt-2" :messages="$errors->get('emp_id')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Amount -->
                            <div>
                                <x-input-label for="amount" :value="__('Jumlah Pinjaman')" />
                                <x-currency-input id="amount" name="amount" class="mt-1 block w-full"
                                    :value="old('amount', $empLoan->amount)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                            </div>

                            <!-- Duration -->
                            <div>
                                <x-input-label for="duration" :value="__('Durasi (Bulan)')" />
                                <x-text-input id="duration" name="duration" type="number" min="1" class="mt-1 block w-full"
                                    :value="old('duration', $empLoan->duration)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('duration')" />
                            </div>
                        </div>

                        <!-- Installment Amount (Auto-calculated) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <x-input-label for="installment_amount" :value="__('Cicilan Per Bulan (Auto)')" />
                                <x-currency-input id="installment_amount" name="installment_amount" class="mt-1 block w-full bg-gray-100"
                                    :value="old('installment_amount', $empLoan->installment_amount)" readonly />
                                <x-input-error class="mt-2" :messages="$errors->get('installment_amount')" />
                            </div>
                        </div>


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Start Date - Month & Year -->
                            <div>
                                <x-month-year-selector
                                    label="Tanggal Mulai Cicilan"
                                    namePrefix="start"
                                    :defaultMonth="$empLoan->start_date ? $empLoan->start_date->format('m') : date('m')"
                                    :defaultYear="$empLoan->start_date ? $empLoan->start_date->format('Y') : date('Y')"
                                />
                                <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >{{ old('description', $empLoan->description) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <x-input-label for="status" :value="__('Status')" />
                            <x-select-input id="status" name="status" class="mt-1 block w-full"
                                :options="['open' => 'Open', 'closed' => 'Closed']"
                                :selected="old('status', $empLoan->status ?? 'open')" />
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                            <a href="{{ $empLoan->exists 
                                    ? route('emp-loans.index', ['emp_id' => $empLoan->emp_id]) 
                                    : ($selectedEmpId ? route('emp-loans.index', ['emp_id' => $selectedEmpId]) : route('emp-loans.index')) }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amount');
            const durationInput = document.getElementById('duration');
            const installmentInput = document.getElementById('installment_amount');
            const startMonthSelect = document.getElementById('start_month');
            const startYearSelect = document.getElementById('start_year');
            const startDateInput = document.getElementById('start_date');

            function updateStartDate() {
                const month = startMonthSelect.value;
                const year = startYearSelect.value;
                if (month && year) {
                    startDateInput.value = `${year}-${String(month).padStart(2, '0')}-01`;
                } else {
                    startDateInput.value = '';
                }
            }

            function calculateInstallment() {
                const amount = parseFloat(amountInput.value.replace(/[^0-9.-]+/g, '')) || 0;
                const duration = parseInt(durationInput.value) || 0;

                if (amount > 0 && duration > 0) {
                    const installment = amount / duration;
                    installmentInput.value = installment.toFixed(2);
                    installmentInput.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                    installmentInput.value = '';
                    installmentInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            amountInput.addEventListener('input', calculateInstallment);
            durationInput.addEventListener('input', calculateInstallment);
            startMonthSelect.addEventListener('change', updateStartDate);
            startYearSelect.addEventListener('change', updateStartDate);

            updateStartDate();
        });
    </script>
</x-app-layout>
