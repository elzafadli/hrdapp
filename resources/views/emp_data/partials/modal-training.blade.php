        <div x-show="trainingModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="training-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="trainingModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="trainingModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="trainingModalOpen" x-transition:enter="ease-out duration-300"
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
                                class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-award"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="training-modal-title"
                                    x-text="editingTraining ? 'Edit Pelatihan / Training / Keahlian' : 'Tambah Pelatihan / Training / Keahlian'">
                                    Tambah Pelatihan / Training / Keahlian
                                </h3>
                                <p class="text-xs text-gray-500"
                                    x-text="editingTraining ? 'Perbarui data kursus, sertifikasi kompetensi, atau lisensi keahlian' : 'Isi data kursus, sertifikasi kompetensi, atau lisensi keahlian'">
                                    Isi data kursus, sertifikasi kompetensi, atau lisensi keahlian
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="trainingModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/training')" @submit.prevent="submitTraining($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_tr_nama" :value="__('Nama Pelatihan / Kursus / Keahlian')" :required="true" />
                                    <x-text-input id="modal_tr_nama" name="nama_pelatihan" type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Contoh: Certified Scrum Master / Fullstack Laravel Architecture"
                                        required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_penyelenggara" :value="__('Lembaga / Penyelenggara')" :required="true" />
                                    <x-text-input id="modal_tr_penyelenggara" name="penyelenggara" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: BNSP / Udemy / Dicoding"
                                        required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_tahun" :value="__('Tahun / Periode')" />
                                    <x-text-input id="modal_tr_tahun" name="tahun" type="text"
                                        class="mt-1 block w-full font-mono" placeholder="Contoh: 2024" maxlength="20" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_nomor" :value="__('Nomor Sertifikat / Lisensi (Opsional)')" />
                                    <x-text-input id="modal_tr_nomor" name="nomor_sertifikat" type="text"
                                        class="mt-1 block w-full font-mono" placeholder="Contoh: CERT-2024-88912" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_masa" :value="__('Masa Berlaku (Opsional)')" />
                                    <x-text-input id="modal_tr_masa" name="masa_berlaku" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: Seumur Hidup / 31 Des 2027" />
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_tr_deskripsi" :value="__('Keterangan / Keahlian yang Diperoleh (Opsional)')" />
                                    <x-text-input id="modal_tr_deskripsi" name="deskripsi" type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Contoh: Menguasai Docker, CI/CD pipeline, dan unit testing" />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="trainingModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-md shadow-amber-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : (editingTraining ? 'Simpan Perubahan' : 'Simpan Pelatihan & Skill')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
