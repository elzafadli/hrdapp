{{-- ========================================================================= --}}
{{-- TAB CONTENT 2: PENUGASAN & STATUS PAYROLL (SAVE BERDIRI SENDIRI VIA AJAX) --}}
{{-- ========================================================================= --}}
<div x-show="activeTab === 'penugasan-payroll'" x-cloak class="space-y-6">

    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
            <div class="flex items-center gap-2.5">
                <span
                    class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-briefcase"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Penugasan & Status Payroll</h2>
                    <p class="text-xs text-gray-500">Perusahaan, jabatan struktural, project, dan status PTKP pajak</p>
                </div>
            </div>
        </div>

        <form novalidate method="POST" :action="empUrl('/assignment')" @input="markDirty()" @change="markDirty()"
            @submit.prevent="submitAssignment($event)">
            @csrf

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Perusahaan (Company) --}}
                    <div>
                        <x-input-label for="assign_company_id" :value="__('Perusahaan (Company)')" />
                        <x-select-input id="assign_company_id" name="company_id" class="mt-1 block w-full"
                            :noSelect2="true"
                            :options="$companyOptions" :selected="old('company_id', $empData->company_id)"
                            placeholder="{{ __('Pilih Perusahaan') }}" />
                        <x-input-error class="mt-1.5" :messages="$errors->get('company_id')" />
                        <p x-show="fieldErrors['company_id']"
                            x-text="fieldErrors['company_id'] ? fieldErrors['company_id'][0] : ''"
                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                    </div>

                    {{-- Jabatan Karyawan --}}
                    <div>
                        <x-input-label for="assign_jabatan_id" :value="__('Jabatan Karyawan')" :required="true" />
                        <x-select-input id="assign_jabatan_id" name="jabatan_id" class="mt-1 block w-full"
                            :noSelect2="true"
                            :options="$jabatanOptions" :selected="old('jabatan_id', $empData->jabatan_id)"
                            placeholder="{{ __('Pilih Jabatan') }}" required />
                        <x-input-error class="mt-1.5" :messages="$errors->get('jabatan_id')" />
                        <p x-show="fieldErrors['jabatan_id']"
                            x-text="fieldErrors['jabatan_id'] ? fieldErrors['jabatan_id'][0] : ''"
                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                    </div>

                    {{-- Project --}}
                    <div>
                        <x-input-label for="assign_project_id" :value="__('Project')" />
                        <x-select-input id="assign_project_id" name="project_id" class="mt-1 block w-full"
                            :noSelect2="true"
                            :options="$projectOptions" :selected="old('project_id', $empData->project_id)"
                            placeholder="{{ __('Pilih Project (Opsional)') }}" />
                        <x-input-error class="mt-1.5" :messages="$errors->get('project_id')" />
                        <p x-show="fieldErrors['project_id']"
                            x-text="fieldErrors['project_id'] ? fieldErrors['project_id'][0] : ''"
                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                    </div>

                    {{-- Status PTKP --}}
                    <div>
                        <x-input-label for="assign_status_ptkp" :value="__('Status PTKP (Pajak PPh 21)')" :required="true" />
                        <x-select-input id="assign_status_ptkp" name="status_ptkp" class="mt-1 block w-full"
                            :noSelect2="true"
                            :options="$ptkpOptions" :selected="old('status_ptkp', $empData->status_ptkp)"
                            placeholder="{{ __('Pilih Status PTKP') }}" required />
                        <x-input-error class="mt-1.5" :messages="$errors->get('status_ptkp')" />
                        <p x-show="fieldErrors['status_ptkp']"
                            x-text="fieldErrors['status_ptkp'] ? fieldErrors['status_ptkp'][0] : ''"
                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        <p class="text-xs text-gray-500 mt-1.5">
                            Status PTKP menentukan skema tarif TER pajak PPh 21 pada modul penggajian.
                        </p>
                    </div>

                </div>
            </div>

            <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span class="text-xs text-gray-500 font-medium">
                    Simpan perubahan penugasan dan status payroll secara mandiri.
                </span>
                <button type="submit" :disabled="isSubmitting"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-md shadow-amber-500/20 disabled:opacity-50 transition cursor-pointer">
                    <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                    <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Penugasan & Payroll'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

