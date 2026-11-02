{{-- ========================================================================= --}}
{{-- TAB CONTENT 3: KARIR (BISA MULTI & SAVE BERDIRI SENDIRI VIA MODAL AJAX) --}}
{{-- ========================================================================= --}}
<div x-show="activeTab === 'karir'" x-cloak class="space-y-6">

    {{-- LIST RIWAYAT KARIR (MULTI-ENTRY DENGAN MODAL & TAMPILAN REAL-TIME) --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
        <div
            class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
            <div class="flex items-center gap-2.5">
                <span
                    class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-briefcase"></i>
                </span>
                <div>
                    <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">RIWAYAT KARIR</h3>
                    <p class="text-xs text-gray-500">Perjalanan karir, penempatan kerja, tanggal efektif, dan jabatan</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span
                    class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                    <span x-text="careers.length"></span> Riwayat Tercatat
                </span>
                <button type="button" @click="openCareerModal()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition cursor-pointer">
                    <i class="fa-solid fa-plus"></i>
                    <span>Tambah Riwayat Karir</span>
                </button>
            </div>
        </div>

        <div class="p-6">
            <div class="space-y-4" x-show="careers.length > 0">
                <template x-for="(career, index) in careers" :key="career.id">
                    <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                        {{-- Header Card: Nomor Riwayat, Badge Posisi, & Tombol Hapus --}}
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                                    <i class="fa-solid fa-briefcase"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-800"
                                    x-text="'Riwayat Karir #' + (index + 1)"></span>
                                <template x-if="index === 0">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                        Posisi Saat Ini
                                    </span>
                                </template>
                            </div>

                            {{-- Tombol Hapus (Selalu Terlihat) --}}
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                    x-text="'Efektif: ' + (career.formatted_tanggal_efektif || career.tanggal_efektif)"></span>
                                <button type="button" @click="deleteCareer(career.id)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                    title="Hapus riwayat karir ini">
                                    <i class="fa-regular fa-trash-can"></i>
                                    <span>Hapus</span>
                                </button>
                            </div>
                        </div>

                        {{-- Grid Input Form Disabled (Max 2 Col) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label :value="__('Jabatan')" />
                                <x-text-input type="text"
                                    class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                    x-bind:value="career.jabatan || '-'" disabled />
                            </div>
                            <div>
                                <x-input-label :value="__('Status Karyawan')" />
                                <x-text-input type="text"
                                    class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                    x-bind:value="career.status_karyawan || '-'" disabled />
                            </div>
                            <div>
                                <x-input-label :value="__('Penempatan Kerja')" />
                                <x-text-input type="text"
                                    class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                    x-bind:value="career.penempatan_kerja || '-'" disabled />
                            </div>
                            <div>
                                <x-input-label :value="__('Tanggal Efektif')" />
                                <x-text-input type="text"
                                    class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                    x-bind:value="career.formatted_tanggal_efektif || career.tanggal_efektif || '-'"
                                    disabled />
                            </div>
                            <div>
                                <x-input-label :value="__('Organisasi / Divisi')" />
                                <x-text-input type="text"
                                    class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                    x-bind:value="career.organisasi || '-'" disabled />
                            </div>
                            <div>
                                <x-input-label :value="__('Pangkat')" />
                                <x-text-input type="text"
                                    class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                    x-bind:value="career.pangkat || '-'" disabled />
                            </div>
                        </div>

                        <template x-if="career.notes">
                            <div>
                                <x-input-label :value="__('Catatan')" />
                                <x-text-input type="text"
                                    class="mt-1 block w-full bg-slate-50 text-slate-700 italic cursor-not-allowed"
                                    x-bind:value="career.notes" disabled />
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div x-show="careers.length === 0" class="text-center py-10">
                <div
                    class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <p class="text-sm font-semibold text-gray-700">Belum Ada Riwayat Karir</p>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah Riwayat Karir"</strong> di atas untuk menambahkan riwayat karir baru secara fleksibel melalui modal.</p>
            </div>
        </div>
    </div>

</div>
