        <div x-show="careerModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="career-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="careerModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="careerModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="careerModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-briefcase"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="career-modal-title"
                                    x-text="editingCareer ? 'Edit Riwayat Karir' : 'Tambah Riwayat Karir'">
                                    Tambah Riwayat Karir
                                </h3>
                                <p class="text-xs text-gray-500"
                                    x-text="editingCareer ? 'Perbarui data riwayat jabatan, penempatan, dan status karyawan' : 'Isi data riwayat jabatan, penempatan, dan status karyawan baru'">
                                    Isi data riwayat jabatan, penempatan, dan status karyawan baru
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="careerModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/career')" @submit.prevent="submitCareer($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_status_karyawan" :value="__('Status Karyawan')" :required="true" />
                                    <x-select-input id="modal_status_karyawan" name="status_karyawan"
                                        class="mt-1 block w-full" :options="$statusKaryawanOptions"
                                        :selected="old('status_karyawan', 'PKWT')" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_penempatan_kerja" :value="__('Penempatan Kerja')" :required="true" />
                                    <x-select-input id="modal_penempatan_kerja" name="penempatan_kerja"
                                        class="mt-1 block w-full" :options="$penempatanKerjaOptions"
                                        :selected="old('penempatan_kerja', 'Baru Direkrut')" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="career_tanggal_efektif" :value="__('Tanggal Efektif')" :required="true" />
                                    <div class="relative mt-1">
                                        <input type="text" id="career_tanggal_efektif" name="tanggal_efektif"
                                            value="{{ old('tanggal_efektif', date('Y-m-d')) }}"
                                            placeholder="Pilih Tanggal Efektif..." required
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_organisasi" :value="__('Organisasi / Divisi')" />
                                    <x-select-input id="modal_organisasi" name="organisasi" class="mt-1 block w-full"
                                        :options="$organisasiOptions" :selected="old('organisasi', 'IT')" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_pangkat" :value="__('Pangkat')" />
                                    <x-select-input id="modal_pangkat" name="pangkat" class="mt-1 block w-full"
                                        :options="$pangkatOptions" :selected="old('pangkat', 'Staff')" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_career_jabatan" :value="__('Jabatan')" :required="true" />
                                    <x-text-input id="modal_career_jabatan" name="jabatan" type="text"
                                        class="mt-1 block w-full" :value="old('jabatan', $empData->jabatan?->jabatan ?? '')" placeholder="Contoh: Back-end Developer Staff" required />
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_career_notes" :value="__('Catatan Karir (Opsional)')" />
                                    <x-text-input id="modal_career_notes" name="notes" type="text"
                                        class="mt-1 block w-full" :value="old('notes')"
                                        placeholder="Contoh: Evaluasi tahunan, promosi berkala, dsb." />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="careerModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : (editingCareer ? 'Simpan Perubahan' : 'Simpan Riwayat Karir')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
