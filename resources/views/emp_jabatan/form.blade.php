<x-app-layout>
    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">
                        {{ $jabatan->exists ? 'Edit Jabatan' : 'Create Jabatan' }}
                    </h2>
                </div>

                <form method="POST"
                    action="{{ $jabatan->exists ? route('employee-jabatan.update', $jabatan) : route('employee-jabatan.store') }}">
                    @csrf
                    @if($jabatan->exists)
                        @method('PUT')
                    @endif

                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-12 gap-6">
                            <div class="col-span-6 my-2">
                                <x-input-label for="jabatan" :value="__('Nama Jabatan')" />
                                <x-text-input id="jabatan" name="jabatan" type="text" class="mt-1 block w-full"
                                    :value="old('jabatan', $jabatan->jabatan)" required autofocus
                                    autocomplete="jabatan" />
                                <x-input-error class="mt-2" :messages="$errors->get('jabatan')" />
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>

                        <x-secondary-link href="{{ route('employee-jabatan.index') }}">
                            {{ __('Cancel') }}
                        </x-secondary-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>