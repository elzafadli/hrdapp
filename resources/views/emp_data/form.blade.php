<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ $empData->exists ? 'Edit Employee Data' : 'Create Employee Data' }}
                    </h2>
                </div>

                <form method="POST"
                    action="{{ $empData->exists ? route('employee.update', $empData) : route('employee.store') }}">
                    @csrf
                    @if($empData->exists)
                        @method('PUT')
                    @endif

                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-12 gap-6">

                            <!-- Name Input -->
                            <div class="col-span-12 sm:col-span-6 my-2">
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name', $empData->name)" required autofocus autocomplete="name" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <!-- User Account Select (untuk ESS & Approval) -->
                            <div class="col-span-12 sm:col-span-6 my-2">
                                <x-input-label for="user_id" :value="__('Akun Login User (Untuk Approval & ESS)')" />
                                <x-select-input id="user_id" name="user_id" class="mt-1 block w-full"
                                    :options="$userOptions" :selected="old('user_id', $empData->user_id)"
                                    placeholder="{{ __('Pilih Akun User Login') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
                                <p class="text-xs text-gray-500 mt-1">
                                    Hubungkan data karyawan ini dengan akun login user agar karyawan dapat login ke ESS dan berwenang melakukan approval.
                                </p>
                            </div>

                            <!-- Gender Select -->
                            <div class="col-span-12 sm:col-span-6 my-2">
                                <x-input-label for="gender" :value="__('Jenis Kelamin')" />
                                <x-select-input id="gender" name="gender" class="mt-1 block w-full"
                                    :options="$genderOptions" :selected="old('gender', $empData->gender ?? 'male')" />
                                <x-input-error class="mt-2" :messages="$errors->get('gender')" />
                            </div>

                            <!-- Company Select -->
                            <div class="col-span-12 sm:col-span-6 my-2">
                                <x-input-label for="company_id" :value="__('Company')" />
                                <x-select-input id="company_id" name="company_id" class="mt-1 block w-full"
                                    :options="$companyOptions" :selected="old('company_id', $empData->company_id)"
                                    placeholder="{{ __('Select Company') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('company_id')" />
                            </div>

                            <!-- Jabatan Select -->
                            <div class="col-span-12 sm:col-span-6 my-2">
                                <x-input-label for="jabatan_id" :value="__('Jabatan')" />
                                <x-select-input id="jabatan_id" name="jabatan_id" class="mt-1 block w-full"
                                    :options="$jabatanOptions" :selected="old('jabatan_id', $empData->jabatan_id)"
                                    placeholder="{{ __('Select Jabatan') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('jabatan_id')" />
                            </div>

                            <!-- Project Select -->
                            <div class="col-span-12 sm:col-span-6 my-2">
                                <x-input-label for="project_id" :value="__('Project')" />
                                <x-select-input id="project_id" name="project_id" class="mt-1 block w-full"
                                    :options="$projectOptions" :selected="old('project_id', $empData->project_id)"
                                    placeholder="{{ __('Select Project') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('project_id')" />
                            </div>

                            <!-- Status PTKP Select -->
                            <div class="col-span-12 sm:col-span-12 my-2">
                                <x-input-label for="status_ptkp" :value="__('Status PTKP')" />
                                <x-select-input id="status_ptkp" name="status_ptkp" class="mt-1 block w-full"
                                    :options="$ptkpOptions" :selected="old('status_ptkp', $empData->status_ptkp)"
                                    placeholder="{{ __('Select Status PTKP') }}" />

                                <p class="text-sm text-gray-500 mt-1">
                                    Pilih status PTKP yang sesuai. Kode (TK/K) dan jumlah tanggungan menentukan tarif
                                    TER (A/B/C) yang berlaku.
                                </p>
                                <x-input-error class="mt-2" :messages="$errors->get('status_ptkp')" />
                            </div>

                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>

                        @if($empData->exists)
                            <x-primary-link href="{{ route('emp-loans.index', ['emp_id' => $empData->id]) }}">
                                {{ __('Manage Loans') }}
                            </x-primary-link>
                        @endif

                        <x-secondary-link href="{{ route('employee.index') }}">
                            {{ __('Cancel') }}
                        </x-secondary-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
