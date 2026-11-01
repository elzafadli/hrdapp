            <div x-show="activeTab === 'pengalaman-kerja'" x-cloak class="space-y-6">

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-building-user"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">PENGALAMAN KERJA
                                </h3>
                                <p class="text-xs text-gray-500">Riwayat pekerjaan dan pengalaman profesional sebelum
                                    bergabung</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                <span x-text="experiences.length"></span> Pengalaman Tercatat
                            </span>
                            <button type="button" @click="openExperienceModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Pengalaman Kerja</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4" x-show="experiences.length > 0">
                            <template x-for="(exp, index) in experiences" :key="exp.id">
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                    {{-- Header Card: Jabatan, Status Terakhir, & Tombol Hapus --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fa-solid fa-briefcase"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800"
                                                x-text="exp.jabatan + ' di ' + exp.perusahaan"></span>
                                            <template x-if="exp.is_current">
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                    Aktif / Terakhir
                                                </span>
                                            </template>
                                        </div>

                                        {{-- Tombol Edit & Hapus --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                                x-text="exp.period"></span>
                                            <button type="button" @click="openExperienceModal(exp)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-600 hover:text-white bg-indigo-50 hover:bg-indigo-600 border border-indigo-200 hover:border-indigo-600 transition shadow-xs cursor-pointer"
                                                title="Edit riwayat pengalaman kerja ini">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" @click="deleteExperience(exp.id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                                title="Hapus riwayat pengalaman kerja ini">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Jabatan / Posisi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                x-bind:value="exp.jabatan" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Nama Perusahaan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.perusahaan" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Periode Kerja')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.period" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Gaji Terakhir')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.gaji_terakhir || '-'" disabled />
                                        </div>
                                        <div class="sm:col-span-2" x-show="exp.deskripsi_pekerjaan">
                                            <x-input-label :value="__('Tanggung Jawab & Deskripsi Pekerjaan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.deskripsi_pekerjaan || '-'" disabled />
                                        </div>
                                        <div class="sm:col-span-2" x-show="exp.alasan_berhenti">
                                            <x-input-label :value="__('Alasan Berhenti')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.alasan_berhenti || '-'" disabled />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="experiences.length === 0" class="text-center py-10">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                <i class="fa-solid fa-building-user"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Belum Ada Pengalaman Kerja</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah
                                    Pengalaman Kerja"</strong> di atas untuk mencatat riwayat pekerjaan masa lampau
                                karyawan.</p>
                        </div>
                    </div>
                </div>

            </div>
