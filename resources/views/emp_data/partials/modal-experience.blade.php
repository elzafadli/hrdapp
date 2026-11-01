        <div x-show="experienceModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="exp-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="experienceModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="experienceModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="experienceModalOpen" x-transition:enter="ease-out duration-300"
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
                                class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-building-user"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="exp-modal-title"
                                    x-text="editingExperience ? 'Edit Pengalaman Kerja' : 'Tambah Pengalaman Kerja'">
                                    Tambah Pengalaman Kerja
                                </h3>
                                <p class="text-xs text-gray-500"
                                    x-text="editingExperience ? 'Perbarui data riwayat pekerjaan dan perusahaan terdahulu karyawan' : 'Isi data riwayat pekerjaan dan perusahaan terdahulu karyawan'">
                                    Isi data riwayat pekerjaan dan perusahaan terdahulu karyawan
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="experienceModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/experience')" @submit.prevent="submitExperience($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_exp_perusahaan" :value="__('Nama Perusahaan')" :required="true" />
                                    <x-text-input id="modal_exp_perusahaan" name="perusahaan" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: PT Teknologi Bangsa" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_exp_jabatan" :value="__('Jabatan / Posisi')" :required="true" />
                                    <x-text-input id="modal_exp_jabatan" name="jabatan" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: Senior Web Developer" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="exp_tanggal_mulai" :value="__('Tanggal Mulai Kerja')" />
                                    <div class="relative mt-1">
                                        <input type="text" id="exp_tanggal_mulai" name="tanggal_mulai"
                                            placeholder="Pilih Tanggal Mulai..."
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="exp_tanggal_selesai" :value="__('Tanggal Berakhir Kerja')" />
                                    <div class="relative mt-1">
                                        <input type="text" id="exp_tanggal_selesai" name="tanggal_selesai"
                                            placeholder="Pilih Tanggal Selesai..."
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_exp_gaji" :value="__('Gaji Terakhir (Opsional)')" />
                                    <x-text-input id="modal_exp_gaji" name="gaji_terakhir" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: Rp 8.000.000" />
                                </div>

                                <div class="sm:col-span-6 flex items-center pt-6">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="is_current" value="1"
                                            class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500">
                                        <span class="text-xs font-semibold text-gray-700">Masih bekerja di sini saat
                                            ini</span>
                                    </label>
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_exp_deskripsi" :value="__('Tanggung Jawab & Pekerjaan (Opsional)')" />
                                    <textarea id="modal_exp_deskripsi" name="deskripsi_pekerjaan" rows="2"
                                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-xs text-sm"
                                        placeholder="Ringkasan tugas, portofolio pencapaian, atau tanggung jawab utama..."></textarea>
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_exp_alasan" :value="__('Alasan Berhenti / Pindah (Opsional)')" />
                                    <x-text-input id="modal_exp_alasan" name="alasan_berhenti" type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Contoh: Kontrak habis / Mencari tantangan baru" />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="experienceModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : (editingExperience ? 'Simpan Perubahan' : 'Simpan Pengalaman Kerja')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
