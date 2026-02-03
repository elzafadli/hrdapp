<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ $payrollComponent->exists ? 'Edit Payroll Component' : 'Create Payroll Component' }}
                    </h2>
                </div>

                <form method="POST"
                    action="{{ $payrollComponent->exists ? route('payroll-components.update', $payrollComponent) : route('payroll-components.store') }}">
                    @csrf
                    @if($payrollComponent->exists)
                        @method('PUT')
                    @endif

                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="col-span-6 my-2">
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name', $payrollComponent->name)" required autofocus
                                    autocomplete="name" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div class="col-span-6 my-2">
                                <x-input-label for="type" :value="__('Type')" />
                                <x-select-input id="type" name="type" :options="[
        'allowance' => 'Tunjangan',
        'deduction' => 'Potongan',
        'bpjs' => 'BPJS',
        'other' => 'Lainnya',
    ]" :selected="old('type', $payrollComponent->type)" placeholder="-- Select Type --"
                                    class="mt-1 block w-full" />
                                <x-input-error class="mt-2" :messages="$errors->get('type')" />
                            </div>

                            <div class="col-span-6 my-2">
                                <x-input-label for="payroll_reference" :value="__('Payroll Reference')" />
                                <x-select-input id="payroll_reference" name="payroll_reference"
                                    :options="$components->pluck('name', 'id')->toArray()"
                                    :selected="old('payroll_reference', $payrollComponent->payroll_reference)"
                                    placeholder="-- Select Reference --" class="mt-1 block w-full" />
                                <x-input-error class="mt-2" :messages="$errors->get('payroll_reference')" />
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>

                        <x-secondary-link href="{{ route('payroll-components.index') }}">
                            {{ __('Cancel') }}
                        </x-secondary-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>