            <div x-show="activeTab === 'bpjs'" x-cloak class="space-y-6">

                <form novalidate method="POST" :action="empUrl('/bpjs')" @input="markDirty()" @change="markDirty()"
                    @submit.prevent="submitBpjs($event)"
                    class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                    @csrf

                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <i class="fa-solid fa-shield-heart text-blue-600"></i>
                                    Kelola Data BPJS Karyawan
                                </h2>
                                <p class="text-xs text-gray-500 mt-1">
                                    Nomor kepesertaan atau nominal komponen BPJS Kesehatan dan Ketenagakerjaan karyawan.
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                Subsidi Perusahaan
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        @if(empty($bpjsComponents) || $bpjsComponents->isEmpty())
                            <div
                                class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                                <i class="fa-solid fa-circle-exclamation text-amber-500"></i>
                                <span>Belum ada komponen payroll bertipe <strong>subsidi</strong> (BPJS). Silakan
                                    konfigurasi komponen subsidi di modul Komponen Payroll.</span>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($bpjsComponents as $payrollComp)
                                    <div
                                        class="p-4 rounded-xl border border-gray-100 bg-slate-50/50 hover:bg-white hover:border-blue-200 transition">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                                            <div class="sm:col-span-5">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold shrink-0">
                                                        <i
                                                            class="fa-solid {{ str_contains(strtolower($payrollComp->name), 'kesehatan') ? 'fa-heart-pulse' : 'fa-briefcase' }}"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-xs font-bold text-gray-900">{{ $payrollComp->name }}
                                                        </h4>
                                                        <span
                                                            class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-200 text-slate-700">
                                                            {{ ucfirst($payrollComp->type) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-7">
                                                <x-input-label for="bpjs_{{ $payrollComp->id }}" :value="__('Nomor Kepesertaan / Nilai')" />
                                                <x-text-input id="bpjs_{{ $payrollComp->id }}"
                                                    name="bpjs[{{ $payrollComp->id }}]" type="text" class="mt-1 block w-full"
                                                    :value="old('bpjs.' . $payrollComp->id, $bpjsData[$payrollComp->id] ?? '')"
                                                    placeholder="Contoh: 000123456789 atau nilai nominal" />
                                                <x-input-error class="mt-1" :messages="$errors->get('bpjs.' . $payrollComp->id)" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 bg-slate-50/60 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500 font-medium">
                            Simpan perubahan BPJS secara mandiri.
                        </span>
                        <button type="submit" :disabled="isSubmitting"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition">
                            <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Data BPJS'"></span>
                        </button>
                    </div>
                </form>

            </div>
