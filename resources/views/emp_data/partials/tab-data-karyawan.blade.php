{{-- ========================================================================= --}}
{{-- TAB CONTENT 1: DATA KARYAWAN (SAVE BERDIRI SENDIRI VIA AJAX) --}}
{{-- ========================================================================= --}}
<div x-show="activeTab === 'data-karyawan'" class="space-y-6">
    <form novalidate method="POST" :action="empExists ? empUrl() : '{{ route('employee.store') }}'"
        enctype="multipart/form-data" @input="markDirty()" @change="markDirty()"
        @submit.prevent="submitDataKaryawan($event)">
        @csrf

        <div class="space-y-6">

            {{-- CARD 1A: FOTO PROFIL & IDENTITAS UTAMA --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                            <i class="fa-regular fa-image"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Foto Profil & Akun Karyawan</h2>
                            <p class="text-xs text-gray-500">Foto ini hanya bisa diubah oleh tim HRD</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                        {{-- Avatar Preview --}}
                        <div class="flex flex-col items-center gap-2">
                            <div
                                class="relative w-28 h-28 rounded-full border-4 border-white shadow-md bg-slate-100 flex items-center justify-center overflow-hidden">
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" alt="Foto Profil"
                                        class="w-full h-full object-cover">
                                </template>
                                <template x-if="!photoPreview">
                                    <div
                                        class="w-full h-full flex flex-col items-center justify-center bg-blue-50 text-blue-600">
                                        <i class="fa-regular fa-user text-3xl"></i>
                                    </div>
                                </template>
                            </div>
                            <span class="text-[11px] text-gray-500 font-medium text-center max-w-[160px]">
                                Maksimal 2 MB (JPG, PNG, WebP)
                            </span>
                        </div>

                        {{-- Upload & Info Input --}}
                        <div class="flex-1 w-full space-y-4">
                            <div>
                                <x-input-label for="photo" :value="__('Unggah Foto Profil')" />
                                <input type="file" id="photo" name="photo" accept="image/*" @change="
                                        const file = $event.target.files[0];
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => { photoPreview = e.target.result; };
                                            reader.readAsDataURL(file);
                                        }
                                        markDirty();
                                    "
                                    class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-200 rounded-xl bg-white p-1" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('photo')" />
                                <p x-show="fieldErrors['photo']"
                                    x-text="fieldErrors['photo'] ? fieldErrors['photo'][0] : ''"
                                    class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                <p
                                    class="text-[11px] text-amber-600 mt-1 flex items-center gap-1 font-medium">
                                    <i class="fa-solid fa-circle-info"></i> Foto ini hanya bisa diubah oleh
                                    tim HRD
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="nik_karyawan" :value="__('ID Karyawan (Nomor Induk Karyawan)')" />
                                    <x-text-input id="nik_karyawan" name="nik_karyawan" type="text"
                                        class="mt-1 block w-full font-mono" :value="old('nik_karyawan', $empData->nik_karyawan ?? $empData->id)"
                                        placeholder="Contoh: 74 atau EMP-0074" />
                                    <x-input-error class="mt-1.5"
                                        :messages="$errors->get('nik_karyawan')" />
                                    <p x-show="fieldErrors['nik_karyawan']"
                                        x-text="fieldErrors['nik_karyawan'] ? fieldErrors['nik_karyawan'][0] : ''"
                                        class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between">
                                        <x-input-label for="user_id" :value="__('Akun Login User (Untuk ESS & Approval)')" />
                                        <span
                                            class="text-[10px] font-semibold text-gray-400 bg-gray-100 px-1.5 py-0.2 rounded">Opsional</span>
                                    </div>
                                    <x-select-input id="user_id" name="user_id" class="mt-1 block w-full"
                                        :options="$userOptions" :selected="old('user_id', $empData->user_id)"
                                        placeholder="{{ __('Pilih Akun User Login (Bisa Dikosongkan)') }}" />
                                    <x-input-error class="mt-1.5" :messages="$errors->get('user_id')" />
                                    <p x-show="fieldErrors['user_id']"
                                        x-text="fieldErrors['user_id'] ? fieldErrors['user_id'][0] : ''"
                                        class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        Bisa dikosongkan terlebih dahulu jika akun user login belum dibuat.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <x-input-label for="email" :value="__('Email Resmi Karyawan')" />
                                        <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded flex items-center gap-1">
                                            <i class="fa-solid fa-lock text-[9px]"></i> Dikelola HRD
                                        </span>
                                    </div>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                            <i class="fa-regular fa-envelope text-sm"></i>
                                        </div>
                                        <x-text-input id="email" name="email" type="email"
                                            class="block w-full pl-10" :value="old('email', $empData->email ?? $empData->user?->email)"
                                            placeholder="Contoh: karyawan@perusahaan.com" />
                                    </div>
                                    <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
                                    <p x-show="fieldErrors['email']"
                                        x-text="fieldErrors['email'] ? fieldErrors['email'][0] : ''"
                                        class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        Alamat email resmi karyawan.
                                    </p>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between">
                                        <x-input-label for="phone" :value="__('No. Handphone / WhatsApp')" />
                                        <span class="text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                                            <i class="fa-solid fa-arrows-rotate text-[8px]"></i> Auto-sync User
                                        </span>
                                    </div>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                            <i class="fa-solid fa-phone text-sm"></i>
                                        </div>
                                        <x-text-input id="phone" name="phone" type="text"
                                            class="block w-full pl-10 font-mono" :value="old('phone', $empData->phone ?? $empData->user?->phone)"
                                            placeholder="Contoh: 081234567890" />
                                    </div>
                                    <x-input-error class="mt-1.5" :messages="$errors->get('phone')" />
                                    <p x-show="fieldErrors['phone']"
                                        x-text="fieldErrors['phone'] ? fieldErrors['phone'][0] : ''"
                                        class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    <p class="text-[11px] text-gray-400 mt-1">
                                        Nomor kontak aktif. Otomatis disinkronkan ke akun user login.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 1B: DATA PRIBADI --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2.5 bg-slate-50/60">
                    <span
                        class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                        <i class="fa-regular fa-id-card"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Data Pribadi Karyawan</h2>
                        <p class="text-xs text-gray-500">Informasi identitas, kewarganegaraan, dan kependudukan</p>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">

                        {{-- Nama Lengkap --}}
                        <div class="sm:col-span-12">
                            <x-input-label for="name" :value="__('Nama Lengkap (Sesuai KTP)')" :required="true" />
                            <x-text-input id="name" name="name" type="text"
                                class="mt-1 block w-full text-base font-semibold" :value="old('name', $empData->name)" required autofocus placeholder="Contoh: Budi" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
                            <p x-show="fieldErrors['name']"
                                x-text="fieldErrors['name'] ? fieldErrors['name'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Jenis Kelamin --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="gender" :value="__('Jenis Kelamin')" />
                            <x-select-input id="gender" name="gender" class="mt-1 block w-full"
                                :options="$genderOptions" :selected="old('gender', $empData->gender ?? 'male')" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('gender')" />
                            <p x-show="fieldErrors['gender']"
                                x-text="fieldErrors['gender'] ? fieldErrors['gender'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Kewarganegaraan --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="citizenship" :value="__('Kewarganegaraan')" />
                            <x-select-input id="citizenship" name="citizenship" class="mt-1 block w-full"
                                :options="$citizenshipOptions" :selected="old('citizenship', $empData->citizenship ?? 'WNI')" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('citizenship')" />
                            <p x-show="fieldErrors['citizenship']"
                                x-text="fieldErrors['citizenship'] ? fieldErrors['citizenship'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Identitas / No. KTP --}}
                        <div class="sm:col-span-12">
                            <x-input-label for="nik_ktp" :value="__('Identitas (Nomor KTP / NIK)')" />
                            <div class="relative mt-1">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fa-regular fa-address-card text-sm"></i>
                                </div>
                                <x-text-input id="nik_ktp" name="nik_ktp" type="text"
                                    class="block w-full pl-9 font-mono tracking-wider"
                                    :value="old('nik_ktp', $empData->nik_ktp)"
                                    placeholder="Contoh: -" maxlength="25" />
                            </div>
                            <x-input-error class="mt-1.5" :messages="$errors->get('nik_ktp')" />
                            <p x-show="fieldErrors['nik_ktp']"
                                x-text="fieldErrors['nik_ktp'] ? fieldErrors['nik_ktp'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Tempat Lahir --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="birth_place" :value="__('Tempat Lahir')" />
                            <x-text-input id="birth_place" name="birth_place" type="text"
                                class="mt-1 block w-full" :value="old('birth_place', $empData->birth_place)"
                                placeholder="Contoh: Pemalang" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('birth_place')" />
                            <p x-show="fieldErrors['birth_place']"
                                x-text="fieldErrors['birth_place'] ? fieldErrors['birth_place'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Tanggal Lahir --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="birth_date" :value="__('Tanggal Lahir')" />
                            <div class="relative mt-1">
                                <input type="text" id="birth_date" name="birth_date"
                                    value="{{ old('birth_date', $empData->birth_date ? \Carbon\Carbon::parse($empData->birth_date)->format('Y-m-d') : '') }}"
                                    placeholder="Pilih Tanggal Lahir..."
                                    class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                            </div>
                            <x-input-error class="mt-1.5" :messages="$errors->get('birth_date')" />
                            <p x-show="fieldErrors['birth_date']"
                                x-text="fieldErrors['birth_date'] ? fieldErrors['birth_date'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Status Perkawinan --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="marital_status" :value="__('Status Perkawinan')" />
                            <x-select-input id="marital_status" name="marital_status"
                                class="mt-1 block w-full" :options="$maritalStatusOptions"
                                :selected="old('marital_status', $empData->marital_status)"
                                placeholder="Pilih Status..." />
                            <x-input-error class="mt-1.5" :messages="$errors->get('marital_status')" />
                            <p x-show="fieldErrors['marital_status']"
                                x-text="fieldErrors['marital_status'] ? fieldErrors['marital_status'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Agama --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="religion" :value="__('Agama')" />
                            <x-select-input id="religion" name="religion" class="mt-1 block w-full"
                                :options="$religionOptions" :selected="old('religion', $empData->religion)"
                                placeholder="Pilih Agama..." />
                            <x-input-error class="mt-1.5" :messages="$errors->get('religion')" />
                            <p x-show="fieldErrors['religion']"
                                x-text="fieldErrors['religion'] ? fieldErrors['religion'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Golongan Darah --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="blood_type" :value="__('Golongan Darah')" />
                            <x-select-input id="blood_type" name="blood_type" class="mt-1 block w-full"
                                :options="$bloodTypeOptions" :selected="old('blood_type', $empData->blood_type ?? '-')" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('blood_type')" />
                            <p x-show="fieldErrors['blood_type']"
                                x-text="fieldErrors['blood_type'] ? fieldErrors['blood_type'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- CARD 1C: PENUGASAN & STATUS PAYROLL --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-briefcase"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Penugasan & Status Payroll</h2>
                            <p class="text-xs text-gray-500">Penempatan perusahaan, jabatan struktural, project, dan status PTKP pajak</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">

                        {{-- Perusahaan (Company) --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="company_id" :value="__('Perusahaan (Company)')" />
                            <x-select-input id="company_id" name="company_id" class="mt-1 block w-full"
                                :options="$companyOptions" :selected="old('company_id', $empData->company_id)"
                                placeholder="{{ __('Pilih Perusahaan') }}" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('company_id')" />
                            <p x-show="fieldErrors['company_id']"
                                x-text="fieldErrors['company_id'] ? fieldErrors['company_id'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Jabatan Karyawan --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="jabatan_id" :value="__('Jabatan Karyawan')" :required="true" />
                            <x-select-input id="jabatan_id" name="jabatan_id" class="mt-1 block w-full"
                                :options="$jabatanOptions" :selected="old('jabatan_id', $empData->jabatan_id)"
                                placeholder="{{ __('Pilih Jabatan') }}" required />
                            <x-input-error class="mt-1.5" :messages="$errors->get('jabatan_id')" />
                            <p x-show="fieldErrors['jabatan_id']"
                                x-text="fieldErrors['jabatan_id'] ? fieldErrors['jabatan_id'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Project --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="project_id" :value="__('Project')" />
                            <div class="mt-1">
                                <x-project-modal-picker id="project_id" name="project_id"
                                    :selected="old('project_id', $empData->project_id)"
                                    :projects="$projects ?? null"
                                    placeholder="{{ __('Pilih Project (Opsional)') }}" />
                            </div>
                            <x-input-error class="mt-1.5" :messages="$errors->get('project_id')" />
                            <p x-show="fieldErrors['project_id']"
                                x-text="fieldErrors['project_id'] ? fieldErrors['project_id'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                        </div>

                        {{-- Status PTKP --}}
                        <div class="sm:col-span-6">
                            <x-input-label for="status_ptkp" :value="__('Status PTKP (Pajak PPh 21)')" :required="true" />
                            <x-select-input id="status_ptkp" name="status_ptkp" class="mt-1 block w-full"
                                :options="$ptkpOptions" :selected="old('status_ptkp', $empData->status_ptkp ?? 'TK0')"
                                placeholder="{{ __('Pilih Status PTKP') }}" required />
                            <x-input-error class="mt-1.5" :messages="$errors->get('status_ptkp')" />
                            <p x-show="fieldErrors['status_ptkp']"
                                x-text="fieldErrors['status_ptkp'] ? fieldErrors['status_ptkp'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                            <p class="text-[11px] text-gray-400 mt-1 sm:col-span-12">
                                Status PTKP menentukan skema tarif TER pajak PPh 21 pada modul penggajian.
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- CARD 1D: LAMA BEKERJA --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                            <i class="fa-regular fa-calendar-check"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Lama Bekerja</h2>
                            <p class="text-xs text-gray-500">Tanggal mulai kerja dan perhitungan akumulasi masa kerja</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-6">

                        <div class="sm:col-span-6">
                            <x-input-label for="tenure_join_date" :value="__('Mulai Kerja (Tanggal Masuk)')" />
                            <div class="relative mt-1">
                                <input type="text" id="tenure_join_date" name="join_date"
                                    value="{{ old('join_date', $empData->join_date ? \Carbon\Carbon::parse($empData->join_date)->format('Y-m-d') : '') }}"
                                    placeholder="Pilih Tanggal Mulai Kerja..."
                                    class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                            </div>
                            <x-input-error class="mt-1.5" :messages="$errors->get('join_date')" />
                            <p x-show="fieldErrors['join_date']"
                                x-text="fieldErrors['join_date'] ? fieldErrors['join_date'][0] : ''"
                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                            <p class="text-xs text-gray-400 mt-1">
                                Tanggal awal karyawan pertama kali terhitung aktif bekerja.
                            </p>
                        </div>

                        <div class="sm:col-span-6">
                            <x-input-label :value="__('Masa Kerja Terhitung')" />
                            <x-text-input type="text"
                                class="mt-1 block w-full font-bold text-blue-700 bg-slate-50 cursor-not-allowed"
                                x-bind:value="tenureDisplay || '{{ addslashes($empData->masa_kerja ?? '-') }}'"
                                placeholder="Otomatis dihitung sistem..." disabled />
                            <p class="text-xs text-gray-400 mt-1">
                                Dihitung otomatis secara real-time dari tanggal mulai kerja.
                            </p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Tombol Aksi Mandiri Tab 1 --}}
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('employee.index') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition">
                    <i class="fa-solid fa-xmark text-gray-400"></i>
                    Batal
                </a>

                <button type="submit" :disabled="isSubmitting"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition cursor-pointer">
                    <span x-show="!isSubmitting" class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-check"></i>
                        <span x-text="empExists ? 'Simpan Data Karyawan' : 'Simpan & Lanjutkan'"></span>
                    </span>
                    <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Menyimpan...</span>
                    </span>
                </button>
            </div>

        </div>
    </form>
</div>
