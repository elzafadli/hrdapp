        <div x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="confirmModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="confirmModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="confirmModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white p-6">
                        <div class="sm:flex sm:items-start gap-4">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-amber-100 text-amber-600 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-base font-bold text-gray-900" id="modal-title">
                                    Perubahan Belum Disimpan!
                                </h3>
                                <div class="mt-2">
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        Anda memiliki perubahan data di tab saat ini yang belum disimpan. Jika Anda
                                        berpindah tab sekarang, perubahan tersebut akan hilang.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-2">
                        <button type="button" @click="confirmSwitchTab()"
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-xs px-4 py-2 bg-amber-600 text-xs font-bold text-white hover:bg-amber-700 sm:w-auto transition">
                            Pindah & Abaikan Perubahan
                        </button>
                        <button type="button" @click="confirmModalOpen = false"
                            class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 sm:w-auto transition">
                            Tetap di Tab Ini (Simpan Dulu)
                        </button>
                    </div>
                </div>
            </div>
        </div>
