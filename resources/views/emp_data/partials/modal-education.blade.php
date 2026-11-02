        <div x-show="educationModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="education-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="educationModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="educationModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="educationModalOpen" x-transition:enter="ease-out duration-300"
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
                                class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="education-modal-title"
                                    x-text="editingEducation ? 'Edit Riwayat Pendidikan' : 'Tambah Riwayat Pendidikan'">
                                    Tambah Riwayat Pendidikan
                                </h3>
                                <p class="text-xs text-gray-500"
                                    x-text="editingEducation ? 'Perbarui jenjang pendidikan, nama instansi akademik, dan jurusan' : 'Isi jenjang pendidikan, nama instansi akademik, dan jurusan'">
                                    Isi jenjang pendidikan, nama instansi akademik, dan jurusan
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="educationModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/education')" @submit.prevent="submitEducation($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_jenjang" :value="__('Jenjang Pendidikan Terakhir / Riwayat')" :required="true" />
                                    <x-select-input id="modal_edu_jenjang" name="jenjang" class="mt-1 block w-full"
                                        :noSelect2="true"
                                        :options="$educationLevelOptions" :selected="old('jenjang', 'D4/S1')"
                                        required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_institusi" :value="__('Nama Institusi Pendidikan')" :required="true" />
                                    <x-text-input id="modal_edu_institusi" name="institusi" type="text"
                                        class="mt-1 block w-full" :value="old('institusi')"
                                        placeholder="Contoh: Universitas Terbuka / SMK Negeri 1 Malang" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_jurusan" :value="__('Jurusan / Program Studi (Opsional)')" />
                                    <x-text-input id="modal_edu_jurusan" name="jurusan" type="text"
                                        class="mt-1 block w-full" :value="old('jurusan')"
                                        placeholder="Contoh: Teknik Informatika / Manajemen" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_nilai" :value="__('IPK / Nilai Akhir (Opsional)')" />
                                    <x-text-input id="modal_edu_nilai" name="nilai" type="text"
                                        class="mt-1 block w-full" :value="old('nilai')"
                                        placeholder="Contoh: 3.75 / 88.5" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_tahun_masuk" :value="__('Tahun Masuk (Opsional)')" />
                                    <x-text-input id="modal_edu_tahun_masuk" name="tahun_masuk" type="text"
                                        class="mt-1 block w-full font-mono" :value="old('tahun_masuk')"
                                        placeholder="Contoh: 2018" maxlength="10" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_tahun_lulus" :value="__('Tahun Lulus (Opsional)')" />
                                    <x-text-input id="modal_edu_tahun_lulus" name="tahun_lulus" type="text"
                                        class="mt-1 block w-full font-mono" :value="old('tahun_lulus')"
                                        placeholder="Contoh: 2022" maxlength="10" />
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_edu_notes" :value="__('Catatan Tambahan (Opsional)')" />
                                    <x-text-input id="modal_edu_notes" name="notes" type="text"
                                        class="mt-1 block w-full" :value="old('notes')"
                                        placeholder="Contoh: Lulusan terbaik / Cumlaude" />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="educationModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : (editingEducation ? 'Simpan Perubahan' : 'Simpan Riwayat Pendidikan')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
