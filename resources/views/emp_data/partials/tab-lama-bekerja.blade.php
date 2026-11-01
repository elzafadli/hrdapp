            <div x-show="activeTab === 'lama-bekerja'" x-cloak class="space-y-6">

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-regular fa-calendar-check"></i>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900">Lama Bekerja</h2>
                                <p class="text-xs text-gray-500">Tanggal mulai kerja dan perhitungan akumulasi masa
                                    kerja</p>
                            </div>
                        </div>
                    </div>

                    <form novalidate method="POST" :action="empUrl('/tenure')" @input="markDirty()" @change="markDirty()"
                        @submit.prevent="submitTenure($event)">
                        @csrf

                        <div class="p-6 space-y-6">

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-6">

                                <div class="sm:col-span-6">
                                    <x-input-label for="tenure_join_date" :value="__('Mulai Kerja (Tanggal Masuk)')" />
                                    <div class="relative mt-1">
                                        <input type="text" id="tenure_join_date" name="join_date"
                                            value="{{ old('join_date', $empData->join_date ? \Carbon\Carbon::parse($empData->join_date)->format('Y-m-d') : '') }}"
                                            placeholder="Pilih Tanggal Mulai Kerja..." required
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                    <x-input-error class="mt-1.5" :messages="$errors->get('join_date')" />
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

                        <div
                            class="px-6 py-4 bg-slate-50/60 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">
                                Simpan perubahan masa kerja secara mandiri.
                            </span>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Lama Bekerja'"></span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>
