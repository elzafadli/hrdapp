<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        Edit Allowances for {{ $employee->name }}
                    </h2>
                </div>

                <form method="POST" action="{{ route('emp-allowance.update', $employee->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="p-6 text-gray-900">
                        <div class="border rounded-lg overflow-hidden">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Component Name</th>
                                        <th class="px-6 py-3">Type</th>
                                        <th class="px-6 py-3">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($components as $payrollComponent)
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                                {{ $payrollComponent->name }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    {{ $payrollComponent->type === 'allowance' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $payrollComponent->type === 'deduction' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $payrollComponent->type === 'bpjs' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $payrollComponent->type === 'other' ? 'bg-gray-100 text-gray-800' : '' }}
                                                ">
                                                    {{ ucfirst($payrollComponent->type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <x-text-input id="component_{{ $payrollComponent->id }}" 
                                                    name="allowances[{{ $payrollComponent->id }}]" 
                                                    type="text" 
                                                    class="block w-full currency-mask text-right"
                                                    :value="old('allowances.' . $payrollComponent->id, $allowances[$payrollComponent->id] ?? '')" 
                                                    placeholder="0.00"
                                                />
                                                <x-input-error class="mt-2" :messages="$errors->get('allowances.'.$payrollComponent->id)" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center gap-4">
                        <x-primary-button>{{ __('Save Allowances') }}</x-primary-button>

                        <x-secondary-link href="{{ route('emp-allowance.index') }}">
                            {{ __('Cancel') }}
                        </x-secondary-link>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
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
        </script>
    @endpush
</x-app-layout>
