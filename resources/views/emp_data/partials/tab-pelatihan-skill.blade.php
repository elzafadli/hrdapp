            <div x-show="activeTab === 'pelatihan-skill'" x-cloak class="space-y-6">

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-award"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">PELATIHAN, TRAINING
                                    & KEAHLIAN</h3>
                                <p class="text-xs text-gray-500">Daftar kursus, keahlian khusus, dan sertifikasi profesi
                                    karyawan</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                <span x-text="trainings.length"></span> Pelatihan / Skill
                            </span>
                            <button type="button" @click="openTrainingModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Pelatihan / Skill</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4" x-show="trainings.length > 0">
                            <template x-for="(tr, index) in trainings" :key="tr.id">
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                    {{-- Header Card: Nama Pelatihan & Tombol Hapus --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fa-solid fa-award"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800"
                                                x-text="tr.nama_pelatihan"></span>
                                        </div>

                                        {{-- Tombol Edit & Hapus --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                                x-text="tr.tahun || '-'"></span>
                                            <button type="button" @click="openTrainingModal(tr)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-amber-600 hover:text-white bg-amber-50 hover:bg-amber-600 border border-amber-200 hover:border-amber-600 transition shadow-xs cursor-pointer"
                                                title="Edit data pelatihan ini">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                                <span>Edit</span>
                                            </button>
                                            <button type="button" @click="deleteTraining(tr.id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                                title="Hapus data pelatihan ini">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Nama Pelatihan / Keahlian')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                x-bind:value="tr.nama_pelatihan" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Lembaga Penyelenggara')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="tr.penyelenggara" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Tahun Pelaksanaan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="tr.tahun || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Nomor Sertifikat / Lisensi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-blue-700 font-bold cursor-not-allowed"
                                                x-bind:value="tr.nomor_sertifikat || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Masa Berlaku')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="tr.masa_berlaku || 'Seumur Hidup'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Deskripsi / Materi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-700 cursor-not-allowed"
                                                x-bind:value="tr.deskripsi || '-'" disabled />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="trainings.length === 0" class="text-center py-10">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Belum Ada Pelatihan atau Keahlian</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah Pelatihan
                                    / Skill"</strong> di atas untuk mencatat sertifikasi dan keahlian karyawan.</p>
                        </div>
                    </div>
                </div>

            </div>
