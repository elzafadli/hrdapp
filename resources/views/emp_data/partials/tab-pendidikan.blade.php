            <div x-show="activeTab === 'pendidikan'" x-cloak class="space-y-6">

                {{-- LIST RIWAYAT PENDIDIKAN (MULTI-ENTRY DENGAN MODAL & TAMPILAN REAL-TIME) --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">RIWAYAT PENDIDIKAN
                                </h3>
                                <p class="text-xs text-gray-500">Jenjang pendidikan, nama institusi akademik, jurusan,
                                    dan IPK</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                <span x-text="educations.length"></span> Riwayat Tercatat
                            </span>
                            <button type="button" @click="openEducationModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Riwayat Pendidikan</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4" x-show="educations.length > 0">
                            <template x-for="(edu, index) in educations" :key="edu.id">
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                    {{-- Header Card: Institusi & Tombol Hapus --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fa-solid fa-graduation-cap"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800"
                                                x-text="edu.institusi"></span>
                                        </div>

                                        {{-- Tombol Edit & Hapus --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                                x-text="edu.period || ((edu.tahun_masuk || '') + ' - ' + (edu.tahun_lulus || ''))"></span>
                                            <button type="button" @click="openEducationModal(edu)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-600 hover:text-white bg-emerald-50 hover:bg-emerald-600 border border-emerald-200 hover:border-emerald-600 transition shadow-xs cursor-pointer"
                                                title="Edit riwayat pendidikan ini">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" @click="deleteEducation(edu.id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                                title="Hapus riwayat pendidikan ini">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Institusi / Sekolah')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                x-bind:value="edu.institusi" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Jenjang Pendidikan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="edu.jenjang" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Jurusan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="edu.jurusan || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Periode / Tahun Masuk - Lulus')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="edu.period || ((edu.tahun_masuk || '') + ' - ' + (edu.tahun_lulus || ''))"
                                                disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Nilai Akhir / IPK')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-amber-700 font-bold cursor-not-allowed"
                                                x-bind:value="edu.nilai || '-'" disabled />
                                        </div>
                                        <div x-show="edu.notes">
                                            <x-input-label :value="__('Catatan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-700 italic cursor-not-allowed"
                                                x-bind:value="edu.notes || '-'" disabled />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="educations.length === 0" class="text-center py-10">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Belum Ada Riwayat Pendidikan</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah Riwayat
                                    Pendidikan"</strong> di atas untuk menambahkan data sekolah/universitas melalui
                                modal.</p>
                        </div>
                    </div>
                </div>

            </div>
