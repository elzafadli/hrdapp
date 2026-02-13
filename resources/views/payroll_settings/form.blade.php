<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ $payrollSetting->exists ? 'Edit Payroll Setting' : 'Create Payroll Setting' }}
                    </h2>
                </div>

                <form method="POST"
                    action="{{ $payrollSetting->exists ? route('payroll-settings.update', $payrollSetting) : route('payroll-settings.store') }}">
                    @csrf
                    @if($payrollSetting->exists)
                        @method('PUT')
                    @endif

                    <div class="p-6 text-gray-900">
                        <!-- Name & Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name', $payrollSetting->name)" required autofocus />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div class="flex items-center mt-6">
                                <label for="is_aktif" class="inline-flex items-center">
                                    <input id="is_aktif" type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        name="is_aktif" value="1" {{ old('is_aktif', $payrollSetting->exists ? $payrollSetting->is_aktif : true) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-600">{{ __('Is Active') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Details Table -->
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-blue-50 px-4 py-2 border-b">
                                <h3 class="font-semibold text-blue-800">Details</h3>
                            </div>
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                    <tr>
                                        <th class="px-4 py-3 text-center">Action</th>
                                        <th class="px-4 py-3">Component *</th>
                                        <th class="px-4 py-3">Base Amount</th>
                                        <th class="px-4 py-3">Value (%)</th>
                                        <th class="px-4 py-3 text-center">Is THR</th>
                                        <th class="px-4 py-3 text-center">Is Variable</th>
                                        <th class="px-4 py-3">Order</th>
                                    </tr>
                                </thead>
                                <tbody id="details-body">
                                    <!-- Rows will be added here via JS -->
                                </tbody>
                            </table>
                            <div class="p-3 bg-gray-50 border-t text-right">
                                <button type="button" id="add-detail-btn"
                                    class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                    + Add Item
                                </button>
                            </div>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('details')" />
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>

                        <x-secondary-link href="{{ route('payroll-settings.index') }}">
                            {{ __('Cancel') }}
                        </x-secondary-link>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <template id="row-template">
            <tr class="bg-white border-b hover:bg-gray-50 detail-row">
                <td class="px-4 py-2 text-center flex items-center justify-center gap-2">
                    <div class="flex flex-col gap-1">
                        <button type="button" class="text-gray-500 hover:text-gray-700 move-up" title="Move Up">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>
                        <button type="button" class="text-gray-500 hover:text-gray-700 move-down" title="Move Down">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                    <button type="button" class="text-red-500 hover:text-red-700 remove-row" title="Remove">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </td>
                <td class="px-4 py-2">
                    <select name="details[__INDEX__][payroll_component_id]"
                        class="component-select border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm py-1.5 text-sm w-full"
                        required>
                        <option value="">-- Select Component --</option>
                        @foreach($payrollComponents as $comp)
                            <option value="{{ $comp->id }}" data-type="{{ $comp->type }}">{{ $comp->name }} ({{ $comp->type }})
                            </option>
                        @endforeach
                    </select>
                </td>
                <td class="px-4 py-2">
                    <x-text-input name="details[__INDEX__][base_amount]" type="text" class="w-full text-right currency-mask"
                        placeholder="Base Amount" />
                </td>
                <td class="px-4 py-2">
                    <x-text-input name="details[__INDEX__][value]" type="number" step="0.01" class="w-full"
                        placeholder="%" />
                </td>
                <td class="px-4 py-2 text-center">
                    <input type="checkbox" name="details[__INDEX__][is_thr]" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                </td>
                <td class="px-4 py-2 text-center">
                    <input type="checkbox" name="details[__INDEX__][is_variable]" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                </td>
                <td class="px-4 py-2">
                    <x-text-input name="details[__INDEX__][urutan]" type="number" class="w-full" />
                </td>
            </tr>
        </template>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const detailsBody = document.getElementById('details-body');
                const addBtn = document.getElementById('add-detail-btn');
                const template = document.getElementById('row-template');

                // Initial data from server (for edit mode)
                let detailsData = @json(old('details', $payrollSetting->details ?? []));

                // If empty (create mode), add one empty row
                if (detailsData.length === 0) {
                    addRow();
                } else {
                    detailsData.forEach((data, index) => {
                        addRow(data, index);
                    });
                }

                addBtn.addEventListener('click', function () {
                    addRow();
                });

                function addRow(data = {}, index = null) {
                    const idx = index !== null ? index : document.querySelectorAll('.detail-row').length;
                    const clone = template.content.cloneNode(true);
                    const tr = clone.querySelector('tr');

                    // Replace placeholders in names
                    tr.querySelectorAll('[name*="__INDEX__"]').forEach(el => {
                        el.name = el.name.replace('__INDEX__', idx);
                    });

                    // Set values
                    const componentSelect = tr.querySelector(`[name="details[${idx}][payroll_component_id]"]`);
                    const baseAmountInput = tr.querySelector(`[name="details[${idx}][base_amount]"]`);
                    const valueInput = tr.querySelector(`[name="details[${idx}][value]"]`);
                    const isThrCheckbox = tr.querySelector(`[name="details[${idx}][is_thr]"]`);
                    const isVariableCheckbox = tr.querySelector(`[name="details[${idx}][is_variable]"]`);
                    const urutanInput = tr.querySelector(`[name="details[${idx}][urutan]"]`);

                    if (data.payroll_component_id) componentSelect.value = data.payroll_component_id;
                    if (baseAmountInput) baseAmountInput.value = data.base_amount || '';
                    if (isThrCheckbox) isThrCheckbox.checked = data.is_thr || false;
                    if (isVariableCheckbox) isVariableCheckbox.checked = data.is_variable || false;

                    detailsBody.appendChild(tr);

                    if (baseAmountInput) {
                        $(baseAmountInput).inputmask({
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
                    }

                    valueInput.value = data.value || '';
                    urutanInput.value = data.urutan || (idx + 1);

                    // Handle remove button
                    tr.querySelector('.remove-row').addEventListener('click', function () {
                        tr.remove();
                        updateRowIndices();
                    });

                    // Handle Move Up
                    tr.querySelector('.move-up').addEventListener('click', function () {
                        if (tr.previousElementSibling) {
                            tr.parentNode.insertBefore(tr, tr.previousElementSibling);
                            updateRowIndices();
                        }
                    });

                    // Handle Move Down
                    tr.querySelector('.move-down').addEventListener('click', function () {
                        if (tr.nextElementSibling) {
                            tr.parentNode.insertBefore(tr.nextElementSibling, tr);
                            updateRowIndices();
                        }
                    });

                    // Removed appendChild from here
                }

                function updateRowIndices() {
                    const rows = detailsBody.querySelectorAll('.detail-row');
                    rows.forEach((row, index) => {
                        const idx = index; // 0-based index

                        // Update field names to ensure array structure is maintained correctly
                        // Also update the urutan/order value

                        // inputs
                        const inputs = row.querySelectorAll('input, select');
                        inputs.forEach(input => {
                            const name = input.name;
                            // Replace the index in the name attribute: details[OLD_INDEX][field] -> details[NEW_INDEX][field]
                            const newName = name.replace(/details\[\d+\]/, `details[${idx}]`);
                            input.name = newName;

                            // If this is the 'urutan' field, update its value
                            if (name.includes('[urutan]')) {
                                input.value = idx + 1;
                            }
                        });
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>